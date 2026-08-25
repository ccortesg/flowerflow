<?php

namespace Tests\Feature;

use App\Enums\RubricVersionStatus;
use App\Models\RubricVersion;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

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

    public function test_legal_v2_is_the_only_active_slot_and_database_rejects_a_second_one(): void
    {
        $this->seedFlowerFlow();
        $v1 = RubricVersion::query()->where('version', 1)->firstOrFail();
        $v2 = RubricVersion::query()->where('version', 2)->firstOrFail();
        $this->assertSame(RubricVersionStatus::Active, $v2->status);
        $this->assertSame(1, RubricVersion::query()->where('active_slot', 1)->count());

        try {
            DB::table('rubric_versions')->where('id', $v1->id)->update([
                'status' => 'active',
                'active_slot' => 1,
                'activated_at' => now('UTC'),
                'activation_source' => 'migration',
                'activation_reason' => 'Intento sintético de segunda activación concurrente.',
            ]);
            $this->fail('The database must reject a second active rubric slot.');
        } catch (QueryException) {
            $this->assertSame(RubricVersionStatus::Draft, $v1->fresh()->status);
            $this->assertSame(RubricVersionStatus::Active, $v2->fresh()->status);
        }
    }
}
