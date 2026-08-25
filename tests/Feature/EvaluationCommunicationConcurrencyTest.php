<?php

namespace Tests\Feature;

use App\Actions\Assignments\DeclareJudgeConflict;
use App\Enums\CommunicationType;
use App\Enums\JudgeConflictType;
use App\Events\JudgeConflictDeclared;
use App\Models\CommunicationDelivery;
use App\Models\JudgeAssignment;
use App\Services\EvaluationCommunicationDispatcher;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;
use Throwable;

class EvaluationCommunicationConcurrencyTest extends TestCase
{
    use CreatesEvaluationScenario;
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        if (Schema::hasTable('communication_delivery_attempts')) {
            DB::table('communication_delivery_attempts')->delete();
        }
        if (Schema::hasTable('communication_deliveries')) {
            DB::table('communication_deliveries')->delete();
        }
        if (Schema::hasTable('jobs')) {
            DB::table('jobs')->delete();
        }
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->delete();
        }
        foreach (['judge_conflicts', 'judge_assignments', 'blind_review_package_files', 'blind_review_packages', 'rubric_criteria', 'rubric_versions', 'judge_profiles'] as $table) {
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

    public function test_two_concurrent_listeners_converge_on_one_delivery_attempt_and_job(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.flags.communication_ledger' => false,
            'flowerflow.flags.evaluation_notifications' => true,
            'flowerflow.mail.queue_connection' => 'database',
            'flowerflow.mail.queue' => 'default',
            'flowerflow.timezone' => 'America/Hermosillo',
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
        ]);
        $this->seedFlowerFlow();
        [, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->firstOrFail();
        $conflict = app(DeclareJudgeConflict::class)->execute(
            $assignment,
            $judge,
            JudgeConflictType::ParticipationInSubmission,
            null,
        );

        config(['flowerflow.flags.communication_ledger' => true]);
        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-m8-communication-concurrency-');
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
                        app(EvaluationCommunicationDispatcher::class)->conflictDeclared(
                            new JudgeConflictDeclared($conflict->id, $assignment->id, $judge->id),
                        );
                        DB::disconnect();
                        exit(0);
                    } catch (Throwable) {
                        DB::disconnect();
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

        $this->assertDatabaseCount('communication_deliveries', 1);
        $this->assertDatabaseCount('communication_delivery_attempts', 1);
        $this->assertSame(1, DB::table('jobs')->where('queue', 'default')->count());
        $this->assertSame(CommunicationType::JudgeConflictDeclared, CommunicationDelivery::query()->sole()->notification_type);
    }
}
