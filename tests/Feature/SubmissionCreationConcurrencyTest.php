<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

class SubmissionCreationConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_concurrent_attempts_cannot_exceed_four_submissions(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }

        config(['flowerflow.flags.submissions' => true]);
        $this->seedFlowerFlow();
        $competition = Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail();
        $categories = $competition->categories()->where('active', true)->orderBy('sort_order')->get();
        $user = $this->participant(['email' => 'concurrencia-limite@example.test']);

        foreach ($categories->take(3) as $category) {
            Submission::query()->create([
                'competition_id' => $competition->id,
                'category_id' => $category->id,
                'user_id' => $user->id,
                'participation_type' => 'individual',
                'title' => 'Propuesta previa '.$category->sort_order,
                'summary' => 'Resumen sintético.',
            ]);
        }

        $extraCategory = $competition->categories()->create([
            'slug' => 'categoria-concurrente-sintetica',
            'name' => 'Categoría concurrente sintética',
            'description' => 'Sólo existe durante esta prueba.',
            'sort_order' => 5,
            'active' => true,
        ]);
        $targets = collect([$categories->last(), $extraCategory]);
        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-concurrency-');
        $this->assertNotFalse($barrier);
        unlink($barrier);
        $children = [];

        DB::disconnect();

        try {
            foreach ($targets as $index => $category) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid, 'No fue posible crear el proceso de prueba concurrente.');

                if ($pid === 0) {
                    try {
                        $deadline = microtime(true) + 5;
                        while (! file_exists($barrier) && microtime(true) < $deadline) {
                            usleep(1000);
                        }

                        DB::purge();
                        DB::reconnect();
                        $childUser = User::query()->findOrFail($user->id);
                        $response = $this->actingAs($childUser)->post(route('submissions.store'), [
                            'wizard_step' => 1,
                            'wizard_action' => 'save',
                            'category_public_id' => $category->public_id,
                            'participation_type' => 'individual',
                            'title' => 'Intento concurrente '.($index + 1),
                            'summary' => 'Sólo uno de estos intentos puede persistirse.',
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

        $this->assertSame(4, Submission::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, Submission::query()
            ->where('user_id', $user->id)
            ->whereIn('category_id', $targets->pluck('id'))
            ->count());
    }
}
