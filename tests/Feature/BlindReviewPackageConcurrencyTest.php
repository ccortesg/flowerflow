<?php

namespace Tests\Feature;

use App\Actions\Assignments\ActivateSubmissionCoverage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Actions\Rubrics\ActivateRubricVersion;
use App\Enums\BlindReviewPackageStatus;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\BlindReviewPackage;
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

class BlindReviewPackageConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        foreach (['blind_review_package_files', 'blind_review_packages', 'judge_conflicts', 'judge_assignments', 'rubric_criteria', 'rubric_versions', 'judge_profiles'] as $table) {
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

    public function test_two_concurrent_activations_converge_on_one_active_package(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }

        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
        ]);
        $this->seedFlowerFlow();
        $admin = $this->admin([
            'email' => 'admin-package-concurrency@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
        foreach (range(1, 6) as $number) {
            $role = $number >= 5 ? JudgeAssignmentRole::Substitute : JudgeAssignmentRole::Primary;
            $user = User::factory()->create(['email' => "judge-package-concurrency-{$number}@example.test"]);
            $user->assignRole('judge');
            $profile = new JudgeProfile;
            $profile->forceFill([
                'user_id' => $user->id,
                'assignment_role' => $role->value,
                'status' => JudgeProfileStatus::Active->value,
                'max_active_assignments' => null,
                'created_by_user_id' => $admin->id,
                'password_initialized_at' => now('UTC'),
                'activated_at' => now('UTC'),
            ])->save();
        }
        $rubric = RubricVersion::query()->where('version', 1)->firstOrFail();
        app(ActivateRubricVersion::class)->execute($rubric, $admin, 'Activación sintética para concurrencia M5.');
        [, $submission, $review] = $this->submittedReview();
        $review->update([
            'status' => EligibilityReviewStatus::Admitted,
            'resolved_at' => now('UTC'),
            'participant_reason' => 'Admitida para concurrencia M5.',
        ]);
        $version = $submission->versions()->firstOrFail();
        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode([
            'schema_version' => 1,
            'category' => ['slug' => $submission->category->slug, 'name' => $submission->category->name],
            'submission' => [
                'participation_type' => 'individual',
                'title' => 'Proyecto sintético concurrente',
                'summary' => 'Resumen sintético concurrente.',
                'description_html' => '<p>Descripción sintética concurrente.</p>',
                'description_text' => 'Descripción sintética concurrente.',
            ],
            'external_links' => [],
            'files' => [],
        ])]);
        app(ActivateSubmissionCoverage::class)->execute($submission, $admin, 'Cobertura sintética para concurrencia de paquete M5.');
        app(GenerateBlindReviewPackageDraft::class)->execute($submission, $admin, 'Generación sintética previa a la carrera de activación M5.');

        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-package-concurrency-');
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
                        $response = $this->actingAs(User::query()->findOrFail($admin->id))
                            ->post(route('panel.blind-review-packages.activate', Submission::query()->findOrFail($submission->id)), [
                                'reason' => "Activación concurrente sintética número {$requestNumber} del paquete.",
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

        $this->assertSame(1, BlindReviewPackage::query()->count());
        $this->assertSame(1, BlindReviewPackage::query()->where('status', BlindReviewPackageStatus::Active)->count());
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'blind_review_package.activated')->count());
    }
}
