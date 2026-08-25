<?php

namespace Tests\Feature;

use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationSubmissionMode;
use App\Events\EvaluationSubmitted;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\EvaluationReopening;
use App\Models\EvaluationRevision;
use App\Models\JudgeAssignment;
use App\Models\RubricCriterion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;

class EvaluationSubmissionReopeningTest extends TestCase
{
    use CreatesEvaluationScenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.flags.evaluation_finalization' => true,
            'flowerflow.timezone' => 'America/Hermosillo',
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
            'flowerflow.evaluation_reopen_close_at' => '2026-08-27 20:00:00',
        ]);
        $this->seedFlowerFlow();
    }

    public function test_judge_confirmation_requires_complete_scores_and_unicode_trimmed_comment_then_seals_revision_one(): void
    {
        Event::fake([EvaluationSubmitted::class]);
        [, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge);
        $evaluation = $this->openAndComplete($judge, $assignment, "\n".str_repeat('á', 99)." \t");

        $beforeGet = [Evaluation::count(), EvaluationRevision::count(), DB::table('evaluation_scores')->count(), EvaluationReopening::count()];
        $this->actingAs($judge)->get(route('judge.assignments.evaluation.confirm', $assignment))
            ->assertOk()->assertSee('El envío es inmutable')->assertDontSee('Motivo administrativo');
        $this->assertSame($beforeGet, [Evaluation::count(), EvaluationRevision::count(), DB::table('evaluation_scores')->count(), EvaluationReopening::count()]);
        $this->submitAsJudge($judge, $assignment, $evaluation->lock_version)
            ->assertSessionHasErrors('evaluation');
        $this->assertSame(EvaluationStatus::Draft, $evaluation->fresh()->status);

        $this->saveAsJudge($judge, $assignment, $evaluation->fresh()->lock_version, str_repeat('á', 2001))
            ->assertSessionHasErrors('general_comment');
        $this->saveAsJudge($judge, $assignment, $evaluation->fresh()->lock_version, str_repeat('á', 2000))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->submitAsJudge($judge, $assignment, $evaluation->lock_version)
            ->assertStatus(409)->assertSee('No sobrescribimos cambios más recientes');

        $this->saveAsJudge($judge, $assignment, $evaluation->fresh()->lock_version, str_repeat('á', 100))
            ->assertRedirect()->assertSessionHasNoErrors();
        $evaluation = $evaluation->fresh();
        $this->submitAsJudge($judge, $assignment, $evaluation->lock_version)
            ->assertRedirect(route('judge.assignments.evaluation.show', $assignment))->assertSessionHasNoErrors();

        $evaluation = $evaluation->fresh('currentRevision.scores');
        $this->assertSame(EvaluationStatus::Submitted, $evaluation->status);
        $this->assertSame(EvaluationRevisionStatus::Submitted, $evaluation->currentRevision->status);
        $this->assertSame(EvaluationSubmissionMode::Judge, $evaluation->currentRevision->submission_mode);
        $this->assertSame($judge->id, $evaluation->currentRevision->submitted_by_user_id);
        $this->assertSame($judge->judgeProfile->id, $evaluation->currentRevision->subject_judge_profile_id);
        $this->assertSame(1, $evaluation->currentRevision->revision_number);
        $this->assertSame(4, $evaluation->lock_version);
        Event::assertDispatchedTimes(EvaluationSubmitted::class, 1);
        $this->assertSame(1, AuditLog::query()->where('action', 'evaluation.submitted')->count());
        $this->actingAs($judge)->get(route('judge.dashboard'))
            ->assertOk()
            ->assertSee('Revisa y confirma el envío.')
            ->assertDontSee('El envío final de la evaluación todavía no está habilitado.');
        $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('La evaluación fue enviada. Puedes consultar la revisión vigente y su historial inmutable.')
            ->assertDontSee('Declarar conflicto');
        $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), $this->savePayload($evaluation->lock_version, str_repeat('b', 100), $assignment->rubric_version_id))
            ->assertSessionHasErrors('evaluation');
        $this->assertSame(str_repeat('á', 100), $evaluation->currentRevision->fresh()->general_comment);
        $this->assertDatabaseCount('communication_deliveries', 0);
    }

    public function test_admin_reopening_clones_exactly_preserves_source_and_records_real_actor_then_supports_revision_three(): void
    {
        [$admin, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge);
        $evaluation = $this->submitCompleteEvaluation($judge, $assignment);
        $packageBefore = (array) DB::table('blind_review_packages')->where('id', $evaluation->blind_review_package_id)
            ->first(['payload_sha256', 'updated_at']);
        $source = $evaluation->currentRevision()->with('scores')->firstOrFail();
        $sourceValues = $this->revisionValues($source);
        $sourceUpdatedAt = $source->updated_at->format('Y-m-d H:i:s.u');

        $this->actingAs($admin)->get(route('panel.evaluations.show', $evaluation))->assertOk();
        $this->actingAs($admin)->withSession($this->passwordConfirmed())->get(route('panel.evaluations.reopen', $evaluation))
            ->assertOk()->assertSee('Acción append-only');
        $this->reopenAsAdmin($admin, $evaluation, $evaluation->lock_version, 'Motivo administrativo sintético de reapertura número uno.')
            ->assertRedirect(route('panel.evaluations.show', $evaluation))->assertSessionHasNoErrors();

        $evaluation = $evaluation->fresh(['currentRevision.scores', 'revisions.scores', 'reopenings.reopenedBy']);
        $this->assertSame(EvaluationStatus::Reopened, $evaluation->status);
        $this->assertSame(2, $evaluation->currentRevision->revision_number);
        $this->assertSame($source->id, $evaluation->currentRevision->source_revision_id);
        $this->assertSame($sourceValues, $this->revisionValues($source->fresh('scores')));
        $this->assertSame($sourceUpdatedAt, $source->fresh()->updated_at->format('Y-m-d H:i:s.u'));
        $this->assertSame($sourceValues, $this->revisionValues($evaluation->currentRevision));
        $reopening = EvaluationReopening::query()->sole();
        $this->assertSame($admin->id, $reopening->reopened_by_user_id);
        $this->assertSame($judge->judgeProfile->id, $reopening->subject_judge_profile_id);

        $judgeHtml = $this->actingAs($judge)->get(route('judge.assignments.evaluation.show', $assignment))->assertOk()->getContent();
        $this->assertStringContainsString('La administración reabrió esta evaluación', $judgeHtml);
        $this->assertStringNotContainsString('Motivo administrativo sintético', $judgeHtml);
        $this->assertStringNotContainsString($admin->name, $judgeHtml);
        $this->actingAs($admin)->get(route('panel.evaluations.show', $evaluation))
            ->assertOk()->assertSee('Motivo administrativo sintético')->assertSee($admin->name);

        $this->saveAsAdmin($admin, $evaluation, $evaluation->lock_version, str_repeat('z', 120))
            ->assertRedirect()->assertSessionHasNoErrors();
        $evaluation = $evaluation->fresh();
        $this->submitAsAdmin($admin, $evaluation, $evaluation->lock_version)
            ->assertRedirect()->assertSessionHasNoErrors();
        $evaluation = $evaluation->fresh('currentRevision');
        $this->assertSame(EvaluationStatus::Submitted, $evaluation->status);
        $this->assertSame(EvaluationSubmissionMode::Administrative, $evaluation->currentRevision->submission_mode);
        $this->assertSame($admin->id, $evaluation->currentRevision->submitted_by_user_id);
        $this->assertSame($judge->judgeProfile->id, $evaluation->currentRevision->subject_judge_profile_id);

        $this->reopenAsAdmin($admin, $evaluation, $evaluation->lock_version, 'Segundo motivo administrativo sintético para crear revisión tres.')
            ->assertRedirect()->assertSessionHasNoErrors();
        $evaluation = $evaluation->fresh('currentRevision');
        $this->assertSame(3, $evaluation->currentRevision->revision_number);
        $this->assertDatabaseCount('evaluation_revisions', 3);
        $this->assertDatabaseCount('evaluation_reopenings', 2);
        $this->assertSame(2, EvaluationRevision::query()->where('status', 'submitted')->count());
        $this->assertSame($packageBefore, (array) DB::table('blind_review_packages')->where('id', $evaluation->blind_review_package_id)
            ->first(['payload_sha256', 'updated_at']));
        $m7Audit = AuditLog::query()->whereIn('action', [
            'evaluation.submitted', 'evaluation.reopened', 'evaluation.reopened_draft_saved_administratively',
        ])->get();
        $serializedAudit = $m7Audit->pluck('metadata')->toJson();
        foreach (['Motivo administrativo', str_repeat('z', 120), 'total_raw', 'score', 'comment', 'reason'] as $forbiddenAuditValue) {
            $this->assertStringNotContainsString($forbiddenAuditValue, $serializedAudit);
        }
    }

    public function test_exact_windows_stale_locks_hostile_payload_roles_and_conflicts_fail_closed(): void
    {
        [$admin, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge);
        $evaluation = $this->submitCompleteEvaluation($judge, $assignment);

        $beforeJudge = $judges->get(1);
        $beforeAssignment = $this->assignmentFor($beforeJudge);
        $beforeEvaluation = $this->submitCompleteEvaluation($beforeJudge, $beforeAssignment);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 19:59:59', 'America/Hermosillo'));
        $this->reopenAsAdmin($admin, $beforeEvaluation, $beforeEvaluation->lock_version, 'Reapertura un segundo antes del cierre administrativo.')
            ->assertRedirect()->assertSessionHasNoErrors();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 20:00:00', 'America/Hermosillo'));
        $this->reopenAsAdmin($admin, $evaluation, $evaluation->lock_version, 'Reapertura exactamente en el segundo inclusivo autorizado.')
            ->assertRedirect()->assertSessionHasNoErrors();
        $evaluation = $evaluation->fresh();
        $this->reopenAsAdmin($admin, $evaluation, $evaluation->lock_version, 'No puede reabrirse mientras existe una revisión pendiente.')
            ->assertForbidden();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 23:59:58', 'America/Hermosillo'));
        $this->submitAsJudge($beforeJudge, $beforeAssignment, $beforeEvaluation->fresh()->lock_version)
            ->assertRedirect()->assertSessionHasNoErrors();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 23:59:59', 'America/Hermosillo'));
        $this->saveAsJudge($judge, $assignment, $evaluation->lock_version, str_repeat('x', 110))
            ->assertRedirect()->assertSessionHasNoErrors();
        $evaluation = $evaluation->fresh();
        $this->submitAsJudge($judge, $assignment, $evaluation->lock_version)
            ->assertRedirect()->assertSessionHasNoErrors();
        $sealedLock = $evaluation->fresh()->lock_version;

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-28 00:00:00', 'America/Hermosillo'));
        $this->submitAsJudge($judge, $assignment, $sealedLock - 1)->assertSessionHasErrors('evaluation');
        $this->assertSame($sealedLock, $evaluation->fresh()->lock_version);

        $otherAdmin = $this->admin(['password' => bcrypt('AdminPass1!')]);
        Role::findByName('admin')->revokePermissionTo('reopen evaluations');
        $this->actingAs($otherAdmin)->withSession($this->passwordConfirmed())
            ->get(route('panel.evaluations.reopen', $evaluation))->assertForbidden();
        foreach ([$this->participant(), $this->reviewer(), $judge] as $forbidden) {
            $this->actingAs($forbidden)->get(route('panel.evaluations.index'))->assertForbidden();
        }

        $this->actingAs($judge)->post(route('judge.assignments.evaluation.submit', $assignment), [
            'lock_version' => $sealedLock, 'confirm_submission' => '1', 'total_raw' => '100.0000',
        ])->assertSessionHasErrors('evaluation');
        $this->actingAs($judge)->post(route('judge.assignments.conflicts.store', $assignment), [
            'type' => 'participation_in_submission',
        ])->assertSessionHasErrors('conflict');
    }

    public function test_reopen_window_second_after_and_configuration_drift_are_rejected_without_evidence(): void
    {
        [$admin, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge);
        $evaluation = $this->submitCompleteEvaluation($judge, $assignment);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 20:00:01', 'America/Hermosillo'));
        $this->reopenAsAdmin($admin, $evaluation, $evaluation->lock_version, 'Reapertura fuera de la ventana que debe ser rechazada.')
            ->assertSessionHasErrors('evaluation');
        $this->assertDatabaseCount('evaluation_reopenings', 0);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-18 12:00:00', 'America/Hermosillo'));
        config(['flowerflow.evaluation_reopen_close_at' => '2026-08-27 20:00:01']);
        $this->reopenAsAdmin($admin, $evaluation, $evaluation->lock_version, 'Configuración divergente que debe fallar de forma cerrada.')
            ->assertSessionHasErrors('evaluation');
        $this->assertDatabaseCount('evaluation_reopenings', 0);
    }

    private function assignmentFor(User $judge): JudgeAssignment
    {
        return JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->firstOrFail();
    }

    private function openAndComplete(User $judge, JudgeAssignment $assignment, string $comment): Evaluation
    {
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();
        $evaluation = Evaluation::query()->where('judge_assignment_id', $assignment->id)->firstOrFail();
        $this->saveAsJudge($judge, $assignment, $evaluation->lock_version, $comment)->assertRedirect()->assertSessionHasNoErrors();

        return $evaluation->fresh();
    }

    private function submitCompleteEvaluation(User $judge, JudgeAssignment $assignment): Evaluation
    {
        $evaluation = $this->openAndComplete($judge, $assignment, str_repeat('c', 120));
        $this->submitAsJudge($judge, $assignment, $evaluation->lock_version)->assertRedirect()->assertSessionHasNoErrors();

        return $evaluation->fresh('currentRevision.scores');
    }

    private function saveAsJudge(User $judge, JudgeAssignment $assignment, int $lockVersion, string $comment)
    {
        return $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), $this->savePayload($lockVersion, $comment, $assignment->rubric_version_id));
    }

    private function saveAsAdmin(User $admin, Evaluation $evaluation, int $lockVersion, string $comment)
    {
        return $this->actingAs($admin)->withSession($this->passwordConfirmed())->patch(
            route('panel.evaluations.draft.update', $evaluation),
            [...$this->savePayload($lockVersion, $comment, $evaluation->rubric_version_id), 'current_password' => 'AdminPass1!'],
        );
    }

    private function submitAsJudge(User $judge, JudgeAssignment $assignment, int $lockVersion)
    {
        return $this->actingAs($judge)->post(route('judge.assignments.evaluation.submit', $assignment), [
            'lock_version' => $lockVersion, 'confirm_submission' => '1',
        ]);
    }

    private function submitAsAdmin(User $admin, Evaluation $evaluation, int $lockVersion)
    {
        return $this->actingAs($admin)->withSession($this->passwordConfirmed())->post(route('panel.evaluations.submit', $evaluation), [
            'lock_version' => $lockVersion, 'current_password' => 'AdminPass1!',
            'confirm_submission' => '1', 'confirm_acting_on_behalf' => '1',
        ]);
    }

    private function reopenAsAdmin(User $admin, Evaluation $evaluation, int $lockVersion, string $reason)
    {
        return $this->actingAs($admin)->withSession($this->passwordConfirmed())->post(route('panel.evaluations.reopen.store', $evaluation), [
            'lock_version' => $lockVersion, 'reason' => $reason, 'current_password' => 'AdminPass1!', 'confirm_reopen' => '1',
        ]);
    }

    private function savePayload(int $lockVersion, string $comment, int $rubricVersionId): array
    {
        $criteria = RubricCriterion::query()->where('rubric_version_id', $rubricVersionId)->orderBy('sort_order')->get()
            ->map(fn ($criterion) => ['code' => $criterion->code, 'score' => '8.0', 'comment' => 'Comentario sintético.'])->all();

        return ['lock_version' => $lockVersion, 'general_comment' => $comment, 'criteria' => $criteria, 'intent' => 'save'];
    }

    private function revisionValues(EvaluationRevision $revision): array
    {
        return [
            'general_comment' => $revision->general_comment,
            'total_raw' => $revision->total_raw,
            'scores' => $revision->scores->map(fn ($score) => [
                $score->rubric_criterion_id, $score->score, $score->calculated_component, $score->comment,
            ])->values()->all(),
        ];
    }

    private function passwordConfirmed(): array
    {
        return ['auth.password_confirmed_at' => now()->timestamp];
    }
}
