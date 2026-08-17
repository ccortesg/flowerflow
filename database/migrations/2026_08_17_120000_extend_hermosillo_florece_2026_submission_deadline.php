<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COMPETITION_SLUG = 'hermosillo-florece-2026';

    private const PREVIOUS_CLOSE_AT_UTC = '2026-08-16 06:59:59';

    private const EXTENDED_CLOSE_AT_UTC = '2026-08-24 06:59:59';

    public function up(): void
    {
        $this->replaceExpectedDeadline(self::PREVIOUS_CLOSE_AT_UTC, self::EXTENDED_CLOSE_AT_UTC, true);
    }

    public function down(): void
    {
        $this->replaceExpectedDeadline(self::EXTENDED_CLOSE_AT_UTC, self::PREVIOUS_CLOSE_AT_UTC, false);
    }

    private function replaceExpectedDeadline(string $expected, string $replacement, bool $failOnUnexpected): void
    {
        DB::transaction(function () use ($expected, $replacement, $failOnUnexpected): void {
            $competition = DB::table('competitions')
                ->where('slug', self::COMPETITION_SLUG)
                ->lockForUpdate()
                ->first(['id', 'closes_at']);

            if (! $competition) {
                return;
            }

            $current = CarbonImmutable::parse($competition->closes_at, 'UTC')->utc()->format('Y-m-d H:i:s');

            if ($current === $replacement) {
                return;
            }

            if ($current !== $expected) {
                if ($failOnUnexpected) {
                    throw new RuntimeException('La convocatoria tiene un cierre distinto al esperado; no se sobrescribió.');
                }

                return;
            }

            DB::table('competitions')
                ->where('id', $competition->id)
                ->update([
                    'closes_at' => $replacement,
                    'updated_at' => now('UTC'),
                ]);
        });
    }
};
