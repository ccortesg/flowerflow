<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class SubmissionReminderConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->where('action', 'like', 'submission_reminder.%')->delete();
        }
        if (Schema::hasTable('submission_reminders')) {
            DB::table('submission_reminders')->delete();
        }
        if (Schema::hasTable('submission_reminder_batches')) {
            DB::table('submission_reminder_batches')->delete();
        }

        parent::tearDown();
    }

    public function test_two_concurrent_individual_requests_create_one_reminder_without_partial_rows(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }

        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.submissions' => true,
            'flowerflow.flags.submission_reminders' => true,
            'flowerflow.mail.queue_connection' => 'database',
            'flowerflow.mail.queue' => 'default',
        ]);
        $this->seedFlowerFlow();
        $owner = $this->participant(['email' => 'reminder-concurrency-owner@example.test']);
        $category = Category::query()->firstOrFail();
        $submission = Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $owner->id,
            'participation_type' => 'individual',
            'title' => 'Propuesta sintética concurrente',
            'summary' => 'Resumen sintético concurrente.',
            'description_html' => '<p>Descripción sintética concurrente.</p>',
            'description_text' => 'Descripción sintética concurrente.',
            'status' => 'draft',
        ]);
        $admin = $this->admin(['email' => 'reminder-concurrency-admin@example.test']);

        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-reminder-concurrency-');
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
                        $response = $this->actingAs(User::query()->findOrFail($admin->id))
                            ->post(route(
                                'panel.submissions.reminders.submissions.store',
                                Submission::query()->findOrFail($submission->id),
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

        $this->assertDatabaseCount('submission_reminder_batches', 2);
        $this->assertDatabaseCount('submission_reminders', 1);
        $this->assertSame(1, DB::table('submission_reminder_batches')->where('queued_count', 1)->count());
        $this->assertSame(1, DB::table('submission_reminder_batches')->where('skipped_count', 1)->count());
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'submission_reminder.queued')->count());
        $this->assertSame(1, DB::table('jobs')->where('queue', 'default')->count());
    }
}
