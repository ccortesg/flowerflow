<?php

namespace Tests\Feature;

use App\Actions\Assignments\DeclareJudgeConflict;
use App\Actions\Assignments\ResolveJudgeConflict;
use App\Enums\BlindReviewPackageStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeConflictType;
use App\Enums\JudgeProfileStatus;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\EvaluationRevision;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Permission\Models\Role;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;

class EvaluationDraftTest extends TestCase
{
    use CreatesEvaluationScenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.timezone' => 'America/Hermosillo',
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
        ]);
        $this->seedFlowerFlow();
    }

    public function test_get_is_read_only_and_explicit_start_is_idempotent_and_complete(): void
    {
        [, $primaries] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $assignment = $this->assignmentFor($judge);

        $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Iniciar evaluación')
            ->assertSee('btn btn-flower', false)
            ->assertSee('btn btn-outline-warning', false)
            ->assertSee('Si existe un conflicto, decláralo antes de evaluar')
            ->assertDontSee('Pertinencia');
        $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))->assertOk();
        $this->assertDatabaseCount('evaluations', 0);
        $this->assertDatabaseCount('evaluation_revisions', 0);
        $this->assertDatabaseCount('evaluation_scores', 0);

        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment), [
            'evaluation_id' => 1,
        ])->assertSessionHasErrors('evaluation');
        $this->assertDatabaseCount('evaluations', 0);

        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertRedirect(route('judge.assignments.show', $assignment))->assertSessionHasNoErrors();
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertRedirect(route('judge.assignments.show', $assignment))->assertSessionHasNoErrors();

        $evaluation = Evaluation::query()->with('currentRevision.scores')->sole();
        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseCount('evaluation_revisions', 1);
        $this->assertDatabaseCount('evaluation_scores', 5);
        $this->assertSame(1, $evaluation->currentRevision->revision_number);
        $this->assertSame(0, $evaluation->lock_version);
        $this->assertNull($evaluation->currentRevision->total_raw);
        $this->assertTrue($evaluation->currentRevision->scores->every(
            fn ($score): bool => $score->score === null && $score->calculated_component === null
        ));
        $this->assertSame(1, AuditLog::query()->where('action', 'evaluation.draft_opened')->count());

        foreach ([
            fn () => DB::table('evaluations')->where('id', $evaluation->id)->update(['status' => 'submitted']),
            fn () => DB::table('evaluation_revisions')->where('id', $evaluation->current_revision_id)->update(['revision_number' => 0]),
            fn () => DB::table('evaluation_scores')->where('evaluation_revision_id', $evaluation->current_revision_id)->limit(1)->update(['score' => '0.1000', 'calculated_component' => '0.2000']),
            fn () => DB::table('evaluation_scores')->where('evaluation_revision_id', $evaluation->current_revision_id)->limit(1)->update(['calculated_component' => '1.0000']),
        ] as $invalidDatabaseMutation) {
            try {
                $invalidDatabaseMutation();
                $this->fail('The MySQL M6 check constraint must reject the invalid mutation.');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
        $evaluation->refresh();
        $this->assertSame('draft', $evaluation->status->value);
        $this->assertSame(1, $evaluation->currentRevision->revision_number);
        $this->assertTrue($evaluation->currentRevision->scores()->get()->every(
            fn (EvaluationScore $score): bool => $score->score === null && $score->calculated_component === null
        ));

        $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Pertinencia')
            ->assertSee('20.0000 %')
            ->assertSee('Rango 0.0000–10.0000; paso exacto 0.5000.')
            ->assertSee('Disponible al capturar todos los criterios.')
            ->assertSee('Guardar borrador');

        try {
            Evaluation::query()->create(['lock_version' => 99]);
            $this->fail('Mass assignment must be blocked.');
        } catch (MassAssignmentException) {
            $this->addToAssertionCount(1);
        }
        try {
            $evaluation->delete();
            $this->fail('Evaluation deletion must be blocked.');
        } catch (LogicException) {
            $this->assertDatabaseCount('evaluations', 1);
        }
        try {
            $evaluation->currentRevision->delete();
            $this->fail('Evaluation revision deletion must be blocked.');
        } catch (LogicException) {
            $this->assertDatabaseCount('evaluation_revisions', 1);
        }
        try {
            EvaluationScore::query()->firstOrFail()->delete();
            $this->fail('Evaluation score deletion must be blocked.');
        } catch (LogicException) {
            $this->assertDatabaseCount('evaluation_scores', 5);
        }
    }

    public function test_partial_and_complete_saves_use_server_decimal_vectors_and_distinguish_null_from_zero(): void
    {
        [, $primaries] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $assignment = $this->assignmentFor($judge);
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();

        $this->save($judge, $assignment, 0, [
            ['code' => 'feasibility', 'score' => '0.5000', 'comment' => null],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $revision = EvaluationRevision::query()->sole();
        $this->assertNull($revision->total_raw);
        $this->assertDatabaseHas('evaluation_scores', ['score' => '0.5000', 'calculated_component' => '1.2500']);
        $this->assertSame(1, Evaluation::query()->sole()->lock_version);

        $this->save($judge, $assignment, 1, $this->criteriaPayload([
            'pertinence' => '0', 'clarity' => '0', 'feasibility' => '0', 'impact' => '0', 'coherence' => '0',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('0.0000', $revision->fresh()->total_raw);

        $this->save($judge, $assignment, 2, $this->criteriaPayload([
            'pertinence' => '10', 'clarity' => '10', 'feasibility' => '10', 'impact' => '10', 'coherence' => '10',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('100.0000', $revision->fresh()->total_raw);

        $this->save($judge, $assignment, 3, $this->criteriaPayload([
            'pertinence' => '0', 'clarity' => '0', 'feasibility' => '0.5', 'impact' => '0', 'coherence' => '0',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('1.2500', $revision->fresh()->total_raw);

        $this->save($judge, $assignment, 4, $this->criteriaPayload([
            'pertinence' => '7.5', 'clarity' => '8', 'feasibility' => '6.5', 'impact' => '9', 'coherence' => '5.5',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('75.2500', $revision->fresh()->total_raw);
        $this->assertSame(5, Evaluation::query()->sole()->lock_version);
        $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()->assertSee('75.25 de 100.00');
    }

    public function test_strict_payload_decimal_comment_and_xss_validation_fail_closed(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        [, $primaries] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $assignment = $this->assignmentFor($judge);
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();

        $hostile = $this->criteriaPayload(['pertinence' => '5']);
        $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => 0,
            'general_comment' => null,
            'criteria' => $hostile,
            'total_raw' => '100.0000',
            'calculated_component' => '20.0000',
        ])->assertSessionHasErrors('evaluation');
        $this->assertSame(0, Evaluation::query()->sole()->lock_version);

        foreach (['5e0', 'NaN', 'INF', '-0.5', '10.5', '0.1', '0.50000'] as $value) {
            $this->save($judge, $assignment, 0, [[
                'code' => 'pertinence', 'score' => $value, 'comment' => null,
            ]])->assertSessionHasErrors('criteria.0.score');
        }
        $this->save($judge, $assignment, 0, [
            ['code' => 'pertinence', 'score' => '5', 'comment' => null],
            ['code' => 'pertinence', 'score' => '6', 'comment' => null],
        ])->assertSessionHasErrors('criteria');
        $this->save($judge, $assignment, 0, [[
            'code' => 'foreign-rubric', 'score' => '5', 'comment' => null,
        ]])->assertSessionHasErrors('criteria');
        $generalCommentResponse = $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => 0,
            'general_comment' => str_repeat('á', 2001),
            'criteria' => [],
        ]);
        $generalCommentResponse->assertRedirect()->assertSessionHasErrors('general_comment');
        $this->save($judge, $assignment, 0, [[
            'code' => 'pertinence', 'score' => '5', 'comment' => str_repeat('ñ', 1001),
        ]])->assertSessionHasErrors('criteria.0.comment');
        $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => 0,
            'general_comment' => null,
            'criteria' => [[
                'code' => 'pertinence', 'score' => '5', 'comment' => null, 'rubric_criterion_id' => 1,
            ]],
        ])->assertSessionHasErrors('criteria.0');

        $criterionBoundary = str_repeat('ñ', 500)."\n".str_repeat('ñ', 499);
        $generalBoundary = str_repeat('á', 1000)."\n".str_repeat('á', 999);
        $this->save($judge, $assignment, 0, [[
            'code' => 'pertinence', 'score' => '5', 'comment' => $criterionBoundary,
        ]], $generalBoundary)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1000, mb_strlen(EvaluationScore::query()->whereNotNull('comment')->sole()->comment));
        $this->assertSame(2000, mb_strlen(EvaluationRevision::query()->sole()->general_comment));

        $xss = "<script>alert(\"M6-XSS\")</script>\nLínea Unicode: evaluación ágil.";
        $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => 1,
            'general_comment' => $xss,
            'criteria' => [[
                'code' => 'pertinence', 'score' => '5.0', 'comment' => $xss,
            ]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($xss, EvaluationRevision::query()->sole()->general_comment);
        $html = $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert("M6-XSS")</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;M6-XSS&quot;)&lt;/script&gt;', $html);
    }

    public function test_authorization_role_profile_package_and_idor_matrix_is_fail_closed(): void
    {
        [$admin, $primaries] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $otherJudge = $primaries->get(1);
        $assignment = $this->assignmentFor($judge);

        $this->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();
        foreach ([$this->participant(), $this->reviewer(), $admin] as $forbidden) {
            $this->actingAs($forbidden)->post(route('judge.assignments.evaluation.store', $assignment))->assertForbidden();
        }
        $this->actingAs($otherJudge)->post(route('judge.assignments.evaluation.store', $assignment))->assertForbidden();
        $this->actingAs($judge)->post('/juez/asignaciones/01J00000000000000000000000/evaluacion')->assertNotFound();

        $judge->judgeProfile->forceFill(['status' => JudgeProfileStatus::PendingSetup->value])->save();
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertRedirect(route('judge.status'));
        $judge->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended->value])->save();
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertRedirect(route('judge.status'));
        $judge->judgeProfile->forceFill(['status' => JudgeProfileStatus::Active->value])->save();

        $roleless = User::factory()->create();
        $this->actingAs($roleless)->post(route('judge.assignments.evaluation.store', $assignment))->assertForbidden();
        $multiRole = User::factory()->create();
        $multiRole->assignRole(['judge', 'participant']);
        $this->actingAs($multiRole)->post(route('judge.assignments.evaluation.store', $assignment))->assertForbidden();
        $unassignedJudge = $this->createActiveEvaluationJudge($admin, JudgeAssignmentRole::Substitute, 7);
        $this->actingAs($unassignedJudge)->post(route('judge.assignments.evaluation.store', $assignment))->assertForbidden();

        $cancelledJudge = $primaries->get(2);
        $cancelledAssignment = $this->assignmentFor($cancelledJudge);
        DB::table('judge_assignments')->where('id', $cancelledAssignment->id)->update([
            'status' => JudgeAssignmentStatus::Cancelled->value,
            'current_slot' => null,
            'cancelled_by_user_id' => $admin->id,
            'cancellation_reason' => 'Cancelación sintética para matriz negativa M6.',
            'cancelled_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
        $this->actingAs($cancelledJudge)->post(route('judge.assignments.evaluation.store', $cancelledAssignment))->assertForbidden();

        $voidedJudge = $primaries->get(3);
        $voidedAssignment = $this->assignmentFor($voidedJudge);
        DB::table('judge_assignments')->where('id', $voidedAssignment->id)->update([
            'status' => JudgeAssignmentStatus::Voided->value,
            'current_slot' => null,
            'voided_by_user_id' => $admin->id,
            'void_reason' => 'Anulación sintética para matriz negativa M6.',
            'voided_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
        $this->actingAs($voidedJudge)->post(route('judge.assignments.evaluation.store', $voidedAssignment))->assertForbidden();

        DB::table('blind_review_packages')->update([
            'status' => BlindReviewPackageStatus::Draft->value,
            'activated_by_user_id' => null,
            'activation_reason' => null,
            'activated_at' => null,
        ]);
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertSessionHasErrors('evaluation');
        $this->assertDatabaseCount('evaluations', 0);
        DB::table('blind_review_packages')->update([
            'status' => BlindReviewPackageStatus::Invalidated->value,
            'invalidated_by_user_id' => $admin->id,
            'invalidation_reason' => 'Invalidación sintética para matriz negativa M6.',
            'invalidated_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertSessionHasErrors('evaluation');
        DB::table('blind_review_packages')->delete();
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertSessionHasErrors('evaluation');
        $this->assertDatabaseCount('evaluations', 0);

        foreach (['participant', 'reviewer', 'admin'] as $role) {
            $this->assertFalse(Role::findByName($role)->hasPermissionTo('manage own evaluation drafts'));
        }
        $this->assertTrue(Role::findByName('judge')->hasPermissionTo('manage own evaluation drafts'));
    }

    public function test_deadline_second_is_inclusive_for_start_and_save_and_next_second_closes(): void
    {
        [, $primaries] = $this->createEvaluationScenario();
        $beforeJudge = $primaries->get(0);
        $exactJudge = $primaries->get(1);
        $afterJudge = $primaries->get(2);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 23:59:58', 'America/Hermosillo'));
        $beforeAssignment = $this->assignmentFor($beforeJudge);
        $this->actingAs($beforeJudge)->post(route('judge.assignments.evaluation.store', $beforeAssignment))
            ->assertRedirect()->assertSessionHasNoErrors();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 23:59:59', 'America/Hermosillo'));
        $exactAssignment = $this->assignmentFor($exactJudge);
        $this->actingAs($exactJudge)->post(route('judge.assignments.evaluation.store', $exactAssignment))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->save($exactJudge, $exactAssignment, 0, [], 'Segundo exacto inclusivo.')
            ->assertRedirect()->assertSessionHasNoErrors();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-28 00:00:00', 'America/Hermosillo'));
        $afterAssignment = $this->assignmentFor($afterJudge);
        $this->actingAs($afterJudge)->post(route('judge.assignments.evaluation.store', $afterAssignment))
            ->assertSessionHasErrors('evaluation');
        $this->save($exactJudge, $exactAssignment, 1, [], 'No debe persistir.')
            ->assertSessionHasErrors('evaluation');
        $this->assertDatabaseCount('evaluations', 2);
        $this->assertSame('Segundo exacto inclusivo.', Evaluation::query()
            ->where('judge_assignment_id', $exactAssignment->id)->firstOrFail()->currentRevision->general_comment);
    }

    public function test_mutation_throttle_is_enforced_without_creating_duplicates(): void
    {
        [, $primaries] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $assignment = $this->assignmentFor($judge);

        foreach (range(1, 10) as $_requestNumber) {
            $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
                ->assertRedirect();
        }
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertTooManyRequests();
        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseCount('evaluation_revisions', 1);
        $this->assertDatabaseCount('evaluation_scores', 5);
    }

    public function test_optimistic_lock_deadline_conflict_and_replacement_preserve_independent_evidence(): void
    {
        [$admin, $primaries, $substitutes] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $assignment = $this->assignmentFor($judge);
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();

        $firstPayload = [[
            'code' => 'pertinence', 'score' => '5', 'comment' => 'Guardado de la primera pestaña.',
        ]];
        $this->save($judge, $assignment, 0, $firstPayload, 'Comentario de la primera pestaña.')
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->save($judge, $assignment, 0, [[
            'code' => 'pertinence', 'score' => '9', 'comment' => 'No debe sobrescribir.',
        ]], 'No debe sobrescribir.')->assertStatus(409)->assertSee('No sobrescribimos cambios más recientes');
        $revision = EvaluationRevision::query()->sole();
        $this->assertSame('Comentario de la primera pestaña.', $revision->general_comment);
        $this->assertDatabaseHas('evaluation_scores', ['score' => '5.0000', 'comment' => 'Guardado de la primera pestaña.']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 23:59:59', 'America/Hermosillo'));
        $this->save($judge, $assignment, 1, [], 'Guardado en el segundo exacto inclusivo.')
            ->assertRedirect()->assertSessionHasNoErrors();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-28 00:00:00', 'America/Hermosillo'));
        $this->save($judge, $assignment, 2, [], 'Guardado un segundo después, debe fallar.')
            ->assertSessionHasErrors('evaluation');
        $this->assertSame(2, Evaluation::query()->sole()->lock_version);
        $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()->assertSee('sólo para lectura')->assertDontSee('Guardar borrador');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-18 12:00:00', 'America/Hermosillo'));
        $conflict = app(DeclareJudgeConflict::class)->execute(
            $assignment,
            $judge,
            JudgeConflictType::ParticipationInSubmission,
            null,
        );
        $this->assertSame(JudgeAssignmentStatus::ConflictDeclared, $assignment->fresh()->status);
        $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()->assertDontSee('Comentario de la primera pestaña.')->assertDontSee('Guardar borrador');
        $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => 2, 'general_comment' => null, 'criteria' => [],
        ])->assertForbidden();
        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseCount('evaluation_scores', 5);

        $replacementJudge = $substitutes->first();
        $replacement = app(ResolveJudgeConflict::class)->execute(
            $conflict,
            $admin,
            $replacementJudge->judgeProfile->public_id,
            'Reasignación sintética para comprobar aislamiento M6.',
        );
        $this->actingAs($replacementJudge)->post(route('judge.assignments.evaluation.store', $replacement))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('evaluations', 2);
        $this->assertDatabaseCount('evaluation_revisions', 2);
        $this->assertDatabaseCount('evaluation_scores', 10);
        $replacementEvaluation = Evaluation::query()->where('judge_assignment_id', $replacement->id)
            ->with('currentRevision.scores')->firstOrFail();
        $this->assertNull($replacementEvaluation->currentRevision->general_comment);
        $this->assertTrue($replacementEvaluation->currentRevision->scores->every(fn ($score): bool => $score->score === null));

        $replacementConflict = app(DeclareJudgeConflict::class)->execute(
            $replacement,
            $replacementJudge,
            JudgeConflictType::ProfessionalOrEconomicRelationship,
            null,
        );
        $secondReplacement = app(ResolveJudgeConflict::class)->execute(
            $replacementConflict,
            $admin,
            $substitutes->get(1)->judgeProfile->public_id,
            'Reemplazo explícito del reemplazo en conflicto para M6A.',
        );
        $this->assertSame($replacement->id, $secondReplacement->replaces_assignment_id);
        $this->assertSame(JudgeAssignmentStatus::Active, $secondReplacement->status);
        $this->assertDatabaseMissing('evaluations', ['judge_assignment_id' => $secondReplacement->id]);
        $this->assertDatabaseCount('evaluations', 2);
        $this->assertDatabaseCount('evaluation_scores', 10);
        $this->assertDatabaseCount('judge_assignments', 6);
        $this->actingAs($replacementJudge)->get(route('judge.assignments.show', $replacement))
            ->assertOk()->assertDontSee('Guardar borrador');
    }

    public function test_evaluation_audit_is_redacted_and_m5_package_never_changes(): void
    {
        [, $primaries, , , , $package] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $assignment = $this->assignmentFor($judge);
        $packageBaseline = $package->only(['payload_sha256', 'payload', 'updated_at']);

        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();
        $this->save($judge, $assignment, 0, $this->criteriaPayload([
            'pertinence' => '7.5', 'clarity' => '8', 'feasibility' => '6.5', 'impact' => '9', 'coherence' => '5.5',
        ], 'COMENTARIO-CRITERIO-SECRETO'), 'COMENTARIO-GENERAL-SECRETO')->assertRedirect();

        $audit = AuditLog::query()->where('action', 'like', 'evaluation.%')->get();
        $serialized = $audit->toJson();
        foreach (['75.2500', 'COMENTARIO-CRITERIO-SECRETO', 'COMENTARIO-GENERAL-SECRETO', 'score', 'total_raw', 'calculated_component', '@example.test'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serialized);
        }
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.draft_opened']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.draft_saved']);
        $this->assertSame($packageBaseline['payload_sha256'], $package->fresh()->payload_sha256);
        $this->assertSame($packageBaseline['payload'], $package->fresh()->payload);
        $this->assertTrue($packageBaseline['updated_at']->equalTo($package->fresh()->updated_at));
    }

    private function assignmentFor(User $judge): JudgeAssignment
    {
        return JudgeAssignment::query()
            ->where('judge_profile_id', $judge->judgeProfile->id)
            ->where('status', JudgeAssignmentStatus::Active)
            ->firstOrFail();
    }

    private function save(User $judge, JudgeAssignment $assignment, int $lockVersion, array $criteria, ?string $generalComment = null)
    {
        return $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => $lockVersion,
            'general_comment' => $generalComment,
            'criteria' => $criteria,
        ]);
    }

    /** @param array<string,string|null> $scores */
    private function criteriaPayload(array $scores, ?string $comment = null): array
    {
        return collect(['pertinence', 'clarity', 'feasibility', 'impact', 'coherence'])
            ->map(fn (string $code): array => [
                'code' => $code,
                'score' => $scores[$code] ?? null,
                'comment' => $comment,
            ])->all();
    }
}
