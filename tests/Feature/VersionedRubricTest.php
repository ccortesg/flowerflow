<?php

namespace Tests\Feature;

use App\Enums\RubricVersionStatus;
use App\Models\RubricCriterion;
use App\Models\RubricVersion;
use App\Models\User;
use App\Services\EvaluationRubricContract;
use Database\Seeders\FlowerFlowSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VersionedRubricTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flowerflow.flags.panel' => true, 'flowerflow.flags.evaluation' => true]);
        $this->seedFlowerFlow();
    }

    public function test_canonical_seed_is_idempotent_and_produces_historical_v1_and_active_legal_v2(): void
    {
        $preserved = $this->participant(['email' => 'preserved-rubric@example.test']);
        $this->seed(FlowerFlowSeeder::class);
        $this->seed(FlowerFlowSeeder::class);

        $rubrics = RubricVersion::query()->with('criteria')->orderBy('version')->get();
        $this->assertCount(2, $rubrics);
        $v1 = $rubrics->firstWhere('version', 1);
        $v2 = $rubrics->firstWhere('version', 2);
        $this->assertSame(EvaluationRubricContract::INITIAL_TITLE, $v1->title);
        $this->assertSame(['pertinence', 'clarity', 'feasibility', 'impact', 'coherence'], $v1->criteria->pluck('code')->all());
        $this->assertSame(EvaluationRubricContract::LEGAL_TITLE, $v2->title);
        $this->assertSame(RubricVersionStatus::Active, $v2->status);
        $this->assertSame('migration', $v2->activation_source);
        $this->assertNull($v2->activated_by_user_id);
        $this->assertSame([
            'relevance_diagnosis',
            'quality_originality',
            'participation_coordination',
            'impact_sustainability',
        ], $v2->criteria->pluck('code')->all());
        $this->assertSame([25.0, 25.0, 25.0, 25.0], $v2->criteria->pluck('weight')->map(fn ($weight) => (float) $weight)->all());
        $this->assertTrue($v2->criteria->every(fn (RubricCriterion $criterion): bool => $criterion->description === null));
        $this->assertTrue($preserved->fresh()->hasExactRoles(['participant']));
        $this->assertDatabaseCount('judge_assignments', 0);
        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_seed_fails_closed_on_legal_v2_divergence_without_overwriting_it(): void
    {
        $v2 = RubricVersion::query()->where('version', 2)->firstOrFail();
        DB::table('rubric_versions')->where('id', $v2->id)->update(['title' => 'Divergencia sintética']);

        try {
            $this->seed(FlowerFlowSeeder::class);
            $this->fail('A divergent legal rubric must fail closed.');
        } catch (RuntimeException|LogicException) {
            $this->assertSame('Divergencia sintética', $v2->fresh()->title);
            $this->assertDatabaseCount('rubric_versions', 2);
        }
    }

    public function test_permissions_and_routes_are_isolated_and_unknown_catalog_creation_is_closed(): void
    {
        $admin = $this->admin();
        $participant = $this->participant();
        $reviewer = $this->reviewer();
        $judge = User::factory()->create(['email_verified_at' => now('UTC')]);
        $judge->assignRole('judge');
        $v2 = RubricVersion::query()->where('version', 2)->firstOrFail();

        foreach (['view evaluation rubrics', 'manage evaluation rubrics'] as $permission) {
            $this->assertTrue(Role::findByName('admin')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('judge')->hasPermissionTo($permission));
        }
        $this->actingAs($admin)->get(route('panel.rubrics.index'))->assertOk()->assertSee(EvaluationRubricContract::LEGAL_TITLE);
        $this->actingAs($admin)->get(route('panel.rubrics.show', $v2))->assertOk()->assertSee('relevance_diagnosis');
        $this->actingAs($admin)->get(route('panel.rubrics.create'))->assertStatus(409);
        foreach ([$participant, $reviewer, $judge] as $unauthorized) {
            $this->actingAs($unauthorized)->get(route('panel.rubrics.index'))->assertForbidden();
            $this->actingAs($unauthorized)->get(route('panel.rubrics.show', $v2))->assertForbidden();
        }
    }

    public function test_active_legal_rubric_and_criteria_are_immutable_and_guarded(): void
    {
        $v2 = RubricVersion::query()->where('version', 2)->firstOrFail();
        try {
            $v2->forceFill(['title' => 'Mutación prohibida'])->save();
            $this->fail('Active rubric must be immutable.');
        } catch (LogicException) {
            $this->assertSame(EvaluationRubricContract::LEGAL_TITLE, $v2->fresh()->title);
        }
        $criterion = $v2->criteria()->firstOrFail();
        try {
            $criterion->forceFill(['weight' => '10.0000'])->save();
            $this->fail('Active criterion must be immutable.');
        } catch (LogicException) {
            $this->assertSame('25.0000', $criterion->fresh()->weight);
        }

        $this->expectException(MassAssignmentException::class);
        RubricVersion::query()->create(['version' => 99]);
    }
}
