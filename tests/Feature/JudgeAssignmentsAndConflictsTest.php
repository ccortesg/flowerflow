<?php

namespace Tests\Feature;

use App\Actions\Assignments\ActivateSubmissionCoverage;
use App\Actions\Assignments\DeclareJudgeConflict;
use App\Actions\Assignments\ResolveJudgeConflict;
use App\Actions\Rubrics\ActivateRubricVersion;
use App\Actions\Rubrics\CreateRubricDraft;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Enums\JudgeConflictStatus;
use App\Enums\JudgeConflictType;
use App\Enums\JudgeProfileStatus;
use App\Models\AuditLog;
use App\Models\EligibilityReview;
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\User;
use App\Services\EvaluationRubricContract;
use Database\Seeders\FlowerFlowSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
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
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
        ]);
        $this->seedFlowerFlow();
    }

    public function test_provisioning_is_idempotent_additive_and_does_not_create_assignments_or_conflicts(): void
    {
        $participant = $this->participant(['email' => 'preserved-m4@example.test']);
        $this->seed(FlowerFlowSeeder::class);
        $this->seed(FlowerFlowSeeder::class);

        $this->assertDatabaseCount('judge_assignments', 0);
        $this->assertDatabaseCount('judge_conflicts', 0);
        $this->assertTrue($participant->fresh()->hasExactRoles(['participant']));

        foreach (['view evaluation assignments', 'manage evaluation assignments', 'resolve evaluation conflicts'] as $permission) {
            $this->assertTrue(Role::findByName('admin')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('judge')->hasPermissionTo($permission));
        }
        $this->assertTrue(Role::findByName('judge')->hasPermissionTo('declare own evaluation conflicts'));
        $this->assertFalse(Role::findByName('admin')->hasPermissionTo('declare own evaluation conflicts'));
    }

    public function test_only_admin_can_create_exact_coverage_for_current_admitted_version(): void
    {
        $admin = $this->adminWithPassword();
        [$primaries, $substitutes] = $this->judgePanel($admin);
        $rubric = $this->activateRubric($admin);
        [, $submission] = $this->admittedSubmission();

        foreach ([$this->participant(), $this->reviewer(), $primaries->first(), User::factory()->create()] as $forbidden) {
            $this->actingAs($forbidden)->post(route('panel.assignments.activate', $submission), [
                'reason' => 'Intento sintético no autorizado para cobertura.',
                'current_password' => 'password',
            ])->assertForbidden();
        }

        $response = $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'Cobertura inicial aprobada para la propuesta sintética.',
            'current_password' => 'AdminPass1!',
        ]);
        $response->assertRedirect()->assertSessionHasNoErrors();

        $assignments = JudgeAssignment::query()->orderBy('judge_profile_id')->get();
        $this->assertCount(4, $assignments);
        $this->assertSame($primaries->pluck('judgeProfile.id')->sort()->values()->all(), $assignments->pluck('judge_profile_id')->sort()->values()->all());
        $this->assertTrue($substitutes->every(fn (User $substitute): bool => ! $assignments->contains('judge_profile_id', $substitute->judgeProfile->id)));
        $this->assertTrue($assignments->every(fn (JudgeAssignment $assignment) => $assignment->type === JudgeAssignmentType::Initial
            && $assignment->status === JudgeAssignmentStatus::Active
            && $assignment->rubric_version_id === $rubric->id
            && $assignment->due_at->timezone('America/Hermosillo')->format('Y-m-d H:i:s') === '2026-08-27 23:59:59'));

        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'Cobertura inicial aprobada para la propuesta sintética.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('judge_assignments', 4);
        $this->assertDatabaseHas('audit_logs', ['action' => 'assignment.coverage_created', 'actor_user_id' => $admin->id]);
        $this->actingAs($admin)->get(route('panel.assignments.index'))
            ->assertOk()->assertSee($submission->public_id);
        $this->actingAs($admin)->get(route('panel.assignments.show', $submission))
            ->assertOk()->assertSee('4 de 4')->assertSee('Inicial');

        $contract = app(EvaluationRubricContract::class);
        $newRubric = app(CreateRubricDraft::class)->execute(
            $admin,
            $submission->competition,
            2,
            'Rúbrica sintética posterior a la cobertura',
            $contract->versionAttributes(),
            $contract->criteria(),
        );
        app(ActivateRubricVersion::class)->execute($newRubric, $admin, 'Activación posterior que no debe reescribir M4.');
        $this->assertSame([$rubric->id], JudgeAssignment::query()->distinct()->pluck('rubric_version_id')->all());
    }

    public function test_ineligible_or_ambiguous_inputs_fail_without_partial_assignments(): void
    {
        $admin = $this->adminWithPassword();
        $this->judgePanel($admin);
        [, $submission, $review] = $this->submittedReview();

        $review->update(['status' => EligibilityReviewStatus::Admitted]);
        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'No debe asignarse cuando ninguna rúbrica está activa.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);

        $review->update(['status' => EligibilityReviewStatus::Pending]);
        $this->activateRubric($admin);

        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'No debe asignarse antes de tener admisibilidad final.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);

        $review->update(['status' => EligibilityReviewStatus::Admitted]);
        $submission->versions()->create([
            'version' => 2,
            'snapshot' => ['schema_version' => 1, 'submission' => ['title' => 'Nueva versión sintética']],
            'created_at' => now('UTC'),
        ]);
        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'La revisión no corresponde a la versión final vigente.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);
        $this->actingAs($admin)->get(route('panel.assignments.index'))
            ->assertOk()->assertDontSee($submission->public_id);

        $draftOwner = $this->participant(['email' => 'draft-owner-m4@example.test']);
        $draft = Submission::query()->create([
            'competition_id' => $submission->competition_id,
            'category_id' => $submission->category_id,
            'user_id' => $draftOwner->id,
            'participation_type' => 'individual',
            'title' => 'Borrador no elegible',
            'summary' => 'Borrador sintético.',
            'description_html' => '<p>Borrador</p>',
            'description_text' => 'Borrador',
            'status' => 'draft',
        ]);
        $this->actingAs($admin)->post(route('panel.assignments.activate', $draft), [
            'reason' => 'Un borrador nunca puede recibir cobertura de jueces.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);
    }

    public function test_judge_surface_is_owner_scoped_and_exposes_only_m4_metadata(): void
    {
        $admin = $this->adminWithPassword();
        [$primaries] = $this->judgePanel($admin);
        $this->activateRubric($admin);
        [, $submission] = $this->admittedSubmission();
        $submission->forceFill([
            'title' => 'IDENTITY-CANARY-TITLE',
            'folio' => 'IDENTITY-CANARY-FOLIO',
            'summary' => 'IDENTITY-CANARY-SUMMARY',
            'description_text' => 'IDENTITY-CANARY-DESCRIPTION',
        ])->save();
        app(ActivateSubmissionCoverage::class)->execute($submission, $admin, 'Cobertura para comprobar minimización de datos sintéticos.');
        $assignment = JudgeAssignment::query()->where('judge_profile_id', $primaries->first()->judgeProfile->id)->firstOrFail();
        $other = $primaries->get(1);

        $html = $this->actingAs($primaries->first())->get(route('judge.assignments.show', $assignment))
            ->assertOk()->assertSee($assignment->public_id)->assertSee($submission->category->name)->getContent();
        foreach (['IDENTITY-CANARY-TITLE', 'IDENTITY-CANARY-FOLIO', 'IDENTITY-CANARY-SUMMARY', 'IDENTITY-CANARY-DESCRIPTION', 'Pertinencia'] as $canary) {
            $this->assertStringNotContainsString($canary, $html);
        }
        $this->actingAs($other)->get(route('judge.assignments.show', $assignment))->assertForbidden();
        $this->actingAs($primaries->first())->get('/juez/asignaciones/01J00000000000000000000000')->assertNotFound();
        $this->actingAs($primaries->first())->get(route('panel.assignments.index'))->assertForbidden();
    }

    public function test_invalid_active_judge_composition_fails_without_partial_coverage(): void
    {
        $admin = $this->adminWithPassword();
        [$primaries, $substitutes] = $this->judgePanel($admin);
        $this->activateRubric($admin);
        [, $submission] = $this->admittedSubmission();

        $primaries->first()->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended->value])->save();
        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'Tres jueces principales no forman cobertura válida.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);

        $primaries->first()->judgeProfile->forceFill(['status' => JudgeProfileStatus::Active->value])->save();
        $substitutes->first()->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended->value])->save();
        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'Sin sustituto activo no puede fijarse la cobertura.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);

        $substitutes->first()->judgeProfile->forceFill(['status' => JudgeProfileStatus::Active->value])->save();
        $extra = $this->activeJudge($admin, JudgeAssignmentRole::Primary, 6);
        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'Cinco jueces principales tampoco forman cobertura válida.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);
        $this->assertSame(JudgeProfileStatus::Active, $extra->judgeProfile->fresh()->status);

        $extra->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended->value])->save();
        $extraSubstitute = $this->activeJudge($admin, JudgeAssignmentRole::Substitute, 7);
        $this->actingAs($admin)->post(route('panel.assignments.activate', $submission), [
            'reason' => 'Tres jueces sustitutos tampoco forman cobertura válida.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('coverage');
        $this->assertDatabaseCount('judge_assignments', 0);
        $this->assertSame(JudgeProfileStatus::Active, $extraSubstitute->judgeProfile->fresh()->status);
    }

    public function test_conflict_is_append_only_and_admin_manually_selects_one_of_two_substitutes(): void
    {
        $admin = $this->adminWithPassword();
        [$primaries, $substitutes] = $this->judgePanel($admin);
        $selectedSubstitute = $substitutes->last();
        $this->activateRubric($admin);
        [, $submission] = $this->admittedSubmission();
        app(ActivateSubmissionCoverage::class)->execute($submission, $admin, 'Cobertura para ejercitar conflicto y reasignación sintéticos.');
        $judge = $primaries->first();
        $original = JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->firstOrFail();

        $this->actingAs($judge)->post(route('judge.assignments.conflicts.store', $original), [
            'type' => 'not-an-approved-conflict',
        ])->assertSessionHasErrors('type');
        $this->actingAs($judge)->post(route('judge.assignments.conflicts.store', $original), [
            'type' => JudgeConflictType::Other->value,
            'explanation' => 'Demasiado corta',
        ])->assertSessionHasErrors('explanation');
        $this->assertDatabaseCount('judge_conflicts', 0);

        $this->actingAs($judge)->post(route('judge.assignments.conflicts.store', $original), [
            'type' => JudgeConflictType::Other->value,
            'explanation' => 'Explicación suficientemente detallada del conflicto sintético.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $conflict = JudgeConflict::query()->sole();
        $this->assertSame(JudgeConflictStatus::Declared, $conflict->status);
        $this->assertSame(JudgeAssignmentStatus::ConflictDeclared, $original->fresh()->status);
        $this->actingAs($admin)->get(route('panel.assignments.show', $submission))
            ->assertOk()
            ->assertSee('name="substitute_judge_profile"', false)
            ->assertSee($substitutes->first()->name)
            ->assertSee($substitutes->last()->name)
            ->assertSee('Sin límite');

        $this->actingAs($judge)->post(route('judge.assignments.conflicts.store', $original), [
            'type' => JudgeConflictType::Other->value,
            'explanation' => 'Explicación suficientemente detallada del conflicto sintético.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('judge_conflicts', 1);

        $this->actingAs($admin)->post(route('panel.assignments.conflicts.resolve', $conflict), [
            'substitute_judge_profile' => $selectedSubstitute->judgeProfile->public_id,
            'reason' => 'Reasignación administrativa al sustituto disponible y activo.',
            'current_password' => 'incorrecta',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame(JudgeAssignmentStatus::ConflictDeclared, $original->fresh()->status);

        $this->actingAs($admin)->post(route('panel.assignments.conflicts.resolve', $conflict), [
            'reason' => 'No debe existir selección automática del juez sustituto.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('substitute_judge_profile');
        $this->assertSame(JudgeAssignmentStatus::ConflictDeclared, $original->fresh()->status);

        $this->actingAs($admin)->post(route('panel.assignments.conflicts.resolve', $conflict), [
            'substitute_judge_profile' => '01J00000000000000000000000',
            'reason' => 'No debe aceptarse un sustituto alterado o ajeno.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('substitute_judge_profile');

        $this->actingAs($admin)->post(route('panel.assignments.conflicts.resolve', $conflict), [
            'substitute_judge_profile' => $selectedSubstitute->judgeProfile->public_id,
            'reason' => 'Reasignación administrativa al sustituto disponible y activo.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $replacement = JudgeAssignment::query()->where('type', JudgeAssignmentType::Replacement)->sole();
        $this->assertSame($selectedSubstitute->judgeProfile->id, $replacement->judge_profile_id);
        $this->assertSame($original->submission_version_id, $replacement->submission_version_id);
        $this->assertSame($original->rubric_version_id, $replacement->rubric_version_id);
        $this->assertTrue($replacement->due_at->equalTo($original->due_at));
        $this->assertSame(JudgeAssignmentStatus::Voided, $original->fresh()->status);
        $this->assertSame(JudgeConflictStatus::ResolvedReassigned, $conflict->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'assignment.replacement_created', 'auditable_id' => $replacement->id]);

        $this->actingAs($selectedSubstitute)->post(route('judge.assignments.conflicts.store', $replacement), [
            'type' => JudgeConflictType::PersonalOrFamilyRelationship->value,
        ])->assertRedirect();
        $replacementConflict = $replacement->conflict()->firstOrFail();
        $this->actingAs($admin)->post(route('panel.assignments.conflicts.resolve', $replacementConflict), [
            'substitute_judge_profile' => $substitutes->first()->judgeProfile->public_id,
            'reason' => 'No existe un segundo reemplazo autorizado para este caso.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('replacement');
        $this->assertSame(JudgeAssignmentStatus::ConflictDeclared, $replacement->fresh()->status);
        $this->assertDatabaseCount('judge_assignments', 5);
    }

    public function test_mass_assignment_and_redacted_audit_invariants_hold(): void
    {
        $admin = $this->adminWithPassword();
        $this->judgePanel($admin);
        $this->activateRubric($admin);
        [, $submission] = $this->admittedSubmission();
        app(ActivateSubmissionCoverage::class)->execute($submission, $admin, 'Cobertura que comprueba auditoría redactada e integridad.');

        try {
            JudgeAssignment::query()->create(['status' => JudgeAssignmentStatus::Active]);
            $this->fail('Mass assignment must remain blocked.');
        } catch (MassAssignmentException) {
            $this->assertDatabaseCount('judge_assignments', 4);
        }

        $payload = AuditLog::query()->where('action', 'like', 'assignment.%')->get()->toJson();
        foreach (['password', 'IDENTITY-CANARY', 'description_html', 'snapshot', 'two_factor'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, mb_strtolower($payload));
        }
    }

    public function test_both_substitutes_are_unlimited_and_manual_selection_remains_independent(): void
    {
        $admin = $this->adminWithPassword();
        [$primaries, $substitutes] = $this->judgePanel($admin);
        $firstSubstitute = $substitutes->first();
        $secondSubstitute = $substitutes->last();
        $this->activateRubric($admin);

        foreach (range(1, 31) as $number) {
            [, $submission] = $this->admittedSubmission();
            app(ActivateSubmissionCoverage::class)->execute(
                $submission,
                $admin,
                "Cobertura sintética número {$number} para comprobar sustituto ilimitado.",
            );
            $original = JudgeAssignment::query()
                ->where('submission_version_id', $submission->versions()->latest('version')->firstOrFail()->id)
                ->where('judge_profile_id', $primaries->first()->judgeProfile->id)
                ->firstOrFail();
            $conflict = app(DeclareJudgeConflict::class)->execute(
                $original,
                $primaries->first(),
                JudgeConflictType::ParticipationInSubmission,
                null,
            );

            app(ResolveJudgeConflict::class)->execute(
                $conflict,
                $admin,
                $firstSubstitute->judgeProfile->public_id,
                "Reasignación sintética ilimitada número {$number}.",
            );
        }

        $this->assertSame(31, JudgeAssignment::query()
            ->where('judge_profile_id', $firstSubstitute->judgeProfile->id)
            ->whereIn('status', [JudgeAssignmentStatus::Active, JudgeAssignmentStatus::ConflictDeclared])
            ->count());
        $this->assertNull($firstSubstitute->judgeProfile->fresh()->max_active_assignments);

        [, $submission] = $this->admittedSubmission();
        app(ActivateSubmissionCoverage::class)->execute(
            $submission,
            $admin,
            'Cobertura sintética para seleccionar al segundo sustituto.',
        );
        $original = JudgeAssignment::query()
            ->where('submission_version_id', $submission->versions()->latest('version')->firstOrFail()->id)
            ->where('judge_profile_id', $primaries->last()->judgeProfile->id)
            ->firstOrFail();
        $conflict = app(DeclareJudgeConflict::class)->execute(
            $original,
            $primaries->last(),
            JudgeConflictType::ParticipationInSubmission,
            null,
        );
        app(ResolveJudgeConflict::class)->execute(
            $conflict,
            $admin,
            $secondSubstitute->judgeProfile->public_id,
            'Reasignación sintética seleccionada para el segundo sustituto.',
        );

        $this->assertSame(1, JudgeAssignment::query()
            ->where('judge_profile_id', $secondSubstitute->judgeProfile->id)
            ->whereIn('status', [JudgeAssignmentStatus::Active, JudgeAssignmentStatus::ConflictDeclared])
            ->count());
        $this->assertNull($secondSubstitute->judgeProfile->fresh()->max_active_assignments);
        $this->assertSame(0, JudgeConflict::query()->where('status', JudgeConflictStatus::Declared)->count());
    }

    private function adminWithPassword(): User
    {
        return $this->admin([
            'email' => 'admin-m4-'.fake()->unique()->numerify('######').'@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
    }

    /** @return array{Collection<int, User>, Collection<int, User>} */
    private function judgePanel(User $creator): array
    {
        $primaries = collect(range(1, 4))->map(fn (int $number) => $this->activeJudge($creator, JudgeAssignmentRole::Primary, $number));
        $substitutes = collect(range(5, 6))->map(fn (int $number) => $this->activeJudge($creator, JudgeAssignmentRole::Substitute, $number));

        return [$primaries, $substitutes];
    }

    private function activeJudge(User $creator, JudgeAssignmentRole $role, int $number): User
    {
        $user = User::factory()->create(['email' => "judge-m4-{$number}-".fake()->unique()->numerify('######').'@example.test']);
        $user->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $user->id,
            'assignment_role' => $role->value,
            'status' => JudgeProfileStatus::Active->value,
            'max_active_assignments' => $role->maxActiveAssignments(),
            'created_by_user_id' => $creator->id,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ])->save();

        return $user->setRelation('judgeProfile', $profile);
    }

    private function activateRubric(User $admin): RubricVersion
    {
        $rubric = RubricVersion::query()->where('version', 1)->firstOrFail();

        return app(ActivateRubricVersion::class)->execute(
            $rubric,
            $admin,
            'Activación de rúbrica para pruebas sintéticas de M4.',
        );
    }

    /** @return array{User, Submission, EligibilityReview} */
    private function admittedSubmission(): array
    {
        [$participant, $submission, $review] = $this->submittedReview();
        $review->update([
            'status' => EligibilityReviewStatus::Admitted,
            'resolved_at' => now('UTC'),
            'participant_reason' => 'Admisibilidad sintética aprobada para pruebas M4.',
        ]);

        return [$participant, $submission->fresh('category'), $review->fresh()];
    }
}
