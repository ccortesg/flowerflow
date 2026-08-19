<?php

namespace Tests\Feature;

use App\Actions\Rubrics\ActivateRubricVersion;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class JudgeAssignmentConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        if (Schema::hasTable('judge_conflicts')) {
            DB::table('judge_conflicts')->delete();
        }
        if (Schema::hasTable('judge_assignments')) {
            DB::table('judge_assignments')->delete();
        }
        if (Schema::hasTable('rubric_criteria')) {
            DB::table('rubric_criteria')->delete();
        }
        if (Schema::hasTable('rubric_versions')) {
            DB::table('rubric_versions')->delete();
        }
        if (Schema::hasTable('judge_profiles')) {
            DB::table('judge_profiles')->delete();
        }
        if (Schema::hasTable('roles') && Schema::hasTable(config('permission.table_names.model_has_roles'))) {
            $judgeRoleId = DB::table('roles')->where('name', 'judge')->value('id');
            if ($judgeRoleId) {
                DB::table(config('permission.table_names.model_has_roles'))->where('role_id', $judgeRoleId)->delete();
            }
        }

        parent::tearDown();
    }

    public function test_two_concurrent_coverage_requests_leave_exactly_four_assignments(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }

        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
        ]);
        $this->seedFlowerFlow();
        $admin = $this->admin([
            'email' => 'admin-assignment-concurrency@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
        foreach (range(1, 6) as $number) {
            $role = $number >= 5 ? JudgeAssignmentRole::Substitute : JudgeAssignmentRole::Primary;
            $user = User::factory()->create(['email' => "judge-assignment-concurrency-{$number}@example.test"]);
            $user->assignRole('judge');
            $profile = new JudgeProfile;
            $profile->forceFill([
                'user_id' => $user->id,
                'assignment_role' => $role->value,
                'status' => JudgeProfileStatus::Active->value,
                'max_active_assignments' => $role->maxActiveAssignments(),
                'created_by_user_id' => $admin->id,
                'password_initialized_at' => now('UTC'),
                'activated_at' => now('UTC'),
            ])->save();
        }
        $rubric = RubricVersion::query()->where('version', 1)->firstOrFail();
        app(ActivateRubricVersion::class)->execute($rubric, $admin, 'Activación sintética para concurrencia de asignaciones.');
        [, $submission, $review] = $this->submittedReview();
        $review->update([
            'status' => EligibilityReviewStatus::Admitted,
            'resolved_at' => now('UTC'),
            'participant_reason' => 'Admitida para concurrencia M4.',
        ]);

        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-assignment-concurrency-');
        $this->assertNotFalse($barrier);
        unlink($barrier);
        $children = [];
        DB::disconnect();

        try {
            foreach (range(1, 2) as $requestNumber) {
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
                        $childAdmin = User::query()->findOrFail($admin->id);
                        $childSubmission = Submission::query()->findOrFail($submission->id);
                        $response = $this->actingAs($childAdmin)->post(route('panel.assignments.activate', $childSubmission), [
                            'reason' => "Cobertura concurrente sintética número {$requestNumber} aprobada.",
                            'current_password' => 'AdminPass1!',
                        ]);
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

        $this->assertSame(4, JudgeAssignment::query()->count());
        $this->assertSame(4, JudgeAssignment::query()->distinct('judge_profile_id')->count('judge_profile_id'));
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'assignment.coverage_created')->count());
    }
}
