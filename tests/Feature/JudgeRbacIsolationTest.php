<?php

namespace Tests\Feature;

use App\Actions\AssignExclusiveBusinessRole;
use App\Enums\BusinessRole;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\User;
use Database\Seeders\FlowerFlowSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JudgeRbacIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.submissions' => true,
            'flowerflow.flags.admissibility_review' => true,
            'flowerflow.flags.evaluation' => true,
        ]);
        Storage::fake('local');
        $this->seedFlowerFlow();
    }

    public function test_each_valid_role_reaches_only_its_explicit_shell(): void
    {
        $participant = $this->participant(['email' => 'participant-m1@example.test']);
        $reviewer = $this->reviewer(['email' => 'reviewer-m1@example.test']);
        $admin = $this->admin(['email' => 'admin-m1@example.test']);
        $judge = $this->judge(['email' => 'judge-m1@example.test']);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('judge.dashboard'))->assertRedirect(route('login'));
        $this->get(route('panel.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($participant)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-testid="participant-menu"', false);
        $this->actingAs($participant)->get(route('profile.edit'))->assertOk();
        $this->actingAs($participant)->get(route('submissions.index'))->assertOk();
        $this->actingAs($participant)->get(route('panel.dashboard'))->assertForbidden();
        $this->actingAs($participant)->get(route('judge.dashboard'))->assertForbidden();

        foreach ([$reviewer, $admin] as $panelUser) {
            $this->actingAs($panelUser)->get(route('dashboard'))
                ->assertRedirect(route('panel.dashboard'));
            $this->actingAs($panelUser)->get(route('panel.dashboard'))->assertOk();
            $this->actingAs($panelUser)->get(route('profile.edit'))->assertForbidden();
            $this->actingAs($panelUser)->get(route('submissions.index'))->assertForbidden();
            $this->actingAs($panelUser)->get(route('judge.dashboard'))->assertForbidden();
        }

        $this->actingAs($judge)->get(route('dashboard'))
            ->assertRedirect(route('judge.dashboard'));
        $this->actingAs($judge)->get(route('judge.dashboard'))
            ->assertOk()
            ->assertSee('Área de evaluación')
            ->assertSee('consultar tus asignaciones y declarar conflictos')
            ->assertDontSee('data-testid="participant-menu"', false)
            ->assertDontSee('Propuestas</a>', false)
            ->assertDontSee('Admisibilidad</a>', false);
        $this->actingAs($judge)->get(route('profile.edit'))->assertForbidden();
        $this->actingAs($judge)->get(route('submissions.index'))->assertForbidden();
        $this->actingAs($judge)->get(route('panel.dashboard'))->assertForbidden();
    }

    public function test_unverified_disabled_permissionless_and_invalid_role_states_fail_closed(): void
    {
        $unverifiedJudge = $this->judge(['email_verified_at' => null]);
        $this->actingAs($unverifiedJudge)->get(route('judge.dashboard'))
            ->assertRedirect(route('verification.notice'));

        $judge = $this->judge(['email' => 'judge-disabled@example.test']);
        config(['flowerflow.flags.evaluation' => false]);
        $this->actingAs($judge)->get(route('judge.dashboard'))->assertNotFound();
        $this->actingAs($judge)->get(route('dashboard'))
            ->assertRedirect(route('account.restricted'));
        $this->actingAs($judge)->get(route('account.restricted'))
            ->assertOk()
            ->assertSee('El área de evaluación aún no está habilitada')
            ->assertDontSee('data-testid="participant-menu"', false);

        config(['flowerflow.flags.evaluation' => true]);
        $judgeRole = Role::findByName('judge');
        $judgeRole->revokePermissionTo('access judge workspace');
        $this->actingAs($judge)->get(route('judge.dashboard'))->assertForbidden();
        $judgeRole->givePermissionTo('access judge workspace');

        $withoutRole = User::factory()->create(['email' => 'without-role-m1@example.test']);
        $multipleRoles = User::factory()->create(['email' => 'multiple-roles-m1@example.test']);
        $multipleRoles->assignRole(['participant', 'judge']);

        foreach ([$withoutRole, $multipleRoles] as $invalidUser) {
            $this->actingAs($invalidUser)->get(route('dashboard'))
                ->assertRedirect(route('account.restricted'));
            $this->actingAs($invalidUser)->get(route('account.restricted'))
                ->assertOk()
                ->assertSee('Por seguridad, esta cuenta no entra por descarte')
                ->assertDontSee('data-testid="participant-menu"', false)
                ->assertDontSee('Resumen</a>', false);
            $this->actingAs($invalidUser)->get(route('profile.edit'))->assertForbidden();
            $this->actingAs($invalidUser)->get(route('submissions.index'))->assertForbidden();
            $this->actingAs($invalidUser)->get(route('panel.dashboard'))->assertForbidden();
            $this->actingAs($invalidUser)->get(route('judge.dashboard'))->assertForbidden();
        }
    }

    public function test_judge_cannot_access_private_submission_admissibility_residency_or_export_resources(): void
    {
        [$participant, $submission, $review] = $this->submittedReview();
        $judge = $this->judge();
        $file = $submission->files()->create([
            'actor_user_id' => $participant->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/'.$submission->public_id.'/private.pdf',
            'original_name' => 'private.pdf',
            'stored_name' => 'private.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('a', 64),
        ]);
        Storage::disk('local')->put($file->path, '%PDF-1.4');

        $residencyRequest = $review->residencyRequests()->create([
            'subject_user_id' => $participant->id,
            'requested_by_user_id' => $this->reviewer()->id,
            'status' => 'requested',
        ]);
        $residencyDocument = $residencyRequest->documents()->create([
            'uploader_user_id' => $participant->id,
            'document_type' => 'address_proof',
            'disk' => 'local',
            'path' => 'residency/private.pdf',
            'original_name' => 'residency.pdf',
            'stored_name' => 'residency.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('b', 64),
            'created_at' => now('UTC'),
        ]);

        $this->actingAs($participant)->get(route('submissions.show', $submission))->assertOk();
        $this->actingAs($participant)->get(route('submissions.files.download', [$submission, $file]))->assertOk();

        $this->actingAs($judge)->get(route('submissions.show', $submission))->assertForbidden();
        $this->actingAs($judge)->get(route('submissions.files.download', [$submission, $file]))->assertForbidden();
        $this->actingAs($judge)->get(route('admissibility.residency-documents.download', $residencyDocument))->assertForbidden();
        $this->actingAs($judge)->get(route('panel.admissibility.show', $review))->assertForbidden();
        $this->actingAs($judge)->get(route('panel.submissions.exports.create'))->assertForbidden();

        $alteredResponse = $this->actingAs($judge)->get('/propuestas/01J00000000000000000000000/archivos/01J00000000000000000000000');
        $this->assertContains($alteredResponse->getStatusCode(), [403, 404]);
        $alteredResponse->assertDontSee($submission->title);
    }

    public function test_role_and_permission_provisioning_is_idempotent_and_preserves_existing_accounts(): void
    {
        $participant = $this->participant(['email' => 'preserved-m1@example.test']);
        $initialUserCount = User::query()->count();

        $this->seed(FlowerFlowSeeder::class);
        $this->seed(FlowerFlowSeeder::class);

        $this->assertSame($initialUserCount, User::query()->count());
        $this->assertDatabaseCount('roles', 4);
        $this->assertSame(1, Permission::query()->where('name', 'access judge workspace')->where('guard_name', 'web')->count());
        $this->assertTrue($participant->fresh()->hasExactRoles(['participant']));
        $this->assertSame(0, Role::findByName('judge')->users()->count());
        $this->assertSame(
            ['access judge workspace', 'declare own evaluation conflicts'],
            Role::findByName('judge')->permissions()->pluck('name')->sort()->values()->all(),
        );
        $this->assertFalse(Role::findByName('participant')->hasPermissionTo('access judge workspace'));
        $this->assertFalse(Role::findByName('reviewer')->hasPermissionTo('access judge workspace'));
        $this->assertFalse(Role::findByName('admin')->hasPermissionTo('access judge workspace'));
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('view panel'));
        $this->assertTrue(Role::findByName('reviewer')->hasPermissionTo('view admissibility reviews'));
    }

    public function test_exclusive_role_writer_is_idempotent_and_rejects_replacement_or_invalid_combinations(): void
    {
        $action = app(AssignExclusiveBusinessRole::class);
        $user = User::factory()->create();

        $action->execute($user, BusinessRole::Judge);
        $action->execute($user, BusinessRole::Judge);
        $this->assertTrue($user->fresh()->hasExactRoles(['judge']));

        try {
            $action->execute($user, BusinessRole::Participant);
            $this->fail('An implicit business-role replacement should have been rejected.');
        } catch (DomainException) {
            $this->assertTrue($user->fresh()->hasExactRoles(['judge']));
        }

        $multipleRoles = User::factory()->create();
        $multipleRoles->assignRole(['participant', 'reviewer']);

        $this->expectException(DomainException::class);
        $action->execute($multipleRoles, BusinessRole::Admin);
    }

    public function test_admin_command_rejects_implicit_role_replacement_without_mutating_account(): void
    {
        $participant = $this->participant([
            'name' => 'Cuenta participante preservada',
            'email' => 'existing-participant@example.test',
            'password' => 'Aa1!old-password',
        ]);

        $this->artisan('flowerflow:admin', [
            'email' => $participant->email,
            '--name' => 'Cambio no autorizado',
            '--password' => 'Aa1!new-password',
        ])->expectsOutput('La cuenta ya tiene un rol de negocio distinto o una combinación inválida. No se realizaron cambios.')
            ->assertFailed();

        $participant->refresh();
        $this->assertSame('Cuenta participante preservada', $participant->name);
        $this->assertTrue(Hash::check('Aa1!old-password', $participant->password));
        $this->assertTrue($participant->hasExactRoles(['participant']));
    }

    private function judge(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('judge');

        if ($user->email_verified_at) {
            $creator = User::role('admin')->first() ?? $this->admin([
                'email' => 'judge-creator-'.fake()->unique()->numerify('######').'@example.test',
            ]);
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
        }

        return $user;
    }
}
