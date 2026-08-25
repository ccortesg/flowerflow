<?php

namespace Tests\Feature;

use App\Actions\Assignments\AssignJudgesToSubmission;
use App\Actions\Assignments\CancelJudgeAssignment;
use App\Actions\Assignments\DeclareJudgeConflict;
use App\Actions\Assignments\ResolveJudgeConflict;
use App\Actions\BlindReview\ActivateBlindReviewPackage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Actions\Evaluations\OpenEvaluationDraft;
use App\Actions\Evaluations\SaveEvaluationDraft;
use App\Enums\CommunicationType;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeConflictType;
use App\Enums\JudgeProfileStatus;
use App\Exceptions\CommunicationCancelledException;
use App\Jobs\DeliverCommunication;
use App\Models\CommunicationDelivery;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\User;
use App\Services\CommunicationMessageRegistry;
use App\Services\EvaluationDraftCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JudgeAssignmentsAndConflictsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.judge_notifications.assignment_enabled' => false,
        ]);
        $this->seedFlowerFlow();
    }

    public function test_admin_selects_one_or_many_judges_manually_without_role_minimums_or_automatic_assignment(): void
    {
        $admin = $this->adminWithPassword();
        $primary = $this->activeJudge($admin, JudgeAssignmentRole::Primary, 1);
        $substitute = $this->activeJudge($admin, JudgeAssignmentRole::Substitute, 2);
        $submission = $this->admittedSubmission();

        foreach ([$this->participant(), $this->reviewer(), $primary] as $forbidden) {
            $this->actingAs($forbidden)->post(route('panel.assignments.judges.store', $submission), [
                'judge_profiles' => [$primary->judgeProfile->public_id],
                'notify_judges' => 0,
                'reason' => 'Selección manual sintética suficientemente extensa.',
                'current_password' => 'AdminPass1!',
            ])->assertForbidden();
        }

        $this->actingAs($admin)->get(route('panel.assignments.show', $submission))
            ->assertOk()
            ->assertSee('Asignar jueces manualmente')
            ->assertSee($primary->name)
            ->assertSee($substitute->name)
            ->assertDontSee('Crear cuatro asignaciones')
            ->assertDontSee('Asignar automáticamente');

        $payload = [
            'judge_profiles' => [$primary->judgeProfile->public_id, $substitute->judgeProfile->public_id],
            'notify_judges' => 0,
            'reason' => 'Selección administrativa manual de jueces sintéticos.',
            'current_password' => 'AdminPass1!',
        ];
        $this->actingAs($admin)->post(route('panel.assignments.judges.store', $submission), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('judge_assignments', 2);
        $activeRubric = RubricVersion::query()->where('status', 'active')->sole();
        $this->assertSame(2, $activeRubric->version);
        $this->assertTrue(JudgeAssignment::query()->get()->every(fn (JudgeAssignment $assignment): bool => $assignment->rubric_version_id === $activeRubric->id));

        $this->actingAs($admin)->post(route('panel.assignments.judges.store', $submission), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('judge_assignments', 2);
        $this->assertSame(2, DB::table('audit_logs')->where('action', 'assignment.created')->count());
    }

    public function test_a_substitute_can_be_the_only_available_judge_and_one_judge_can_receive_more_than_four_proposals(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin, JudgeAssignmentRole::Substitute, 1);

        foreach (range(1, 5) as $number) {
            $submission = $this->admittedSubmission();
            app(AssignJudgesToSubmission::class)->execute(
                $submission,
                $admin,
                [$judge->judgeProfile->public_id],
                "Asignación manual sintética número {$number} sin límite.",
                false,
            );
        }

        $this->assertSame(5, JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->count());
        $this->assertNull($judge->judgeProfile->fresh()->max_active_assignments);
        $this->assertDatabaseCount('judge_profiles', 1);
    }

    public function test_blind_package_can_be_generated_and_activated_with_zero_assignments(): void
    {
        $admin = $this->adminWithPassword();
        $submission = $this->admittedSubmission();

        app(GenerateBlindReviewPackageDraft::class)->execute($submission, $admin, 'Generación sintética sin cobertura mínima.');
        $package = app(ActivateBlindReviewPackage::class)->execute($submission, $admin, 'Activación sintética sin cobertura mínima.');

        $this->assertSame('active', $package->status->value);
        $this->assertDatabaseCount('judge_assignments', 0);
    }

    public function test_assignment_cancellation_is_append_only_and_is_rejected_after_evaluation_starts(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin, JudgeAssignmentRole::Primary, 1);
        $submission = $this->admittedSubmission();
        $first = app(AssignJudgesToSubmission::class)->execute(
            $submission,
            $admin,
            [$judge->judgeProfile->public_id],
            'Asignación manual sintética que será cancelada.',
            false,
        )['created']->sole();
        app(CancelJudgeAssignment::class)->execute($first, $admin, 'Cancelación administrativa sintética antes de evaluar.');
        $this->assertSame(JudgeAssignmentStatus::Cancelled, $first->fresh()->status);
        $this->assertNull($first->fresh()->current_slot);
        $this->assertDatabaseHas('audit_logs', ['action' => 'assignment.cancelled', 'auditable_id' => $first->id]);

        $second = app(AssignJudgesToSubmission::class)->execute(
            $submission,
            $admin,
            [$judge->judgeProfile->public_id],
            'Nueva asignación manual sintética después de cancelar.',
            false,
        )['created']->sole();
        app(GenerateBlindReviewPackageDraft::class)->execute($submission, $admin, 'Generación de paquete sintético para evaluar.');
        app(ActivateBlindReviewPackage::class)->execute($submission, $admin, 'Activación de paquete sintético para evaluar.');
        app(OpenEvaluationDraft::class)->execute($second, $judge);

        try {
            app(CancelJudgeAssignment::class)->execute($second, $admin, 'Intento de cancelar después de iniciar la evaluación.');
            $this->fail('Cancellation after evaluation must fail closed.');
        } catch (ValidationException) {
            $this->assertSame(JudgeAssignmentStatus::Active, $second->fresh()->status);
        }
    }

    public function test_any_active_judge_can_replace_and_a_replacement_conflict_requires_another_explicit_action(): void
    {
        $admin = $this->adminWithPassword();
        $first = $this->activeJudge($admin, JudgeAssignmentRole::Substitute, 1);
        $second = $this->activeJudge($admin, JudgeAssignmentRole::Primary, 2);
        $third = $this->activeJudge($admin, JudgeAssignmentRole::Substitute, 3);
        $submission = $this->admittedSubmission();
        $original = app(AssignJudgesToSubmission::class)->execute(
            $submission,
            $admin,
            [$first->judgeProfile->public_id],
            'Asignación manual original para probar cadena explícita.',
            false,
        )['created']->sole();

        $firstConflict = app(DeclareJudgeConflict::class)->execute($original, $first, JudgeConflictType::PersonalOrFamilyRelationship, null);
        $replacement = app(ResolveJudgeConflict::class)->execute(
            $firstConflict,
            $admin,
            $second->judgeProfile->public_id,
            'Reemplazo manual explícito por conflicto sintético original.',
        );
        $this->assertSame($original->id, $replacement->replaces_assignment_id);

        $secondConflict = app(DeclareJudgeConflict::class)->execute($replacement, $second, JudgeConflictType::ProfessionalOrEconomicRelationship, null);
        $this->assertDatabaseCount('judge_assignments', 2);
        $next = app(ResolveJudgeConflict::class)->execute(
            $secondConflict,
            $admin,
            $third->judgeProfile->public_id,
            'Segundo reemplazo manual explícito por conflicto sintético.',
        );
        $this->assertSame($replacement->id, $next->replaces_assignment_id);
        $this->assertDatabaseCount('judge_assignments', 3);
        $this->assertSame(JudgeAssignmentStatus::Voided, $replacement->fresh()->status);
        $this->assertSame(JudgeAssignmentStatus::Active, $next->status);
    }

    public function test_optional_assignment_notification_uses_outbox_and_contains_no_proposal_identity_or_content(): void
    {
        Queue::fake();
        config([
            'flowerflow.flags.communication_ledger' => true,
            'flowerflow.judge_notifications.assignment_enabled' => true,
        ]);
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin, JudgeAssignmentRole::Primary, 1);
        $submission = $this->admittedSubmission();
        $assignment = app(AssignJudgesToSubmission::class)->execute(
            $submission,
            $admin,
            [$judge->judgeProfile->public_id],
            'Asignación manual sintética con notificación opcional.',
            true,
        )['created']->sole();

        $delivery = CommunicationDelivery::query()->sole();
        $this->assertSame(CommunicationType::JudgeAssignmentCreated, $delivery->notification_type);
        $this->assertSame($assignment->id, $delivery->related_id);
        $this->assertSame('default', $delivery->queue);
        Queue::assertPushed(DeliverCommunication::class, 1);
        $prepared = app(CommunicationMessageRegistry::class)->prepare($delivery);
        $html = (string) $prepared['message']->toMail($judge)->render();
        $this->assertStringContainsString($assignment->public_id, $html);
        $this->assertStringContainsString('Ver asignación', $html);
        $this->assertStringNotContainsString($submission->title, $html);
        $this->assertStringNotContainsString($submission->user->email, $html);

        app(CancelJudgeAssignment::class)->execute($assignment, $admin, 'Cancelación sintética antes de ejecutar el correo.');
        $this->expectException(CommunicationCancelledException::class);
        app(CommunicationMessageRegistry::class)->prepare($delivery);
    }

    public function test_pending_setup_judges_can_be_assigned_and_replaced_without_early_access_or_deferred_mail(): void
    {
        Queue::fake();
        config([
            'flowerflow.flags.communication_ledger' => true,
            'flowerflow.judge_notifications.assignment_enabled' => true,
        ]);
        $admin = $this->adminWithPassword();
        $active = $this->activeJudge($admin, JudgeAssignmentRole::Primary, 1);
        $pending = $this->pendingJudge($admin, JudgeAssignmentRole::Substitute, 2);
        $submission = $this->admittedSubmission();

        $result = app(AssignJudgesToSubmission::class)->execute(
            $submission,
            $admin,
            [$active->judgeProfile->public_id, $pending->judgeProfile->public_id],
            'Asignación mixta sintética antes de completar el onboarding.',
            true,
        );

        $this->assertCount(2, $result['created']);
        $this->assertSame(1, $result['notifications_skipped_pending']);
        $this->assertDatabaseCount('communication_deliveries', 1);
        $pendingAssignment = JudgeAssignment::query()
            ->where('judge_profile_id', $pending->judgeProfile->id)
            ->sole();
        $this->assertSame(JudgeAssignmentStatus::Active, $pendingAssignment->status);
        $this->actingAs($pending)->get(route('judge.assignments.index'))
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assignment.notification_skipped',
            'auditable_id' => $pendingAssignment->id,
        ]);
        $audit = DB::table('audit_logs')
            ->where('action', 'assignment.notification_skipped')
            ->where('auditable_id', $pendingAssignment->id)
            ->value('metadata');
        $this->assertStringContainsString('judge_setup_pending', (string) $audit);

        DB::table('users')->where('id', $pending->id)->update(['email_verified_at' => now('UTC')]);
        DB::table('judge_profiles')->where('id', $pending->judgeProfile->id)->update([
            'status' => JudgeProfileStatus::Active->value,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ]);
        $pending->unsetRelation('judgeProfile')->refresh();
        $this->actingAs($pending)->get(route('judge.assignments.index'))
            ->assertOk()
            ->assertSee($pendingAssignment->public_id);
        $this->assertDatabaseCount('judge_assignments', 2);

        $otherPending = $this->pendingJudge($admin, JudgeAssignmentRole::Primary, 3);
        $conflict = app(DeclareJudgeConflict::class)->execute(
            $result['created']->first(),
            $active,
            JudgeConflictType::ParticipationInSubmission,
            null,
        );
        $replacement = app(ResolveJudgeConflict::class)->execute(
            $conflict,
            $admin,
            $otherPending->judgeProfile->public_id,
            'Reemplazo explícito sintético hacia una cuenta aún pendiente.',
            true,
        );
        $this->assertSame($otherPending->judgeProfile->id, $replacement->judge_profile_id);
        $this->assertDatabaseCount('communication_deliveries', 1);
    }

    public function test_legal_v2_evaluation_uses_four_criteria_and_server_authoritative_decimal_total(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin, JudgeAssignmentRole::Primary, 1);
        $submission = $this->admittedSubmission();
        $assignment = app(AssignJudgesToSubmission::class)->execute(
            $submission,
            $admin,
            [$judge->judgeProfile->public_id],
            'Asignación sintética para comprobar la rúbrica legal v2.',
            false,
        )['created']->sole();
        app(GenerateBlindReviewPackageDraft::class)->execute($submission, $admin, 'Paquete sintético para rúbrica legal v2.');
        app(ActivateBlindReviewPackage::class)->execute($submission, $admin, 'Activación sintética para rúbrica legal v2.');

        $evaluation = app(OpenEvaluationDraft::class)->execute($assignment, $judge);
        $this->assertSame(2, $assignment->rubricVersion->version);
        $this->assertDatabaseCount('evaluation_scores', 4);
        $this->assertNull($evaluation->currentRevision->total_raw);

        $evaluation = app(SaveEvaluationDraft::class)->execute($assignment, $judge, [
            'lock_version' => 0,
            'general_comment' => null,
            'criteria' => [
                ['code' => 'relevance_diagnosis', 'score' => '7.5', 'comment' => null],
                ['code' => 'quality_originality', 'score' => '8', 'comment' => null],
                ['code' => 'participation_coordination', 'score' => '6.5', 'comment' => null],
                ['code' => 'impact_sustainability', 'score' => '9', 'comment' => null],
            ],
        ]);

        $this->assertSame('77.5000', $evaluation->currentRevision->total_raw);
        $this->assertSame('77.50', app(EvaluationDraftCalculator::class)->display($evaluation->currentRevision->total_raw));
        $this->assertSame(4, $evaluation->currentRevision->scores()->whereNotNull('score')->count());
    }

    public function test_suspended_roleless_multi_role_and_incoherent_pending_profiles_are_not_assignable(): void
    {
        $admin = $this->adminWithPassword();
        $submission = $this->admittedSubmission();
        $suspended = $this->pendingJudge($admin, JudgeAssignmentRole::Primary, 10);
        $suspended->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended])->save();
        $roleless = $this->pendingJudge($admin, JudgeAssignmentRole::Primary, 11);
        $roleless->syncRoles([]);
        $multiRole = $this->pendingJudge($admin, JudgeAssignmentRole::Primary, 12);
        $multiRole->assignRole('reviewer');
        $incoherent = $this->pendingJudge($admin, JudgeAssignmentRole::Primary, 13);
        $incoherent->forceFill(['email_verified_at' => now('UTC')])->save();
        $incoherent->judgeProfile->forceFill(['password_initialized_at' => now('UTC')])->save();

        foreach ([$suspended, $roleless, $multiRole, $incoherent] as $candidate) {
            try {
                app(AssignJudgesToSubmission::class)->execute(
                    $submission,
                    $admin,
                    [$candidate->judgeProfile->public_id],
                    'Intento sintético con un perfil que debe fallar de forma cerrada.',
                    false,
                );
                $this->fail('The invalid judge candidate must be rejected.');
            } catch (ValidationException) {
                $this->assertDatabaseMissing('judge_assignments', [
                    'judge_profile_id' => $candidate->judgeProfile->id,
                ]);
            }
        }

        $this->assertDatabaseCount('judge_assignments', 0);
    }

    private function admittedSubmission(): Submission
    {
        [, $submission, $review] = $this->submittedReview();
        $review->update([
            'status' => EligibilityReviewStatus::Admitted,
            'resolved_at' => now('UTC'),
            'participant_reason' => 'Admisibilidad sintética aprobada para M6A.',
        ]);

        $version = $submission->versions()->latest('version')->firstOrFail();
        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode([
            'schema_version' => 1,
            'category' => ['slug' => $submission->category->slug, 'name' => $submission->category->name],
            'submission' => [
                'participation_type' => 'individual',
                'title' => 'Proyecto sintético M6A',
                'summary' => 'Resumen sintético para operaciones de jueces.',
                'description_html' => '<p>Descripción sintética para operaciones de jueces.</p>',
                'description_text' => 'Descripción sintética para operaciones de jueces.',
            ],
            'external_links' => [],
            'files' => [],
        ], JSON_THROW_ON_ERROR)]);

        return $submission->fresh();
    }

    private function activeJudge(User $creator, JudgeAssignmentRole $role, int $number): User
    {
        $judge = User::factory()->create([
            'name' => "Juez M6A {$number}",
            'email' => "judge-m6a-{$number}-".fake()->unique()->numerify('######').'@example.test',
            'email_verified_at' => now('UTC'),
        ]);
        $judge->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $judge->id,
            'assignment_role' => $role,
            'status' => JudgeProfileStatus::Active,
            'max_active_assignments' => null,
            'created_by_user_id' => $creator->id,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ])->save();

        return $judge->setRelation('judgeProfile', $profile);
    }

    private function pendingJudge(User $creator, JudgeAssignmentRole $role, int $number): User
    {
        $judge = User::factory()->create([
            'name' => "Juez pendiente {$number}",
            'email' => "judge-pending-{$number}-".fake()->unique()->numerify('######').'@example.test',
            'email_verified_at' => null,
        ]);
        $judge->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $judge->id,
            'assignment_role' => $role,
            'status' => JudgeProfileStatus::PendingSetup,
            'max_active_assignments' => null,
            'created_by_user_id' => $creator->id,
            'password_initialized_at' => null,
            'activated_at' => null,
        ])->save();

        return $judge->setRelation('judgeProfile', $profile);
    }

    private function adminWithPassword(): User
    {
        return $this->admin([
            'email' => 'admin-assignments-'.fake()->unique()->numerify('######').'@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
    }
}
