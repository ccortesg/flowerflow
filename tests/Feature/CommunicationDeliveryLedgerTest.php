<?php

namespace Tests\Feature;

use App\Enums\CommunicationAttemptSource;
use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationType;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Enums\SubmissionReminderBatchScope;
use App\Enums\SubmissionReminderBatchStatus;
use App\Enums\SubmissionReminderStatus;
use App\Jobs\DeliverCommunication;
use App\Mail\AdmissibilityUpdate;
use App\Mail\SubmissionAdministrativelyFinalized;
use App\Mail\SubmissionDraftReminder;
use App\Mail\SubmissionReceived;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\CommunicationDelivery;
use App\Models\Submission;
use App\Models\SubmissionReminder;
use App\Models\SubmissionReminderBatch;
use App\Models\User;
use App\Notifications\JudgeAccountSetupNotification;
use App\Notifications\JudgeAccountStatusNotification;
use App\Notifications\JudgeVerifyEmailNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\ResilientMailDispatcher;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use LogicException;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommunicationDeliveryLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.communication_ledger' => true,
            'flowerflow.flags.submission_reminders' => true,
            'flowerflow.flags.submissions' => true,
            'flowerflow.mail.queue_connection' => 'database',
            'flowerflow.mail.queue' => 'default',
            'flowerflow.communication_ledger.force_queue' => 'high',
        ]);
        $this->seedFlowerFlow();
    }

    public function test_all_current_mail_families_create_encrypted_idempotent_deliveries_with_id_only_jobs(): void
    {
        Queue::fake();
        $dispatcher = app(ResilientMailDispatcher::class);
        $account = User::factory()->unverified()->create(['email' => 'account-ledger@example.test']);
        $admin = $this->admin();
        $judge = User::factory()->unverified()->create(['email' => 'judge-ledger@example.test']);
        $judge->assignRole('judge');
        $judgeProfile = $judge->judgeProfile()->make();
        $judgeProfile->forceFill([
            'assignment_role' => JudgeAssignmentRole::Primary,
            'status' => JudgeProfileStatus::Active,
            'max_active_assignments' => null,
            'created_by_user_id' => $admin->id,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ])->save();
        [$participant, $submission, $review] = $this->submittedReview();
        $draft = $this->draftFor($this->participant(['email' => 'reminder-ledger@example.test']));
        $reminder = $this->reminderFor($draft, $admin);

        $messages = [
            [$account, new VerifyEmailNotification, 'account-verification'],
            [$account, new ResetPasswordNotification('synthetic-reset-token'), 'account-reset'],
            [$judge, new JudgeAccountSetupNotification(999, 'synthetic-setup-token'), 'judge-setup'],
            [$judge, new JudgeVerifyEmailNotification, 'judge-verification'],
            [$judge, new JudgeAccountStatusNotification('reactivated'), 'judge-status'],
            [$participant, new SubmissionReceived($submission), 'submission-received'],
            [$participant, new SubmissionAdministrativelyFinalized($submission), 'submission-administrative'],
            [$draft->user, new SubmissionDraftReminder($reminder), 'submission-reminder:'.$reminder->public_id],
            [$participant, new AdmissibilityUpdate($review, 'admitted'), 'admissibility-update'],
        ];

        foreach ($messages as [$recipient, $message, $source]) {
            $this->assertTrue($dispatcher->recordAndDispatch($recipient, $message, 'Synthetic warning.', $source));
        }
        $this->assertDatabaseCount('communication_deliveries', 9);
        $this->assertDatabaseCount('communication_delivery_attempts', 9);
        Queue::assertPushed(DeliverCommunication::class, 9);
        $this->assertCount(9, CommunicationDelivery::query()->pluck('notification_type')->unique());

        $raw = DB::table('communication_deliveries')->get();
        $serialized = $raw->toJson();
        $this->assertStringNotContainsString('account-ledger@example.test', $serialized);
        $this->assertStringNotContainsString('synthetic-reset-token', $serialized);
        $this->assertStringNotContainsString('synthetic-setup-token', $serialized);
        $this->assertNotNull($reminder->fresh()->communication_delivery_id);

        $job = new DeliverCommunication(123);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $job);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $job);
        $this->assertFalse(property_exists($job, 'message'));
        $this->assertFalse(property_exists($job, 'recipient'));
        $this->assertStringNotContainsString('synthetic-reset-token', serialize($job));

        $dispatcher->recordAndDispatch($account, new VerifyEmailNotification, 'Synthetic warning.', 'account-verification');
        $this->assertDatabaseCount('communication_deliveries', 9);
    }

    public function test_worker_revalidates_sends_and_purges_sensitive_context(): void
    {
        Queue::fake();
        Notification::fake();
        $recipient = User::factory()->unverified()->create(['email' => 'worker-ledger@example.test']);
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $recipient,
            new VerifyEmailNotification,
            'Synthetic warning.',
            'worker-verification',
        );
        $delivery = CommunicationDelivery::query()->sole();

        app()->call([new DeliverCommunication($delivery->id), 'handle']);

        $delivery->refresh();
        $this->assertSame(CommunicationDeliveryStatus::Sent, $delivery->status);
        $this->assertSame('Aceptado por el servidor de correo', $delivery->status->label());
        $this->assertNull($delivery->recipient_address);
        $this->assertNull($delivery->context);
        $this->assertSame(1, $delivery->attempts_count);
        $this->assertSame(CommunicationAttemptStatus::Sent, $delivery->attempts()->sole()->status);
        Notification::assertSentTo($recipient, VerifyEmailNotification::class);

        foreach (AuditLog::query()->where('action', 'like', 'communication_delivery.%')->get() as $audit) {
            $json = json_encode($audit->metadata);
            $this->assertStringNotContainsString('worker-ledger@example.test', $json);
            $this->assertStringNotContainsString('http', $json);
        }
    }

    public function test_panel_is_read_only_on_get_and_exact_admin_can_force_once_with_optimistic_lock(): void
    {
        Queue::fake();
        $recipient = User::factory()->unverified()->create();
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $recipient,
            new VerifyEmailNotification,
            'Synthetic warning.',
            'panel-verification',
        );
        $delivery = CommunicationDelivery::query()->sole();
        $before = [
            CommunicationDelivery::query()->count(),
            DB::table('communication_delivery_attempts')->count(),
            $delivery->updated_at->toISOString(),
        ];

        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->get(route('panel.communication-deliveries.index'))->assertForbidden();
        $multiRole = $this->admin();
        $multiRole->assignRole('reviewer');
        $this->actingAs($multiRole)->get(route('panel.communication-deliveries.index'))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.communication-deliveries.index'))
            ->assertOk()
            ->assertSee('Notificaciones')
            ->assertSee('Aceptado por el servidor', false)
            ->assertSee($delivery->recipient_mask)
            ->assertDontSee($recipient->email);
        $this->actingAs($admin)->get(route('panel.communication-deliveries.show', $delivery))->assertOk();
        $this->assertSame($before, [
            CommunicationDelivery::query()->count(),
            DB::table('communication_delivery_attempts')->count(),
            $delivery->fresh()->updated_at->toISOString(),
        ]);

        $payload = [
            'lock_version' => $delivery->lock_version,
            'reason' => 'Se requiere adelantar esta comunicación sintética pendiente.',
            'confirm_processing' => '1',
        ];
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.communication-deliveries.store', $delivery), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $delivery->refresh();
        $this->assertSame(CommunicationDeliveryStatus::Queued, $delivery->status);
        $this->assertSame(2, $delivery->attempts()->count());
        $this->assertSame(CommunicationAttemptSource::AdminForced, $delivery->attempts()->latest('attempt_number')->first()->source);
        $this->assertStringNotContainsString(
            $payload['reason'],
            (string) DB::table('communication_delivery_attempts')->latest('attempt_number')->value('reason'),
        );
        Queue::assertPushed(DeliverCommunication::class, fn ($job): bool => $job->queue === 'high');

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.communication-deliveries.store', $delivery), $payload)
            ->assertStatus(409);
        $this->assertSame(2, $delivery->attempts()->count());
    }

    public function test_unknown_requires_duplicate_risk_acknowledgement_and_terminal_records_have_no_action(): void
    {
        Queue::fake();
        $recipient = User::factory()->unverified()->create();
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $recipient,
            new VerifyEmailNotification,
            'Synthetic warning.',
            'unknown-verification',
        );
        $delivery = CommunicationDelivery::query()->sole();
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Unknown,
            'unknown_at' => now('UTC'),
            'failure_code' => 'synthetic_unknown',
        ])->save();
        $admin = $this->admin();
        $payload = [
            'lock_version' => $delivery->lock_version,
            'reason' => 'Se revisó el resultado desconocido y se solicita reenvío.',
            'confirm_processing' => '1',
        ];

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.communication-deliveries.store', $delivery), $payload)
            ->assertSessionHasErrors('duplicate_risk_acknowledged');

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.communication-deliveries.store', $delivery), [
                ...$payload,
                'duplicate_risk_acknowledged' => '1',
            ])->assertRedirect();
        $this->assertTrue($delivery->attempts()->latest('attempt_number')->first()->duplicate_risk_acknowledged);

        $delivery->refresh()->forceFill([
            'status' => CommunicationDeliveryStatus::Cancelled,
            'recipient_address' => null,
            'context' => null,
        ])->save();
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.communication-deliveries.process', $delivery))
            ->assertStatus(409);
    }

    public function test_reminder_backfill_is_dry_run_by_default_and_idempotent_when_applied(): void
    {
        $admin = $this->admin();
        $reminder = $this->reminderFor($this->draftFor($this->participant()), $admin);

        $this->artisan('flowerflow:communications-backfill-reminders')
            ->expectsOutputToContain('Dry-run eligible reminders: 1')
            ->assertSuccessful();
        $this->assertDatabaseCount('communication_deliveries', 0);

        $this->artisan('flowerflow:communications-backfill-reminders', ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('communication_deliveries', 1);
        $this->assertNotNull($reminder->fresh()->communication_delivery_id);
        $this->artisan('flowerflow:communications-backfill-reminders', ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('communication_deliveries', 1);
    }

    public function test_reminder_action_uses_central_delivery_and_keeps_domain_status_in_sync(): void
    {
        Queue::fake();
        Mail::fake();
        $admin = $this->admin();
        $submission = $this->draftFor($this->participant(['email' => 'central-reminder@example.test']));

        $this->actingAs($admin)
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $reminder = SubmissionReminder::query()->sole();
        $delivery = $reminder->communicationDelivery;
        $this->assertNotNull($delivery);
        $this->assertSame(CommunicationDeliveryStatus::Queued, $delivery->status);
        $this->assertSame(SubmissionReminderStatus::Queued, $reminder->status);
        Queue::assertPushed(DeliverCommunication::class, 1);

        app()->call([new DeliverCommunication($delivery->id), 'handle']);

        $this->assertSame(CommunicationDeliveryStatus::Sent, $delivery->fresh()->status);
        $this->assertSame(SubmissionReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertSame(1, $reminder->batch->fresh()->sent_count);
        Mail::assertSent(SubmissionDraftReminder::class, 1);
    }

    public function test_invalid_event_cancels_and_transport_exhaustion_fails_without_exposing_context(): void
    {
        Queue::fake();
        $verified = User::factory()->create();
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $verified,
            new VerifyEmailNotification,
            'Synthetic warning.',
            'already-verified',
        );
        $cancelled = CommunicationDelivery::query()->sole();
        app()->call([new DeliverCommunication($cancelled->id), 'handle']);
        $cancelled->refresh();
        $this->assertSame(CommunicationDeliveryStatus::Cancelled, $cancelled->status);
        $this->assertSame('verification_no_longer_required', $cancelled->failure_code);
        $this->assertNull($cancelled->context);
        $this->assertNull($cancelled->recipient_address);

        $participant = $this->participant(['email' => 'transport-failure@example.test']);
        $submission = $this->submittedFor($participant);
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $participant,
            new SubmissionReceived($submission),
            'Synthetic warning.',
            'transport-failure',
        );
        $failed = CommunicationDelivery::query()->where('notification_type', CommunicationType::SubmissionReceived->value)->sole();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Synthetic transport failure with no recipient data.'));
        $job = new DeliverCommunication($failed->id);
        $job->tries = 1;
        app()->call([$job, 'handle']);

        $failed->refresh();
        $this->assertSame(CommunicationDeliveryStatus::Failed, $failed->status);
        $this->assertSame('RuntimeException', $failed->failure_code);
        $this->assertNotNull($failed->context);
        $this->assertNotNull($failed->context_expires_at);
        $this->assertStringNotContainsString($participant->email, DB::table('communication_deliveries')->where('id', $failed->id)->value('context'));
    }

    public function test_stalled_processing_reconciles_to_unknown_without_sending(): void
    {
        Queue::fake();
        Notification::fake();
        $recipient = User::factory()->unverified()->create();
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $recipient,
            new VerifyEmailNotification,
            'Synthetic warning.',
            'stalled-verification',
        );
        $delivery = CommunicationDelivery::query()->sole();
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Processing,
            'processing_at' => now('UTC')->subMinutes(10),
            'attempts_count' => 1,
        ])->save();
        $attempt = $delivery->attempts()->sole();
        $attempt->forceFill([
            'status' => CommunicationAttemptStatus::Processing,
            'started_at' => now('UTC')->subMinutes(10),
        ])->save();

        $this->artisan('flowerflow:communications-reconcile')->assertSuccessful();

        $this->assertSame(CommunicationDeliveryStatus::Unknown, $delivery->fresh()->status);
        $this->assertSame(CommunicationAttemptStatus::Unknown, $attempt->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_menu_and_routes_fail_closed_by_flag_exact_role_and_permissions(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Paquetes ciegos', 'Notificaciones', 'Cuenta y seguridad']);

        $this->app['auth']->logout();
        $this->get(route('panel.communication-deliveries.index'))->assertRedirect(route('login'));
        foreach ([$this->participant(), $this->reviewer(), User::factory()->create()] as $blocked) {
            $this->actingAs($blocked)->get(route('panel.communication-deliveries.index'))->assertForbidden();
        }
        $judge = User::factory()->create();
        $judge->assignRole('judge');
        $this->actingAs($judge)->get(route('panel.communication-deliveries.index'))->assertForbidden();

        Role::findByName('admin')->revokePermissionTo('view communication deliveries');
        $this->actingAs($admin)->get(route('panel.communication-deliveries.index'))->assertForbidden();
        Role::findByName('admin')->givePermissionTo('view communication deliveries');
        config(['flowerflow.flags.communication_ledger' => false]);
        $this->actingAs($admin)->get(route('panel.communication-deliveries.index'))->assertNotFound();
        $this->actingAs($admin)->get(route('panel.dashboard'))->assertDontSee('Notificaciones');
    }

    public function test_database_queue_payload_contains_no_recipient_or_security_context(): void
    {
        $recipient = User::factory()->unverified()->create(['email' => 'payload-ledger@example.test']);
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $recipient,
            new ResetPasswordNotification('payload-secret-token'),
            'Synthetic warning.',
            'payload-reset',
        );
        Queue::connection('database')->push(new DeliverCommunication(CommunicationDelivery::query()->sole()->id));

        $payload = (string) DB::table('jobs')->where('queue', 'default')->value('payload');
        $this->assertNotSame('', $payload);
        $this->assertStringNotContainsString('payload-ledger@example.test', $payload);
        $this->assertStringNotContainsString('payload-secret-token', $payload);
        $this->assertStringNotContainsString('ResetPasswordNotification', $payload);
    }

    public function test_models_cannot_be_deleted_and_migration_refuses_down_when_evidence_exists(): void
    {
        Queue::fake();
        $recipient = User::factory()->unverified()->create();
        app(ResilientMailDispatcher::class)->recordAndDispatch(
            $recipient,
            new VerifyEmailNotification,
            'Synthetic warning.',
            'rollback-evidence',
        );
        $delivery = CommunicationDelivery::query()->sole();
        $attempt = $delivery->attempts()->sole();

        try {
            $attempt->delete();
            $this->fail('The attempt should be immutable against deletion.');
        } catch (LogicException) {
            $this->assertDatabaseHas('communication_delivery_attempts', ['id' => $attempt->id]);
        }
        try {
            $delivery->delete();
            $this->fail('The delivery should be immutable against deletion.');
        } catch (LogicException) {
            $this->assertDatabaseHas('communication_deliveries', ['id' => $delivery->id]);
        }

        $migration = require database_path('migrations/2026_08_23_120000_create_communication_delivery_ledger.php');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot remove the communication ledger');
        $migration->down();
    }

    private function draftFor(User $owner): Submission
    {
        $category = Category::query()->firstOrFail();

        return Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $owner->id,
            'participation_type' => 'individual',
            'title' => 'Borrador sintético para bitácora',
            'summary' => 'Resumen sintético.',
            'description_html' => '<p>Descripción sintética.</p>',
            'description_text' => 'Descripción sintética.',
            'status' => 'draft',
        ]);
    }

    private function reminderFor(Submission $submission, User $admin): SubmissionReminder
    {
        $batch = new SubmissionReminderBatch;
        $batch->forceFill([
            'requested_by_user_id' => $admin->id,
            'scope' => SubmissionReminderBatchScope::Single,
            'status' => SubmissionReminderBatchStatus::Queued,
            'eligible_count' => 1,
            'queued_count' => 1,
        ])->save();
        $reminder = new SubmissionReminder;
        $reminder->forceFill([
            'submission_reminder_batch_id' => $batch->id,
            'submission_id' => $submission->id,
            'recipient_user_id' => $submission->user_id,
            'status' => SubmissionReminderStatus::Queued,
            'link_expires_at' => now('UTC')->addDay(),
        ])->save();

        return $reminder->load('recipient');
    }

    private function submittedFor(User $owner): Submission
    {
        $submission = $this->draftFor($owner);
        $submission->forceFill([
            'status' => 'submitted',
            'folio' => 'HMO26-887766',
            'submitted_at' => now('UTC'),
        ])->save();

        return $submission;
    }
}
