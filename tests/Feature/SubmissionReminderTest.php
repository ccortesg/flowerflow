<?php

namespace Tests\Feature;

use App\Enums\SubmissionReminderBatchScope;
use App\Enums\SubmissionReminderStatus;
use App\Jobs\SendSubmissionDraftReminder;
use App\Mail\SubmissionDraftReminder;
use App\Mail\SubmissionReceived;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Submission;
use App\Models\SubmissionReminder;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubmissionReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.submissions' => true,
            'flowerflow.flags.submission_reminders' => true,
            'flowerflow.flags.admissibility_review' => true,
            'flowerflow.mail.queue_connection' => 'sync',
            'flowerflow.mail.queue' => 'default',
            'flowerflow.submission_reminders.cooldown_hours' => 24,
        ]);
        Mail::fake();
        $this->seedFlowerFlow();
    }

    public function test_admin_can_send_one_owner_only_reminder_and_cooldown_prevents_duplicates(): void
    {
        $owner = $this->participant(['email' => 'owner@example.test']);
        $submission = $this->draftFor($owner, '<script>alert(1)</script> Proyecto');
        $team = Team::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Equipo sintético',
            'eligibility_declared_at' => now('UTC'),
        ]);
        $team->members()->create([
            'full_name' => 'Integrante sintético',
            'email' => 'member@example.test',
            'is_representative' => false,
        ]);
        $submission->forceFill(['team_id' => $team->id, 'participation_type' => 'team'])->save();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect()
            ->assertSessionHas('status');

        $reminder = SubmissionReminder::query()->sole();
        $this->assertSame(SubmissionReminderStatus::Sent, $reminder->status);
        $this->assertSame($owner->id, $reminder->recipient_user_id);
        $this->assertDatabaseHas('submission_reminder_batches', [
            'scope' => SubmissionReminderBatchScope::Single->value,
            'eligible_count' => 1,
            'queued_count' => 1,
            'sent_count' => 1,
            'skipped_count' => 0,
        ]);
        Mail::assertSent(SubmissionDraftReminder::class, fn ($mail) => $mail->hasTo($owner->email));
        Mail::assertNotSent(SubmissionDraftReminder::class, fn ($mail) => $mail->hasTo('member@example.test'));

        $html = (new SubmissionDraftReminder($reminder))->render();
        $this->assertStringContainsString('logo_flowerflow_transparente.png', $html);
        $this->assertStringContainsString('logo_florecehermosillo_transparente.png', $html);
        $this->assertStringContainsString('Enviar propuesta', $html);
        $this->assertStringContainsString('Hermosillo Florece 2026', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);

        $this->actingAs($admin)
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('submission_reminders', 1);
        $this->assertDatabaseHas('submission_reminder_batches', [
            'scope' => SubmissionReminderBatchScope::Single->value,
            'eligible_count' => 1,
            'queued_count' => 0,
            'skipped_count' => 1,
        ]);
        Mail::assertSent(SubmissionDraftReminder::class, 1);
    }

    public function test_bulk_confirmation_counts_all_active_drafts_independent_of_list_filters_and_page(): void
    {
        $first = $this->draftFor($this->participant(['email' => 'first@example.test']), 'Primer borrador');
        $second = $this->draftFor(
            $this->participant(['email' => 'second@example.test']),
            'Segundo borrador',
            Category::query()->orderBy('id')->skip(1)->firstOrFail(),
        );
        $submitted = $this->draftFor(
            $this->participant(['email' => 'submitted@example.test']),
            'Ya enviada',
            Category::query()->orderBy('id')->skip(2)->firstOrFail(),
        );
        $submitted->forceFill(['status' => 'submitted', 'submitted_at' => now('UTC'), 'folio' => 'HMO26-999991'])->save();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('panel.submissions.reminders.create', ['status' => 'submitted', 'page' => 99]))
            ->assertOk()
            ->assertSee('Propuestas en borrador')
            ->assertSee('2');

        $this->actingAs($admin)
            ->post(route('panel.submissions.reminders.store', ['status' => 'submitted', 'page' => 99]))
            ->assertRedirect(route('panel.submissions.index'));

        $this->assertDatabaseCount('submission_reminders', 2);
        $this->assertDatabaseHas('submission_reminders', ['submission_id' => $first->id]);
        $this->assertDatabaseHas('submission_reminders', ['submission_id' => $second->id]);
        $this->assertDatabaseMissing('submission_reminders', ['submission_id' => $submitted->id]);
        Mail::assertSent(SubmissionDraftReminder::class, 2);
    }

    public function test_signed_get_is_read_only_and_signed_post_submits_without_attachment(): void
    {
        $owner = $this->participant(['email' => 'signed-owner@example.test']);
        $submission = $this->draftFor($owner, 'Proyecto sin archivo');
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect();
        $reminder = SubmissionReminder::query()->sole();
        $getUrl = (new SubmissionDraftReminder($reminder))->confirmationUrl();

        $before = $this->aggregateCounts();
        $this->app['auth']->logout();
        $this->get($getUrl)->assertOk()->assertSee('Confirmar envío de propuesta');
        $this->get($getUrl)->assertOk();
        $this->assertSame($before, $this->aggregateCounts());

        $postUrl = URL::temporarySignedRoute(
            'submissions.reminders.submit',
            $reminder->link_expires_at,
            ['submission' => $submission, 'reminder' => $reminder],
        );
        $this->post($postUrl, $this->acceptances())
            ->assertOk()
            ->assertSee('Propuesta enviada');

        $submission->refresh();
        $this->assertSame('submitted', $submission->status);
        $this->assertNotNull($submission->folio);
        $this->assertDatabaseCount('submission_files', 0);
        $this->assertDatabaseCount('submission_versions', 1);
        $this->assertDatabaseCount('legal_acceptances', 3);
        $this->assertDatabaseCount('eligibility_reviews', 1);
        $this->assertNotNull($reminder->fresh()->consumed_at);
        $snapshot = $submission->versions()->sole()->snapshot;
        $this->assertSame('signed_reminder', $snapshot['finalization']['mode']);
        $this->assertTrue($snapshot['finalization']['attachment_requirement_waived']);
        $this->assertSame(['document_attachment'], $snapshot['finalization']['requirements_waived']);
        $this->assertDatabaseHas('submission_events', ['event' => 'submission.submitted_from_reminder']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'submission.submitted_from_reminder']);
        Mail::assertQueued(SubmissionReceived::class, 1);

        $this->get($getUrl)->assertGone();
        $this->post($postUrl, $this->acceptances())->assertGone();
        $this->assertDatabaseCount('submission_versions', 1);
        $this->assertDatabaseCount('legal_acceptances', 3);
    }

    public function test_signed_links_fail_closed_when_tampered_expired_or_crossed(): void
    {
        $first = $this->draftFor($this->participant(), 'Primera propuesta');
        $second = $this->draftFor(
            $this->participant(),
            'Segunda propuesta',
            Category::query()->orderBy('id')->skip(1)->firstOrFail(),
        );
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $first))
            ->assertRedirect();
        $reminder = SubmissionReminder::query()->sole();
        $valid = (new SubmissionDraftReminder($reminder))->confirmationUrl();

        $this->app['auth']->logout();
        $this->get($valid.'&tampered=1')->assertForbidden();

        $crossed = URL::temporarySignedRoute(
            'submissions.reminders.confirm',
            $reminder->link_expires_at,
            ['submission' => $second, 'reminder' => $reminder],
        );
        $this->get($crossed)->assertNotFound();

        $expired = URL::temporarySignedRoute(
            'submissions.reminders.confirm',
            now()->subSecond(),
            ['submission' => $first, 'reminder' => $reminder],
        );
        $this->get($expired)->assertForbidden();
        $this->assertSame('draft', $first->fresh()->status);
        $this->assertDatabaseCount('submission_versions', 0);
        $this->assertDatabaseCount('legal_acceptances', 0);
    }

    public function test_missing_content_or_legal_acceptance_never_creates_submission_evidence(): void
    {
        $owner = $this->participant();
        $submission = $this->draftFor($owner, 'Contenido incompleto');
        $submission->forceFill(['description_text' => null, 'description_html' => null])->save();
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect();
        $reminder = SubmissionReminder::query()->sole();
        $url = URL::temporarySignedRoute(
            'submissions.reminders.submit',
            $reminder->link_expires_at,
            ['submission' => $submission, 'reminder' => $reminder],
        );

        $this->app['auth']->logout();
        $this->post($url, $this->acceptances())->assertSessionHasErrors('description');
        $submission->forceFill(['description_text' => 'Descripción restaurada', 'description_html' => '<p>Descripción restaurada</p>'])->save();
        $this->post($url, [])->assertSessionHasErrors([
            'accept_call_rules',
            'accept_terms',
            'accept_privacy',
        ]);

        $this->assertSame('draft', $submission->fresh()->status);
        $this->assertDatabaseCount('submission_versions', 0);
        $this->assertDatabaseCount('legal_acceptances', 0);
        $this->assertDatabaseCount('eligibility_reviews', 0);
        $this->assertNull($reminder->fresh()->consumed_at);
    }

    public function test_role_permission_flag_status_recipient_and_deadline_guards_fail_closed(): void
    {
        $submission = $this->draftFor($this->participant(), 'Guardas de recordatorio');
        $reviewer = $this->reviewer();
        $adminWithoutPermission = $this->admin();

        $this->post(route('panel.submissions.reminders.submissions.store', $submission))->assertRedirect(route('login'));
        $this->actingAs($reviewer)
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertForbidden();
        $adminRole = Role::findByName('admin');
        $adminRole->revokePermissionTo('send submission reminders');
        $this->actingAs($adminWithoutPermission)
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertForbidden();
        $adminRole->givePermissionTo('send submission reminders');

        config(['flowerflow.flags.submission_reminders' => false]);
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertNotFound();
        config(['flowerflow.flags.submission_reminders' => true]);

        $submission->user->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect()
            ->assertSessionHas('warning');
        $this->assertDatabaseCount('submission_reminders', 0);

        $submission->user->forceFill(['email_verified_at' => now('UTC')])->save();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-23 23:59:59', 'America/Hermosillo'));
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect();
        $this->assertDatabaseCount('submission_reminders', 1);

        $other = $this->draftFor(
            $this->participant(),
            'Después del cierre',
            Category::query()->orderBy('id')->skip(1)->firstOrFail(),
        );
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-24 00:00:00', 'America/Hermosillo'));
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $other))
            ->assertSessionHasErrors('deadline');
        $this->assertDatabaseMissing('submission_reminders', ['submission_id' => $other->id]);
    }

    public function test_job_contract_and_audit_metadata_are_redacted(): void
    {
        $job = new SendSubmissionDraftReminder(123);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $job);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $job);

        $owner = $this->participant(['email' => 'pii@example.test']);
        $submission = $this->draftFor($owner, 'Texto confidencial sintético');
        $this->actingAs($this->admin())
            ->post(route('panel.submissions.reminders.submissions.store', $submission))
            ->assertRedirect();

        foreach (AuditLog::query()->where('action', 'like', 'submission_reminder.%')->get() as $audit) {
            $json = json_encode($audit->metadata);
            $this->assertStringNotContainsString('pii@example.test', $json);
            $this->assertStringNotContainsString('Texto confidencial sintético', $json);
            $this->assertStringNotContainsString('http', $json);
        }
    }

    /** @return array<string, int> */
    private function aggregateCounts(): array
    {
        return [
            'versions' => (int) DB::table('submission_versions')->count(),
            'events' => (int) DB::table('submission_events')->count(),
            'acceptances' => (int) DB::table('legal_acceptances')->count(),
            'reviews' => (int) DB::table('eligibility_reviews')->count(),
            'reminders' => (int) DB::table('submission_reminders')->count(),
        ];
    }

    /** @return array<string, string> */
    private function acceptances(): array
    {
        return [
            'accept_call_rules' => '1',
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ];
    }

    private function draftFor(User $owner, string $title, ?Category $category = null): Submission
    {
        $category ??= Category::query()->firstOrFail();

        return Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $owner->id,
            'participation_type' => 'individual',
            'title' => $title,
            'summary' => 'Resumen sintético suficiente para el recordatorio.',
            'description_html' => '<p>Descripción sintética.</p>',
            'description_text' => 'Descripción sintética suficiente.',
            'status' => 'draft',
        ]);
    }
}
