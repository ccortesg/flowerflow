<?php

namespace Tests\Feature;

use App\Mail\SubmissionAdministrativelyFinalized;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Submission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrativeSubmissionFinalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.submissions' => true,
            'flowerflow.flags.administrative_finalization' => true,
            'flowerflow.flags.admissibility_review' => true,
            'flowerflow.mail.queue_connection' => 'sync',
        ]);
        Mail::fake();
        $this->seedFlowerFlow();
    }

    public function test_exact_admin_can_finalize_minimum_content_without_file_profile_team_or_legal_acceptances(): void
    {
        $owner = $this->participant(['email' => 'administrative-owner@example.test']);
        $owner->forceFill(['email_verified_at' => null])->save();
        $owner->profile()->delete();
        $submission = $this->draftFor($owner);
        $admin = $this->admin();
        $reason = 'Solicitud documentada por la coordinación durante la recepción.';

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->get(route('panel.submissions.administrative-finalization.show', $submission))
            ->assertOk()
            ->assertSee('No se registrarán aceptaciones jurídicas');

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), [
                'reason' => $reason,
                'confirm_administrative_finalization' => '1',
            ])
            ->assertRedirect(route('panel.submissions.show', $submission));

        $submission->refresh();
        $this->assertSame('submitted', $submission->status);
        $this->assertSame('HMO26-'.str_pad((string) $submission->id, 6, '0', STR_PAD_LEFT), $submission->folio);
        $this->assertDatabaseCount('submission_files', 0);
        $this->assertDatabaseCount('legal_acceptances', 0);
        $this->assertDatabaseCount('submission_versions', 1);
        $this->assertDatabaseCount('eligibility_reviews', 1);
        $version = $submission->versions()->sole();
        $finalization = $version->snapshot['finalization'];
        $this->assertSame('administrative', $finalization['mode']);
        $this->assertSame($admin->id, $finalization['actor_user_id']);
        $this->assertSame($reason, $finalization['administrative_reason']);
        $this->assertContains('submission_legal_acceptances', $finalization['requirements_waived']);
        $this->assertSame($owner->public_id, $version->snapshot['participant']['public_id']);
        $this->assertDatabaseHas('submission_events', [
            'submission_id' => $submission->id,
            'actor_user_id' => $admin->id,
            'event' => 'submission.submitted_administratively',
        ]);
        $audit = AuditLog::query()->where('action', 'submission.submitted_administratively')->sole();
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertArrayNotHasKey('administrative_reason', $audit->metadata);
        $this->assertStringNotContainsString($reason, json_encode($audit->metadata));
        Mail::assertQueued(SubmissionAdministrativelyFinalized::class, fn ($mail) => $mail->hasTo($owner->email));
    }

    public function test_admin_finalization_requires_recent_password_confirmation_reason_and_explicit_confirmation(): void
    {
        $submission = $this->draftFor($this->participant());
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('panel.submissions.administrative-finalization.show', $submission))
            ->assertRedirect(route('password.confirm'));
        $this->actingAs($admin)
            ->post(route('panel.submissions.administrative-finalization.store', $submission), [
                'reason' => str_repeat('a', 20),
                'confirm_administrative_finalization' => '1',
            ])
            ->assertRedirect(route('password.confirm'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), [
                'reason' => 'corta',
            ])
            ->assertSessionHasErrors(['reason', 'confirm_administrative_finalization']);
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->get(route('panel.submissions.administrative-finalization.show', $submission))
            ->assertOk()
            ->assertSee('La razón debe tener al menos 20 caracteres.')
            ->assertSee('Debes confirmar que comprendes la excepción administrativa.');

        $this->assertSame('draft', $submission->fresh()->status);
        $this->assertDatabaseCount('submission_versions', 0);
        $this->assertDatabaseCount('legal_acceptances', 0);
    }

    public function test_visitor_participant_reviewer_multi_role_and_admin_without_permission_are_denied(): void
    {
        $submission = $this->draftFor($this->participant());
        $payload = [
            'reason' => 'Razón administrativa sintética suficientemente extensa.',
            'confirm_administrative_finalization' => '1',
        ];

        $this->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertRedirect(route('login'));
        $this->actingAs($this->participant())
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertForbidden();
        $this->actingAs($this->reviewer())
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertForbidden();

        $multiRole = $this->admin();
        $multiRole->assignRole('reviewer');
        $this->actingAs($multiRole)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertForbidden();

        $adminRole = Role::findByName('admin');
        $adminRole->revokePermissionTo('administratively finalize submissions');
        $this->actingAs($this->admin())
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertForbidden();
        $adminRole->givePermissionTo('administratively finalize submissions');

        $this->assertSame('draft', $submission->fresh()->status);
        $this->assertDatabaseCount('submission_versions', 0);
    }

    public function test_feature_state_minimum_content_and_exact_deadline_are_enforced_server_side(): void
    {
        $submission = $this->draftFor($this->participant());
        $admin = $this->admin();
        $payload = [
            'reason' => 'Razón administrativa sintética suficientemente extensa.',
            'confirm_administrative_finalization' => '1',
        ];

        config(['flowerflow.flags.administrative_finalization' => false]);
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertNotFound();
        config(['flowerflow.flags.administrative_finalization' => true]);

        $submission->forceFill(['summary' => ''])->save();
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertSessionHasErrors('summary');
        $submission->forceFill(['summary' => 'Resumen restaurado'])->save();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-23 23:59:59', 'America/Hermosillo'));
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertRedirect(route('panel.submissions.show', $submission));
        $this->assertSame('submitted', $submission->fresh()->status);

        $afterClose = $this->draftFor(
            $this->participant(),
            Category::query()->orderBy('id')->skip(1)->firstOrFail(),
        );
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-24 00:00:00', 'America/Hermosillo'));
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $afterClose), $payload)
            ->assertSessionHasErrors('deadline');
        $this->assertSame('draft', $afterClose->fresh()->status);
    }

    public function test_repeated_http_action_is_idempotent_and_never_duplicates_evidence(): void
    {
        $submission = $this->draftFor($this->participant());
        $admin = $this->admin();
        $reason = 'Razón administrativa sintética suficientemente extensa.';
        $payload = [
            'reason' => $reason,
            'confirm_administrative_finalization' => '1',
        ];

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertRedirect(route('panel.submissions.show', $submission));
        $firstFolio = $submission->fresh()->folio;
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), $payload)
            ->assertRedirect(route('panel.submissions.show', $submission));

        $this->assertSame($firstFolio, $submission->fresh()->folio);
        $this->assertDatabaseCount('submission_versions', 1);
        $this->assertDatabaseCount('submission_events', 1);
        $this->assertDatabaseCount('eligibility_reviews', 1);
        $this->assertDatabaseCount('legal_acceptances', 0);
        Mail::assertQueued(SubmissionAdministrativelyFinalized::class, 1);
    }

    public function test_administrative_retry_rejects_a_submission_finalized_by_another_mode(): void
    {
        $submission = $this->draftFor($this->participant());
        $submission->forceFill([
            'status' => 'submitted',
            'submitted_at' => now('UTC'),
            'folio' => 'HMO26-999999',
            'submission_idempotency_key' => 'participant-flow',
        ])->save();
        $submission->versions()->create([
            'version' => 1,
            'snapshot' => ['finalization' => ['mode' => 'participant']],
            'created_at' => now('UTC'),
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.submissions.administrative-finalization.store', $submission), [
                'reason' => 'Razón administrativa sintética suficientemente extensa.',
                'confirm_administrative_finalization' => '1',
            ])
            ->assertSessionHasErrors('submission');

        $this->assertSame('participant-flow', $submission->fresh()->submission_idempotency_key);
        $this->assertDatabaseCount('submission_versions', 1);
        $this->assertDatabaseCount('submission_events', 0);
        $this->assertDatabaseCount('eligibility_reviews', 0);
        Mail::assertNothingQueued();
    }

    private function draftFor(User $owner, ?Category $category = null): Submission
    {
        $category ??= Category::query()->firstOrFail();

        return Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $owner->id,
            'participation_type' => 'individual',
            'title' => 'Proyecto administrativo sintético',
            'summary' => 'Resumen sintético suficiente.',
            'description_html' => '<p>Descripción sintética.</p>',
            'description_text' => 'Descripción sintética suficiente.',
            'status' => 'draft',
        ]);
    }
}
