<?php

namespace Tests\Feature;

use App\Actions\Judges\CreateJudgeAccount;
use App\Enums\JudgeAssignmentRole;
use App\Models\JudgeSetupLink;
use App\Notifications\JudgeAccountSetupNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Throwable;

class JudgeSetupLinkConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->where('action', 'like', 'judge.setup_link.%')->delete();
        }
        if (Schema::hasTable('judge_setup_links')) {
            DB::table('judge_setup_links')->delete();
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

    public function test_two_concurrent_posts_consume_the_purpose_bound_link_once(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('La extensión pcntl es necesaria para la prueba de concurrencia MySQL.');
        }

        Notification::fake();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.flags.communication_ledger' => false,
            'flowerflow.judge_notifications.account_setup_enabled' => true,
        ]);
        $this->seedFlowerFlow();
        $admin = $this->admin(['email' => 'admin-judge-setup-concurrency@example.test']);
        $profile = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Concurrencia Enlace',
            'judge-setup-concurrency@example.test',
            JudgeAssignmentRole::Primary,
            true,
        );
        $link = JudgeSetupLink::query()->sole();
        $notification = Notification::sent($profile->user, JudgeAccountSetupNotification::class)->sole();
        $url = URL::temporarySignedRoute('judge.setup.show', $link->expires_at, [
            'setupLink' => $link,
            'token' => $notification->token,
        ]);

        $barrier = tempnam(sys_get_temp_dir(), 'flowerflow-judge-setup-concurrency-');
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
                        $response = $this->post($url, [
                            'password' => 'JudgeConcurrent1!',
                            'password_confirmation' => 'JudgeConcurrent1!',
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

        $this->assertNotNull($profile->user->fresh()->email_verified_at);
        $this->assertNotNull($link->fresh()->consumed_at);
        $this->assertNull($link->fresh()->active_slot);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'judge.setup_link.consumed')->count());
    }
}
