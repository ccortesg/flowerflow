<?php

namespace Tests\Feature;

use App\Actions\Rubrics\CreateRubricDraft;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Enums\RubricVersionStatus;
use App\Models\AuditLog;
use App\Models\Competition;
use App\Models\JudgeProfile;
use App\Models\RubricCriterion;
use App\Models\RubricVersion;
use App\Models\User;
use App\Services\EvaluationRubricContract;
use Database\Seeders\FlowerFlowSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
        ]);
        $this->seedFlowerFlow();
    }

    public function test_local_provisioning_is_exact_idempotent_additive_and_fails_closed_on_divergence(): void
    {
        $preserved = $this->participant(['email' => 'preserved-rubric@example.test']);

        $this->seed(FlowerFlowSeeder::class);
        $this->seed(FlowerFlowSeeder::class);

        $rubric = RubricVersion::query()->with('criteria')->sole();
        $this->assertSame(1, $rubric->version);
        $this->assertSame(RubricVersionStatus::Draft, $rubric->status);
        $this->assertSame(EvaluationRubricContract::INITIAL_TITLE, $rubric->title);
        $this->assertSame(
            ['pertinence', 'clarity', 'feasibility', 'impact', 'coherence'],
            $rubric->criteria->pluck('code')->all(),
        );
        $this->assertSame([20.0, 20.0, 25.0, 25.0, 10.0], $rubric->criteria->pluck('weight')->map(fn ($weight) => (float) $weight)->all());
        $this->assertTrue($rubric->criteria->every(fn (RubricCriterion $criterion) => $criterion->description === null));
        $this->assertTrue($preserved->fresh()->hasExactRoles(['participant']));
        $this->assertDatabaseCount('judge_profiles', 0);
        $this->assertTrue(Schema::hasTable('judge_assignments'));
        $this->assertDatabaseCount('judge_assignments', 0);
        $this->assertDatabaseCount('judge_conflicts', 0);
        $this->assertFalse(Schema::hasTable('evaluations'));

        DB::table('rubric_versions')->where('id', $rubric->id)->update(['title' => 'Título divergente sintético']);
        try {
            $this->seed(FlowerFlowSeeder::class);
            $this->fail('A divergent canonical version must fail without being overwritten.');
        } catch (RuntimeException) {
            $this->assertSame('Título divergente sintético', $rubric->fresh()->title);
            $this->assertDatabaseCount('rubric_versions', 1);
        }
    }

    public function test_permissions_routes_and_judge_workspace_are_isolated_by_exact_role(): void
    {
        $admin = $this->adminWithPassword();
        $participant = $this->participant();
        $reviewer = $this->reviewer();
        $judge = $this->activeJudge($admin);
        $roleless = User::factory()->create();
        $multiRole = User::factory()->create();
        $multiRole->assignRole(['participant', 'admin']);
        $rubric = RubricVersion::query()->firstOrFail();

        foreach (['view evaluation rubrics', 'manage evaluation rubrics'] as $permission) {
            $this->assertTrue(Role::findByName('admin')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('participant')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('reviewer')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('judge')->hasPermissionTo($permission));
        }
        $this->assertFalse($admin->can('access judge workspace'));

        $this->actingAs($admin)->get(route('panel.rubrics.index'))->assertOk()->assertSee('Rúbricas');
        $this->actingAs($admin)->get(route('panel.rubrics.create'))->assertOk()->assertSee('Nueva versión');
        $this->actingAs($admin)->get(route('panel.rubrics.show', $rubric))->assertOk()->assertSee('pertinence');
        $this->actingAs($admin)->get('/panel/rubricas/01J00000000000000000000000')->assertNotFound();

        foreach ([$participant, $reviewer, $judge, $roleless, $multiRole] as $unauthorized) {
            $this->actingAs($unauthorized)->get(route('panel.rubrics.index'))->assertForbidden();
            $this->actingAs($unauthorized)->get(route('panel.rubrics.show', $rubric))->assertForbidden();
            $this->actingAs($unauthorized)->post(route('panel.rubrics.store'), $this->payload(2))->assertForbidden();
        }

        $this->actingAs($judge)->get(route('judge.dashboard'))->assertOk()->assertDontSee('Pertinencia');
    }

    public function test_draft_creation_rejects_invalid_contract_and_lifecycle_mass_assignment(): void
    {
        $admin = $this->adminWithPassword();
        $invalidPayloads = [
            $this->changedPayload('criteria.0.code', 'relevance'),
            $this->changedPayload('criteria.1.weight', '20.00001'),
            $this->changedPayload('criteria.2.score_step', '0.25'),
            $this->changedPayload('internal_decimal_places', 2),
            $this->changedPayload('rounding_mode', 'HALF_EVEN'),
            $this->changedPayload('total_weight', 'NaN'),
            $this->changedPayload('criteria.0.description', 'Texto no aprobado'),
            [...$this->payload(2), 'status' => 'active'],
            [...$this->payload(2), 'criteria' => [...$this->payload(2)['criteria'], $this->payload(2)['criteria'][0]]],
        ];

        foreach ($invalidPayloads as $payload) {
            $this->actingAs($admin)->post(route('panel.rubrics.store'), $payload)->assertSessionHasErrors();
            $this->assertDatabaseMissing('rubric_versions', ['version' => 2]);
        }

        $this->actingAs($admin)->post(route('panel.rubrics.store'), $this->payload(2))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $created = RubricVersion::query()->where('version', 2)->with('criteria')->firstOrFail();
        $this->assertSame(RubricVersionStatus::Draft, $created->status);
        $this->assertNull($created->active_slot);
        $this->assertCount(5, $created->criteria);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'rubric.draft_created',
            'auditable_id' => $created->id,
            'actor_user_id' => $admin->id,
        ]);

        $this->expectException(MassAssignmentException::class);
        RubricVersion::query()->create([
            'competition_id' => $created->competition_id,
            'version' => 99,
            'status' => RubricVersionStatus::Active,
        ]);
    }

    public function test_draft_can_be_corrected_but_activation_requires_password_reason_and_complete_contract(): void
    {
        $admin = $this->adminWithPassword();
        $rubric = $this->createDraft($admin, 2);
        $updatePayload = $this->payload(2, 'Título interno actualizado');
        unset($updatePayload['version']);

        $this->actingAs($admin)->put(route('panel.rubrics.update', $rubric), $updatePayload)
            ->assertRedirect(route('panel.rubrics.show', $rubric));
        $this->assertSame('Título interno actualizado', $rubric->fresh()->title);
        $this->assertSame($admin->id, $rubric->fresh()->last_edited_by_user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rubric.draft_edited', 'auditable_id' => $rubric->id]);

        $this->actingAs($admin)->post(route('panel.rubrics.activate', $rubric), [
            'reason' => 'Razón suficientemente extensa para activar.',
            'current_password' => 'incorrecta',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame(RubricVersionStatus::Draft, $rubric->fresh()->status);

        DB::table('rubric_criteria')->where('rubric_version_id', $rubric->id)->where('code', 'coherence')->delete();
        $this->actingAs($admin)->post(route('panel.rubrics.activate', $rubric), [
            'reason' => 'Razón suficientemente extensa para activar.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('rubric');
        $this->assertSame(RubricVersionStatus::Draft, $rubric->fresh()->status);

        $this->actingAs($admin)->put(route('panel.rubrics.update', $rubric), $updatePayload)->assertRedirect();
        $this->assertDatabaseCount('rubric_criteria', 10);
    }

    public function test_activation_supersedes_atomically_and_active_or_superseded_versions_are_immutable(): void
    {
        $admin = $this->adminWithPassword();
        $first = RubricVersion::query()->where('version', 1)->firstOrFail();
        $second = $this->createDraft($admin, 2);

        $this->activate($admin, $first);
        $this->assertSame(RubricVersionStatus::Active, $first->fresh()->status);
        $this->assertSame(1, $first->fresh()->active_slot);
        $this->assertSame(1, RubricVersion::query()->where('status', RubricVersionStatus::Active)->count());

        $this->activate($admin, $second);
        $this->assertSame(RubricVersionStatus::Superseded, $first->fresh()->status);
        $this->assertNull($first->fresh()->active_slot);
        $this->assertNotNull($first->fresh()->superseded_at);
        $this->assertSame(RubricVersionStatus::Active, $second->fresh()->status);
        $this->assertSame(1, RubricVersion::query()->where('status', RubricVersionStatus::Active)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'rubric.superseded', 'auditable_id' => $first->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rubric.activated', 'auditable_id' => $second->id]);

        $this->actingAs($admin)->get(route('panel.rubrics.edit', $second))->assertForbidden();
        $this->actingAs($admin)->post(route('panel.rubrics.activate', $first), [
            'reason' => 'No debe poder reactivarse esta versión sustituida.',
            'current_password' => 'AdminPass1!',
        ])->assertForbidden();

        foreach ([$first->fresh(), $second->fresh()] as $immutable) {
            try {
                $immutable->forceFill(['title' => 'Mutación prohibida'])->save();
                $this->fail('Active and superseded versions must reject updates.');
            } catch (LogicException) {
                $this->assertNotSame('Mutación prohibida', $immutable->fresh()->title);
            }

            try {
                $immutable->delete();
                $this->fail('Rubric versions must not be deleted.');
            } catch (LogicException) {
                $this->assertDatabaseHas('rubric_versions', ['id' => $immutable->id]);
            }
        }

        $criterion = $second->criteria()->firstOrFail();
        try {
            $criterion->forceFill(['label' => 'Cambio prohibido'])->save();
            $this->fail('Active rubric criteria must reject updates.');
        } catch (LogicException) {
            $this->assertNotSame('Cambio prohibido', $criterion->fresh()->label);
        }
    }

    public function test_mysql_constraints_reject_invalid_values_and_a_second_active_slot(): void
    {
        $admin = $this->adminWithPassword();
        $active = RubricVersion::query()->where('version', 1)->firstOrFail();
        $this->activate($admin, $active);
        $draft = $this->createDraft($admin, 2);

        try {
            DB::table('rubric_versions')->where('id', $draft->id)->update(['criterion_score_step' => '0.2500']);
            $this->fail('The database must reject a divergent score step.');
        } catch (QueryException) {
            $this->assertSame('0.5000', $draft->fresh()->criterion_score_step);
        }

        try {
            DB::table('rubric_versions')->where('id', $draft->id)->update([
                'status' => RubricVersionStatus::Active->value,
                'active_slot' => 1,
                'activated_at' => now('UTC'),
                'activated_by_user_id' => $admin->id,
                'activation_reason' => 'Intento directo concurrente suficientemente largo.',
            ]);
            $this->fail('The database must reject a second active slot for one competition.');
        } catch (QueryException) {
            $this->assertSame(RubricVersionStatus::Draft, $draft->fresh()->status);
            $this->assertSame(1, RubricVersion::query()->where('status', RubricVersionStatus::Active)->count());
        }

        try {
            DB::table('rubric_criteria')->where('rubric_version_id', $draft->id)->where('code', 'pertinence')->update(['weight' => '19.0000']);
            $this->fail('The database must reject a divergent criterion weight.');
        } catch (QueryException) {
            $this->assertSame('20.0000', $draft->criteria()->where('code', 'pertinence')->firstOrFail()->weight);
        }
    }

    public function test_audit_is_redacted_and_m3_preserves_judge_profile_capacity_and_existing_data(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $profile = $judge->judgeProfile;
        $participant = $this->participant(['email' => 'm3-preserved-participant@example.test']);
        $rubric = $this->createDraft($admin, 2);
        $this->activate($admin, $rubric, 'Activación M3 con evidencia técnica sintética.');

        $this->assertNull($profile->fresh()->max_active_assignments);
        $this->assertSame(JudgeAssignmentRole::Primary, $profile->fresh()->assignment_role);
        $this->assertTrue($participant->fresh()->hasExactRoles(['participant']));
        $this->assertTrue(Schema::hasTable('judge_assignments'));
        $this->assertDatabaseCount('judge_assignments', 0);
        $this->assertDatabaseCount('judge_conflicts', 0);
        $this->assertFalse(Schema::hasTable('evaluations'));

        $auditPayload = AuditLog::query()->where('auditable_type', $rubric->getMorphClass())->get()->toJson();
        $this->assertStringNotContainsString('AdminPass1!', $auditPayload);
        $this->assertStringNotContainsString('password', mb_strtolower($auditPayload));
        $this->assertStringNotContainsString('two_factor', mb_strtolower($auditPayload));
        $this->assertStringNotContainsString('proposal', mb_strtolower($auditPayload));
    }

    private function payload(int $version, string $title = EvaluationRubricContract::INITIAL_TITLE): array
    {
        $contract = app(EvaluationRubricContract::class);

        return [
            'version' => $version,
            'title' => $title,
            ...$contract->versionAttributes(),
            'criteria' => collect($contract->criteria())
                ->map(fn (array $criterion) => Arr::except($criterion, 'description'))
                ->all(),
        ];
    }

    private function createDraft(User $admin, int $version): RubricVersion
    {
        $contract = app(EvaluationRubricContract::class);

        return app(CreateRubricDraft::class)->execute(
            $admin,
            Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail(),
            $version,
            "Rúbrica sintética v{$version}",
            $contract->versionAttributes(),
            $contract->criteria(),
        );
    }

    private function changedPayload(string $path, mixed $value): array
    {
        $payload = $this->payload(2);
        Arr::set($payload, $path, $value);

        return $payload;
    }

    private function activate(User $admin, RubricVersion $rubric, string $reason = 'Activación administrativa sintética suficientemente justificada.'): void
    {
        $this->actingAs($admin)->post(route('panel.rubrics.activate', $rubric), [
            'reason' => $reason,
            'current_password' => 'AdminPass1!',
        ])->assertRedirect(route('panel.rubrics.show', $rubric));
    }

    private function adminWithPassword(): User
    {
        return $this->admin([
            'email' => 'admin-rubric-'.fake()->unique()->numerify('######').'@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
    }

    private function activeJudge(User $creator): User
    {
        $user = User::factory()->create();
        $user->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $user->id,
            'assignment_role' => JudgeAssignmentRole::Primary,
            'status' => JudgeProfileStatus::Active,
            'max_active_assignments' => null,
            'created_by_user_id' => $creator->id,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ])->save();

        return $user->setRelation('judgeProfile', $profile);
    }
}
