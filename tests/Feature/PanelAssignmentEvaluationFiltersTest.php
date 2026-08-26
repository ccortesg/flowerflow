<?php

namespace Tests\Feature;

use App\Actions\Evaluations\OpenEvaluationDraft;
use App\Actions\Evaluations\SaveEvaluationDraft;
use App\Actions\Evaluations\SubmitEvaluation;
use App\Enums\EligibilityReviewStatus;
use App\Models\Category;
use App\Models\EligibilityReview;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;

class PanelAssignmentEvaluationFiltersTest extends TestCase
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
            'flowerflow.flags.evaluation_export' => false,
            'flowerflow.flags.evaluation_notifications' => false,
        ]);
        $this->seedFlowerFlow();
    }

    public function test_assignment_filters_reuse_literal_reference_search_combine_category_and_preserve_query_string_without_mutation(): void
    {
        $admin = $this->admin();
        $categories = Category::query()->orderBy('sort_order')->take(2)->get();
        $this->assertCount(2, $categories);

        [, $percentSubmission, $percentReview] = $this->submittedReview();
        $percentSubmission->forceFill([
            'category_id' => $categories->get(0)->id,
            'folio' => 'HMO26-A%LITERAL',
        ])->save();
        $this->admit($percentReview);

        [, $plainSubmission, $plainReview] = $this->submittedReview();
        $plainSubmission->forceFill([
            'category_id' => $categories->get(1)->id,
            'folio' => 'HMO26-AXLITERAL',
        ])->save();
        $this->admit($plainReview);

        $before = $this->evaluationTableCounts();
        $literal = $this->actingAs($admin)->get(route('panel.assignments.index', [
            'folio' => '%LIT',
            'category' => $categories->get(0)->slug,
        ]));
        $literal->assertOk()
            ->assertSee('Filtros de asignaciones')
            ->assertSee($percentSubmission->public_id)
            ->assertDontSee($plainSubmission->public_id);
        $this->assertStringContainsString('folio=', $literal->viewData('submissions')->url(2));
        $this->assertStringContainsString('category=', $literal->viewData('submissions')->url(2));

        $this->actingAs($admin)->get(route('panel.assignments.index', [
            'folio' => substr($plainSubmission->public_id, 7, 10),
        ]))->assertOk()
            ->assertSee($plainSubmission->public_id)
            ->assertDontSee($percentSubmission->public_id);

        $this->actingAs($admin)->get(route('panel.assignments.index', [
            'folio' => str_repeat('x', 65),
        ]))->assertSessionHasErrors('folio');
        $this->assertSame($before, $this->evaluationTableCounts());
    }

    public function test_evaluation_filters_combine_reference_status_and_category_and_validate_status_without_mutation(): void
    {
        [$admin, $judges, , $submission] = $this->createEvaluationScenario();
        $draft = app(OpenEvaluationDraft::class)->execute(
            $this->assignmentFor($judges->get(0)),
            $judges->get(0),
        );
        $submitted = $this->completeAndSubmit(
            $judges->get(1),
            $this->assignmentFor($judges->get(1)),
        );
        $before = $this->evaluationTableCounts();

        $response = $this->actingAs($admin)->get(route('panel.evaluations.index', [
            'folio' => substr($submission->public_id, 5, 12),
            'status' => 'submitted',
            'category' => $submission->category->slug,
        ]));
        $response->assertOk()
            ->assertSee('Filtros de evaluaciones')
            ->assertSee($submission->folio)
            ->assertSee($submitted->public_id)
            ->assertDontSee($draft->public_id);
        $this->assertStringContainsString('folio=', $response->viewData('evaluations')->url(2));
        $this->assertStringContainsString('status=submitted', $response->viewData('evaluations')->url(2));
        $this->assertStringContainsString('category=', $response->viewData('evaluations')->url(2));

        $this->actingAs($admin)->get(route('panel.evaluations.index', [
            'status' => 'not-a-real-status',
        ]))->assertSessionHasErrors('status');
        $this->actingAs($admin)->get(route('panel.evaluations.index', [
            'category' => 'not-a-real-category',
        ]))->assertOk()->assertDontSee($submitted->public_id);
        $this->assertSame($before, $this->evaluationTableCounts());
    }

    private function admit(EligibilityReview $review): void
    {
        $review->update([
            'status' => EligibilityReviewStatus::Admitted,
            'resolved_at' => now('UTC'),
            'participant_reason' => 'Admisibilidad sintética aprobada para filtros.',
        ]);
    }

    private function assignmentFor(User $judge): JudgeAssignment
    {
        return JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->firstOrFail();
    }

    private function completeAndSubmit(User $judge, JudgeAssignment $assignment): Evaluation
    {
        $evaluation = app(OpenEvaluationDraft::class)->execute($assignment, $judge);
        $evaluation = app(SaveEvaluationDraft::class)->execute($assignment, $judge, [
            'lock_version' => $evaluation->lock_version,
            'general_comment' => 'Comentario general sintético '.str_repeat('x', 110),
            'criteria' => $assignment->rubricVersion->criteria()->orderBy('sort_order')->get()
                ->map(fn ($criterion): array => [
                    'code' => $criterion->code,
                    'score' => '8.0',
                    'comment' => 'Comentario sintético del rubro.',
                ])->all(),
        ]);
        app(SubmitEvaluation::class)->execute($assignment, $judge, [
            'lock_version' => $evaluation->lock_version,
            'confirm_submission' => 1,
        ]);

        return $evaluation->fresh();
    }

    /** @return array<int, int> */
    private function evaluationTableCounts(): array
    {
        return [
            DB::table('submissions')->count(),
            DB::table('judge_assignments')->count(),
            DB::table('evaluations')->count(),
            DB::table('evaluation_revisions')->count(),
            DB::table('evaluation_scores')->count(),
        ];
    }
}
