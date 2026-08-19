<?php

namespace Tests\Feature;

use App\Actions\Rubrics\CreateRubricDraft;
use App\Enums\RubricVersionStatus;
use App\Models\Competition;
use App\Models\RubricVersion;
use App\Models\User;
use App\Services\EvaluationRubricContract;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class RubricActivationConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        if (Schema::hasTable('rubric_criteria')) {
            DB::table('rubric_criteria')->delete();
        }
        if (Schema::hasTable('rubric_versions')) {
            DB::table('rubric_versions')->delete();
        }

        parent::tearDown();
    }

    public function test_two_concurrent_activations_leave_exactly_one_active_version(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }

        config(['flowerflow.flags.panel' => true]);
        $this->seedFlowerFlow();
        $admin = $this->admin([
            'email' => 'admin-rubric-concurrency@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
        $competition = Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail();
        $contract = app(EvaluationRubricContract::class);
        $targets = collect([2, 3])->map(fn (int $version) => app(CreateRubricDraft::class)->execute(
            $admin,
            $competition,
            $version,
            "Rúbrica concurrente v{$version}",
            $contract->versionAttributes(),
            $contract->criteria(),
        ));
        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-rubric-concurrency-');
        $this->assertNotFalse($barrier);
        unlink($barrier);
        $children = [];
        DB::disconnect();

        try {
            foreach ($targets as $target) {
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
                        $childRubric = RubricVersion::query()->findOrFail($target->id);
                        $response = $this->actingAs($childAdmin)->post(route('panel.rubrics.activate', $childRubric), [
                            'reason' => 'Activación concurrente sintética suficientemente justificada.',
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

        $this->assertSame(1, RubricVersion::query()->where('status', RubricVersionStatus::Active)->count());
        $this->assertSame(1, RubricVersion::query()->where('active_slot', 1)->count());
        $this->assertSame(1, RubricVersion::query()->whereIn('id', $targets->pluck('id'))->where('status', RubricVersionStatus::Superseded)->count());
        $this->assertSame(2, DB::table('audit_logs')->where('action', 'rubric.activated')->count());
    }
}
