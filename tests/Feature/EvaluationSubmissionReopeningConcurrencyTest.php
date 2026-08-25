<?php

namespace Tests\Feature;

use App\Actions\Evaluations\ReopenEvaluation;
use App\Actions\Evaluations\SubmitEvaluation;
use App\Enums\EvaluationStatus;
use App\Exceptions\StaleEvaluationDraft;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Models\RubricCriterion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;
use Throwable;

class EvaluationSubmissionReopeningConcurrencyTest extends TestCase
{
    use CreatesEvaluationScenario;
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->where('action', 'like', 'evaluation.%')->delete();
        }
        if (Schema::hasTable('evaluations')) {
            DB::table('evaluations')->update(['current_revision_id' => null]);
        }
        if (Schema::hasTable('evaluation_revisions')) {
            DB::table('evaluation_revisions')->update(['source_revision_id' => null]);
        }
        foreach (['evaluation_reopenings', 'evaluation_scores', 'evaluation_revisions', 'evaluations', 'blind_review_package_files', 'blind_review_packages', 'judge_conflicts', 'judge_assignments', 'rubric_criteria', 'rubric_versions', 'judge_profiles'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
        if (Schema::hasTable('roles') && Schema::hasTable(config('permission.table_names.model_has_roles'))) {
            $judgeRoleId = DB::table('roles')->where('name', 'judge')->value('id');
            if ($judgeRoleId) {
                DB::table(config('permission.table_names.model_has_roles'))->where('role_id', $judgeRoleId)->delete();
            }
        }

        parent::tearDown();
    }

    public function test_concurrent_submit_and_reopen_each_create_one_transition_without_overwrite(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.flags.evaluation_finalization' => true,
            'flowerflow.timezone' => 'America/Hermosillo',
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
            'flowerflow.evaluation_reopen_close_at' => '2026-08-27 20:00:00',
        ]);
        $this->seedFlowerFlow();
        [$admin, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->firstOrFail();
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();
        $evaluation = Evaluation::query()->sole();
        $criteria = RubricCriterion::query()->where('rubric_version_id', $assignment->rubric_version_id)->orderBy('sort_order')->get()
            ->map(fn ($criterion) => ['code' => $criterion->code, 'score' => '8.0', 'comment' => null])->all();
        $this->actingAs($judge)->patch(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => 0, 'general_comment' => str_repeat('c', 120), 'criteria' => $criteria,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $evaluation = $evaluation->fresh();

        $this->forkTogether(function (int $_requestNumber) use ($judge, $assignment, $evaluation): void {
            app(SubmitEvaluation::class)->execute(
                JudgeAssignment::query()->findOrFail($assignment->id),
                User::query()->findOrFail($judge->id),
                ['lock_version' => $evaluation->lock_version, 'confirm_submission' => true],
            );
        });
        $evaluation = $evaluation->fresh('currentRevision');
        $this->assertSame(EvaluationStatus::Submitted, $evaluation->status);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'evaluation.submitted')->count());
        $this->assertDatabaseCount('evaluation_revisions', 1);

        $this->forkTogether(function (int $_requestNumber) use ($admin, $evaluation): void {
            app(ReopenEvaluation::class)->execute(
                Evaluation::query()->findOrFail($evaluation->id),
                User::query()->findOrFail($admin->id),
                $evaluation->lock_version,
                'Motivo sintético concurrente con longitud válida para reapertura.',
            );
        });
        $evaluation = $evaluation->fresh('currentRevision');
        $this->assertSame(EvaluationStatus::Reopened, $evaluation->status);
        $this->assertSame(2, $evaluation->currentRevision->revision_number);
        $this->assertDatabaseCount('evaluation_reopenings', 1);
        $this->assertDatabaseCount('evaluation_revisions', 2);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'evaluation.reopened')->count());

        $this->forkTogether(function (int $requestNumber) use ($admin, $judge, $assignment, $evaluation): void {
            $actor = $requestNumber === 1
                ? User::query()->findOrFail($judge->id)
                : User::query()->findOrFail($admin->id);
            app(SubmitEvaluation::class)->execute(
                JudgeAssignment::query()->findOrFail($assignment->id),
                $actor,
                $requestNumber === 1
                    ? ['lock_version' => $evaluation->lock_version, 'confirm_submission' => true]
                    : ['lock_version' => $evaluation->lock_version, 'current_password' => 'not-used-by-action', 'confirm_submission' => true, 'confirm_acting_on_behalf' => true],
            );
        });
        $evaluation = $evaluation->fresh('currentRevision');
        $this->assertSame(EvaluationStatus::Submitted, $evaluation->status);
        $this->assertContains($evaluation->currentRevision->submitted_by_user_id, [$judge->id, $admin->id]);
        $this->assertSame(2, DB::table('audit_logs')->where('action', 'evaluation.submitted')->count());
    }

    private function forkTogether(\Closure $operation): void
    {
        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-m7-concurrency-');
        $this->assertNotFalse($barrier);
        unlink($barrier);
        $children = [];
        DB::disconnect();

        try {
            foreach (range(1, 2) as $_requestNumber) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        $deadline = microtime(true) + 5;
                        while (! file_exists($barrier) && microtime(true) < $deadline) {
                            usleep(1000);
                        }
                        DB::purge();
                        DB::reconnect();
                        $operation($_requestNumber);
                        DB::disconnect();
                        exit(0);
                    } catch (StaleEvaluationDraft|ValidationException) {
                        DB::disconnect();
                        exit(0);
                    } catch (Throwable) {
                        exit(3);
                    }
                }
                $children[] = $pid;
            }
            touch($barrier);
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertTrue(pcntl_wifexited($status));
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
        } finally {
            if (file_exists($barrier)) {
                unlink($barrier);
            }
            DB::reconnect();
        }
    }
}
