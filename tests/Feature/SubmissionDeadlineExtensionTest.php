<?php

namespace Tests\Feature;

use App\Models\Competition;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SubmissionDeadlineExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_and_seeded_competition_share_the_extended_deadline(): void
    {
        $this->seedFlowerFlow();

        $competition = Competition::query()
            ->where('slug', 'hermosillo-florece-2026')
            ->firstOrFail();
        $configured = CarbonImmutable::createFromFormat(
            'Y-m-d H:i:s',
            config('flowerflow.submissions_close_at'),
            config('flowerflow.timezone')
        );

        $this->assertSame('2026-08-23 23:59:59', $configured->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-24 06:59:59', $configured->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('America/Hermosillo', $competition->source_timezone);
        $this->assertTrue($competition->closes_at->equalTo($configured));
    }

    public function test_deadline_data_migration_is_idempotent_reversible_and_fail_closed(): void
    {
        $this->seedFlowerFlow();
        $competition = Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail();
        $competition->update(['closes_at' => '2026-08-16 06:59:59']);

        $migration = require database_path('migrations/2026_08_17_120000_extend_hermosillo_florece_2026_submission_deadline.php');
        $migration->up();
        $migration->up();

        $this->assertSame('2026-08-24 06:59:59', $competition->fresh()->closes_at->utc()->format('Y-m-d H:i:s'));

        $migration->down();
        $this->assertSame('2026-08-16 06:59:59', $competition->fresh()->closes_at->utc()->format('Y-m-d H:i:s'));

        $competition->update(['closes_at' => '2026-08-20 06:59:59']);

        try {
            $migration->up();
            $this->fail('La migración debió rechazar una fecha inesperada.');
        } catch (RuntimeException) {
            $this->assertSame('2026-08-20 06:59:59', $competition->fresh()->closes_at->utc()->format('Y-m-d H:i:s'));
        }
    }
}
