<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('judge_profiles')) {
            throw new RuntimeException('Cannot apply unlimited judge capacity without judge_profiles.');
        }

        $invalid = DB::table('judge_profiles')
            ->where(function ($query): void {
                $query->where(function ($primary): void {
                    $primary->where('assignment_role', 'primary')
                        ->whereNotNull('max_active_assignments');
                })->orWhere(function ($substitute): void {
                    $substitute->where('assignment_role', 'substitute')
                        ->whereNotNull('max_active_assignments')
                        ->where('max_active_assignments', '<>', 10);
                });
            })
            ->exists();

        if ($invalid) {
            throw new RuntimeException('Cannot migrate judge capacity because existing profiles diverge from the historical NULL/10 contract.');
        }

        DB::statement('ALTER TABLE judge_profiles DROP CHECK judge_profiles_capacity_check');
        DB::table('judge_profiles')
            ->where('assignment_role', 'substitute')
            ->where('max_active_assignments', 10)
            ->update(['max_active_assignments' => null]);
        DB::statement('ALTER TABLE judge_profiles ADD CONSTRAINT judge_profiles_capacity_check CHECK (max_active_assignments IS NULL)');
    }

    public function down(): void
    {
        if (! Schema::hasTable('judge_profiles')) {
            return;
        }

        if (Schema::hasTable('judge_assignments')) {
            $overHistoricalLimit = DB::table('judge_profiles as profiles')
                ->join('judge_assignments as assignments', 'assignments.judge_profile_id', '=', 'profiles.id')
                ->select('profiles.id')
                ->where('profiles.assignment_role', 'substitute')
                ->where('assignments.type', 'replacement')
                ->whereIn('assignments.status', ['active', 'conflict_declared'])
                ->groupBy('profiles.id')
                ->havingRaw('COUNT(*) > 10')
                ->limit(1)
                ->first() !== null;

            if ($overHistoricalLimit) {
                throw new RuntimeException('Cannot restore the historical substitute limit while a substitute has more than ten current replacements.');
            }
        }

        DB::statement('ALTER TABLE judge_profiles DROP CHECK judge_profiles_capacity_check');
        DB::table('judge_profiles')
            ->where('assignment_role', 'substitute')
            ->update(['max_active_assignments' => 10]);
        DB::statement("ALTER TABLE judge_profiles ADD CONSTRAINT judge_profiles_capacity_check CHECK ((assignment_role = 'primary' AND max_active_assignments IS NULL) OR (assignment_role = 'substitute' AND max_active_assignments = 10))");
    }
};
