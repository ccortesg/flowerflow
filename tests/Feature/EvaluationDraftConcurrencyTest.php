<?php

namespace Tests\Feature;

use App\Enums\JudgeAssignmentStatus;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;
use Throwable;

class EvaluationDraftConcurrencyTest extends TestCase
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

        foreach ([
            'evaluation_scores',
            'evaluation_revisions',
            'evaluations',
            'blind_review_package_files',
            'blind_review_packages',
            'judge_conflicts',
            'judge_assignments',
            'rubric_criteria',
            'rubric_versions',
            'judge_profiles',
        ] as $table) {
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

    public function test_two_concurrent_explicit_starts_converge_on_one_complete_aggregate(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }

        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.timezone' => 'America/Hermosillo',
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
        ]);
        $this->seedFlowerFlow();
        [, $primaries] = $this->createEvaluationScenario();
        $judge = $primaries->first();
        $assignment = JudgeAssignment::query()
            ->where('judge_profile_id', $judge->judgeProfile->id)
            ->where('status', JudgeAssignmentStatus::Active)
            ->firstOrFail();

        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-evaluation-concurrency-');
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
                        $response = $this->actingAs(User::query()->findOrFail($judge->id))
                            ->post(route(
                                'judge.assignments.evaluation.store',
                                JudgeAssignment::query()->findOrFail($assignment->id),
                            ));
                        DB::disconnect();
                        exit($response->getStatusCode() >= 500 ? 2 : 0);
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

        $evaluation = Evaluation::query()->with('currentRevision.scores')->sole();
        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseCount('evaluation_revisions', 1);
        $this->assertDatabaseCount('evaluation_scores', 5);
        $this->assertCount(5, $evaluation->currentRevision->scores);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'evaluation.draft_opened')->count());
    }
}
