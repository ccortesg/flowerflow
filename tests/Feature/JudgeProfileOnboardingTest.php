<?php

namespace Tests\Feature;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Judges\CreateJudgeAccount;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\AuditLog;
use App\Models\JudgeProfile;
use App\Models\User;
use App\Notifications\JudgeAccountSetupNotification;
use App\Notifications\JudgeAccountStatusNotification;
use App\Notifications\JudgeVerifyEmailNotification;
use Database\Seeders\FlowerFlowSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JudgeProfileOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'session.driver' => 'database',
        ]);
        $this->seedFlowerFlow();
    }

    public function test_m2_provisioning_is_idempotent_additive_and_creates_no_accounts_or_profiles(): void
    {
        $preserved = $this->participant(['email' => 'preserved-m2@example.test']);
        $usersBefore = User::query()->count();

        $this->seed(FlowerFlowSeeder::class);
        $this->seed(FlowerFlowSeeder::class);

        $this->assertSame($usersBefore, User::query()->count());
        $this->assertDatabaseCount('judge_profiles', 0);
        $this->assertTrue($preserved->fresh()->hasExactRoles(['participant']));
        $this->assertSame(4, Role::query()->count());
        $this->assertSame(1, Permission::query()->where('name', 'view judges')->count());
        $this->assertSame(1, Permission::query()->where('name', 'manage judges')->count());
        $this->assertSame(1, Permission::query()->where('name', 'recover judge two factor')->count());

        foreach (['view judges', 'manage judges', 'recover judge two factor'] as $permission) {
            $this->assertTrue(Role::findByName('admin')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('participant')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('reviewer')->hasPermissionTo($permission));
            $this->assertFalse(Role::findByName('judge')->hasPermissionTo($permission));
        }

        $this->assertFalse(Schema::hasTable('judge_invitations'));
        $this->assertFalse(Schema::hasTable('judge_assignments'));
    }

    public function test_only_admin_can_create_and_new_judge_has_secure_pending_profile_and_setup_mail(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();

        $this->actingAs($admin)->post(route('panel.judges.store'), [
            'name' => 'Función inválida',
            'email' => 'invalid-assignment-role@example.test',
            'assignment_role' => 'automatic',
        ])->assertSessionHasErrors('assignment_role');
        $this->assertDatabaseMissing('users', ['email' => 'invalid-assignment-role@example.test']);

        $response = $this->actingAs($admin)->post(route('panel.judges.store'), [
            'name' => '  Jueza Sintética  ',
            'email' => '  JUEZA.M2@EXAMPLE.TEST ',
            'assignment_role' => JudgeAssignmentRole::Primary->value,
            'status' => 'active',
            'max_active_assignments' => 99,
            'role' => 'admin',
            'email_verified_at' => now('UTC'),
        ]);

        $judge = User::query()->where('email', 'jueza.m2@example.test')->firstOrFail();
        $profile = $judge->judgeProfile;
        $response->assertRedirect(route('panel.judges.show', $profile));
        $response->assertSessionHasNoErrors();
        $this->assertSame('Jueza Sintética', $judge->name);
        $this->assertTrue($judge->hasExactRoles(['judge']));
        $this->assertNull($judge->email_verified_at);
        $this->assertSame(JudgeAssignmentRole::Primary, $profile->assignment_role);
        $this->assertSame(JudgeProfileStatus::PendingSetup, $profile->status);
        $this->assertNull($profile->max_active_assignments);
        $this->assertSame($admin->id, $profile->created_by_user_id);
        $this->assertNull($profile->password_initialized_at);
        $this->assertFalse(Hash::check('password', $judge->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'judge.account_created',
            'auditable_id' => $profile->id,
            'actor_user_id' => $admin->id,
        ]);
        Notification::assertSentTo($judge, JudgeAccountSetupNotification::class, function ($notification): bool {
            $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);
            $this->assertInstanceOf(ShouldQueueAfterCommit::class, $notification);

            return true;
        });
        $this->actingAs($admin)->get(route('panel.judges.index'))->assertOk()->assertSee('Jueza Sintética');
        $this->actingAs($admin)->get(route('panel.judges.create'))->assertOk()->assertSee('Alta directa');
        $this->actingAs($admin)->get(route('panel.judges.show', $profile))->assertOk()->assertSee('Principal')->assertSee('Sin límite fijo');

        $this->actingAs($admin)->post(route('panel.judges.store'), [
            'name' => 'Juez Sustituto Sintético',
            'email' => 'sustituto.m2@example.test',
            'assignment_role' => JudgeAssignmentRole::Substitute->value,
        ])->assertRedirect();
        $substitute = User::query()->where('email', 'sustituto.m2@example.test')->firstOrFail()->judgeProfile;
        $this->assertSame(JudgeAssignmentRole::Substitute, $substitute->assignment_role);
        $this->assertSame(10, $substitute->max_active_assignments);

        foreach ([$this->participant(), $this->reviewer(), $this->activeJudge($admin)] as $unauthorized) {
            $this->actingAs($unauthorized)->get(route('panel.judges.index'))->assertForbidden();
            $this->actingAs($unauthorized)->post(route('panel.judges.store'), [
                'name' => 'No autorizado',
                'email' => 'blocked-'.$unauthorized->id.'@example.test',
                'assignment_role' => JudgeAssignmentRole::Primary->value,
            ])->assertForbidden();
        }
    }

    public function test_existing_emails_repeated_requests_and_duplicate_profiles_fail_without_role_replacement_or_partial_rows(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();
        $existingAccounts = [
            $this->participant(['email' => 'participant-existing@example.test']),
            $this->reviewer(['email' => 'reviewer-existing@example.test']),
            $admin,
            $this->pendingJudge($admin, ['email' => 'judge-existing@example.test']),
        ];

        foreach ($existingAccounts as $account) {
            $rolesBefore = $account->getRoleNames()->all();
            $profilesBefore = JudgeProfile::query()->count();
            $this->actingAs($admin)->from(route('panel.judges.create'))->post(route('panel.judges.store'), [
                'name' => 'Intento duplicado',
                'email' => strtoupper($account->email),
                'assignment_role' => JudgeAssignmentRole::Primary->value,
            ])->assertRedirect(route('panel.judges.create'))->assertSessionHasErrors('email');
            $this->assertSame($rolesBefore, $account->fresh()->getRoleNames()->all());
            $this->assertSame($profilesBefore, JudgeProfile::query()->count());
        }

        $payload = [
            'name' => 'Petición repetida',
            'email' => 'repeated-m2@example.test',
            'assignment_role' => JudgeAssignmentRole::Primary->value,
        ];
        $this->actingAs($admin)->post(route('panel.judges.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('panel.judges.store'), $payload)->assertSessionHasErrors('email');
        $this->assertSame(1, User::query()->where('email', $payload['email'])->count());
        $this->assertSame(1, JudgeProfile::query()->whereHas('user', fn ($query) => $query->where('email', $payload['email']))->count());

        $judge = User::query()->where('email', $payload['email'])->firstOrFail();
        try {
            $duplicate = new JudgeProfile;
            $duplicate->forceFill([
                'user_id' => $judge->id,
                'assignment_role' => JudgeAssignmentRole::Primary,
                'status' => JudgeProfileStatus::PendingSetup,
                'max_active_assignments' => null,
                'created_by_user_id' => $admin->id,
            ])->save();
            $this->fail('The one-to-one database constraint should reject a duplicate judge profile.');
        } catch (QueryException) {
            $this->assertDatabaseCount('judge_profiles', 2);
        }
    }

    public function test_password_and_email_prerequisites_activate_idempotently_in_either_order(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();
        $first = app(CreateJudgeAccount::class)->execute($admin, 'Juez Contraseña Primero', 'password-first@example.test', JudgeAssignmentRole::Primary);
        $firstUser = $first->user;

        app(ResetUserPassword::class)->reset($firstUser, [
            'password' => 'JudgePass1!',
            'password_confirmation' => 'JudgePass1!',
        ]);
        $first->refresh();
        $initializedAt = $first->password_initialized_at;
        $this->assertNotNull($initializedAt);
        $this->assertSame(JudgeProfileStatus::PendingSetup, $first->status);
        Notification::assertSentTo($firstUser, JudgeVerifyEmailNotification::class);

        app(ResetUserPassword::class)->reset($firstUser, [
            'password' => 'JudgePass2!',
            'password_confirmation' => 'JudgePass2!',
        ]);
        $this->assertTrue($first->fresh()->password_initialized_at->equalTo($initializedAt));
        $this->assertSame(1, AuditLog::query()->where('action', 'judge.password_initialized')->where('auditable_id', $first->id)->count());

        $firstUser->markEmailAsVerified();
        event(new Verified($firstUser));
        $this->assertSame(JudgeProfileStatus::Active, $first->fresh()->status);
        $this->actingAs($firstUser->fresh())->get(route('judge.dashboard'))->assertOk();

        $second = app(CreateJudgeAccount::class)->execute($admin, 'Juez Correo Primero', 'email-first@example.test', JudgeAssignmentRole::Primary);
        $secondUser = $second->user;
        $secondUser->markEmailAsVerified();
        event(new Verified($secondUser));
        $this->assertSame(JudgeProfileStatus::PendingSetup, $second->fresh()->status);

        app(ResetUserPassword::class)->reset($secondUser, [
            'password' => 'JudgePass3!',
            'password_confirmation' => 'JudgePass3!',
        ]);
        $this->assertSame(JudgeProfileStatus::Active, $second->fresh()->status);
        $this->assertSame(1, AuditLog::query()->where('action', 'judge.profile_status_changed')->where('auditable_id', $second->id)->count());

        $participant = $this->participant(['email' => 'participant-reset-m2@example.test']);
        app(ResetUserPassword::class)->reset($participant, [
            'password' => 'ParticipantPass1!',
            'password_confirmation' => 'ParticipantPass1!',
        ]);
        $this->assertTrue(Hash::check('ParticipantPass1!', $participant->fresh()->password));
        $this->assertNull($participant->judgeProfile);
    }

    public function test_pending_suspended_roleless_and_multi_role_accounts_fail_closed_while_active_judge_keeps_m1_isolation(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();
        $pending = $this->pendingJudge($admin, ['email' => 'pending-state@example.test']);
        $active = $this->activeJudge($admin, ['email' => 'active-state@example.test']);
        $suspended = $this->activeJudge($admin, ['email' => 'suspended-state@example.test']);
        $suspended->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended])->save();
        $roleless = User::factory()->create(['email' => 'roleless-state@example.test']);
        $multiRole = User::factory()->create(['email' => 'multi-state@example.test']);
        $multiRole->assignRole(['participant', 'judge']);

        $this->actingAs($pending)->get(route('judge.dashboard'))->assertRedirect(route('verification.notice'));
        $this->actingAs($pending)->get(route('judge.status'))->assertOk()->assertSee('Completa la configuración');
        $this->actingAs($suspended)->get(route('judge.dashboard'))->assertRedirect(route('judge.status'));
        $this->actingAs($suspended)->get(route('judge.status'))->assertOk()->assertSee('Tu acceso está suspendido');

        $this->actingAs($active)->get(route('judge.dashboard'))->assertOk();
        $this->actingAs($active)->get(route('profile.edit'))->assertForbidden();
        $this->actingAs($active)->get(route('submissions.index'))->assertForbidden();
        $this->actingAs($active)->get(route('panel.dashboard'))->assertForbidden();
        $this->assertFalse($active->hasEnabledTwoFactorAuthentication());

        foreach ([$roleless, $multiRole] as $invalid) {
            $this->actingAs($invalid)->get(route('judge.status'))->assertForbidden();
            $this->actingAs($invalid)->get(route('judge.dashboard'))->assertForbidden();
        }

        config(['flowerflow.flags.evaluation' => false]);
        $this->actingAs($active)->get(route('judge.dashboard'))->assertNotFound();
        $this->actingAs($pending)->get(route('judge.status'))->assertNotFound();
    }

    public function test_suspension_and_reactivation_require_confirmation_revoke_sessions_and_preserve_history(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin, ['email' => 'suspension-m2@example.test', 'remember_token' => 'remember-before']);
        $profile = $judge->judgeProfile;
        $this->insertSession('judge-session-one', $judge);
        $this->insertSession('judge-session-two', $judge);

        $this->actingAs($admin)->post(route('panel.judges.suspend', $profile), [
            'reason' => 'Razón administrativa suficientemente extensa.',
            'current_password' => 'incorrecta',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame(JudgeProfileStatus::Active, $profile->fresh()->status);
        $this->assertSame(2, DB::table('sessions')->where('user_id', $judge->id)->count());

        $this->actingAs($admin)->post(route('panel.judges.suspend', $profile), [
            'reason' => 'Razón administrativa suficientemente extensa.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect();
        $profile->refresh();
        $this->assertSame(JudgeProfileStatus::Suspended, $profile->status);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $judge->id)->count());
        $this->assertNotSame('remember-before', $judge->fresh()->remember_token);
        $this->assertSame('Razón administrativa suficientemente extensa.', $profile->suspension_reason);
        Notification::assertSentTo($judge, JudgeAccountStatusNotification::class, fn ($notification) => $notification->event === 'suspended');
        $this->actingAs($judge)->get(route('judge.dashboard'))->assertRedirect(route('judge.status'));

        $this->actingAs($admin)->post(route('panel.judges.reactivate', $profile), [
            'reason' => 'Reactivación administrativa debidamente justificada.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect();
        $profile->refresh();
        $this->assertSame(JudgeProfileStatus::Active, $profile->status);
        $this->assertNotNull($profile->suspended_at);
        $this->assertNotNull($profile->reactivated_at);
        $this->assertSame($admin->id, $profile->reactivated_by_user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'judge.suspended', 'auditable_id' => $profile->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'judge.reactivated', 'auditable_id' => $profile->id]);
        Notification::assertSentTo($judge, JudgeAccountStatusNotification::class, fn ($notification) => $notification->event === 'reactivated');

        $pending = $this->pendingJudge($admin, ['email' => 'pending-reactivation@example.test']);
        $pending->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended])->save();
        $this->actingAs($admin)->post(route('panel.judges.reactivate', $pending->judgeProfile), [
            'reason' => 'Termina suspensión pero aún faltan prerrequisitos.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect();
        $this->assertSame(JudgeProfileStatus::PendingSetup, $pending->judgeProfile->fresh()->status);
    }

    public function test_two_factor_recovery_is_admin_only_redacted_and_revokes_all_sessions_without_granting_access(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin, ['email' => 'two-factor-m2@example.test', 'remember_token' => 'remember-2fa-before']);
        $profile = $judge->judgeProfile;
        $judge->forceFill([
            'two_factor_secret' => Crypt::encryptString('synthetic-totp-secret'),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(['synthetic-recovery-code'])),
            'two_factor_confirmed_at' => now('UTC'),
        ])->save();
        $this->insertSession('judge-2fa-session', $judge);

        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.judges.two-factor.recover', $profile), [
            'reason' => 'Intento de recuperación no autorizado.',
            'current_password' => 'password',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('panel.judges.two-factor.recover', $profile), [
            'reason' => 'Recuperación administrativa solicitada por soporte.',
            'current_password' => 'incorrecta',
        ])->assertSessionHasErrors('current_password');
        $this->assertNotNull($judge->fresh()->two_factor_secret);

        $this->actingAs($admin)->post(route('panel.judges.two-factor.recover', $profile), [
            'reason' => 'Recuperación administrativa solicitada por soporte.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect();
        $judge->refresh();
        $this->assertNull($judge->two_factor_secret);
        $this->assertNull($judge->two_factor_recovery_codes);
        $this->assertNull($judge->two_factor_confirmed_at);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $judge->id)->count());
        $this->assertNotSame('remember-2fa-before', $judge->remember_token);
        $this->assertSame(JudgeProfileStatus::Active, $profile->fresh()->status);
        $this->assertTrue($judge->hasExactRoles(['judge']));
        $audit = AuditLog::query()->where('action', 'judge.two_factor_recovered')->where('auditable_id', $profile->id)->firstOrFail();
        $this->assertSame('Recuperación administrativa solicitada por soporte.', $audit->metadata['reason']);
        $this->assertTrue($audit->metadata['had_two_factor']);
        $this->assertStringNotContainsString('synthetic-totp-secret', json_encode($audit->metadata));
        $this->assertStringNotContainsString('synthetic-recovery-code', json_encode($audit->metadata));
        Notification::assertSentTo($judge, JudgeAccountStatusNotification::class, fn ($notification) => $notification->event === 'two_factor_recovered');
    }

    public function test_idor_mass_assignment_capacity_constraint_and_mail_failure_are_fail_closed(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->pendingJudge($admin, ['email' => 'integrity-m2@example.test']);
        $profile = $judge->judgeProfile;

        $this->actingAs($admin)->get('/panel/jueces/01J00000000000000000000000')->assertNotFound();
        $this->actingAs($this->reviewer())->get(route('panel.judges.show', $profile))->assertForbidden();

        $this->expectException(MassAssignmentException::class);
        JudgeProfile::query()->create([
            'user_id' => $judge->id,
            'status' => JudgeProfileStatus::Active,
            'max_active_assignments' => 99,
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function test_database_enforces_primary_and_substitute_capacity_contract_and_mail_failure_does_not_rollback_account(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->pendingJudge($admin, ['email' => 'capacity-m2@example.test']);

        try {
            DB::table('judge_profiles')->where('id', $judge->judgeProfile->id)->update(['max_active_assignments' => 9]);
            $this->fail('A primary judge must not have a fixed capacity.');
        } catch (QueryException) {
            $this->assertNull($judge->judgeProfile->fresh()->max_active_assignments);
        }

        $substitute = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Sustituto de Capacidad',
            'capacity-substitute-m2@example.test',
            JudgeAssignmentRole::Substitute,
        );
        $this->assertSame(10, $substitute->max_active_assignments);
        try {
            DB::table('judge_profiles')->where('id', $substitute->id)->update(['max_active_assignments' => 9]);
            $this->fail('A substitute judge must have capacity ten.');
        } catch (QueryException) {
            $this->assertSame(10, $substitute->fresh()->max_active_assignments);
        }

        config(['flowerflow.mail.queue_connection' => 'missing-m2-connection']);
        $response = $this->actingAs($admin)->post(route('panel.judges.store'), [
            'name' => 'Juez Correo Fallido',
            'email' => 'mail-failure-m2@example.test',
            'assignment_role' => JudgeAssignmentRole::Primary->value,
        ]);
        $response->assertRedirect()->assertSessionHas('warning');
        $created = User::query()->where('email', 'mail-failure-m2@example.test')->firstOrFail();
        $this->assertTrue($created->hasExactRoles(['judge']));
        $this->assertSame(JudgeProfileStatus::PendingSetup, $created->judgeProfile->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'judge.setup_email.failed',
            'auditable_id' => $created->judgeProfile->id,
        ]);
    }

    public function test_setup_templates_are_dual_format_branded_and_audit_contains_no_credentials(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();
        $profile = app(CreateJudgeAccount::class)->execute($admin, 'Juez Plantilla', 'template-m2@example.test', JudgeAssignmentRole::Primary);
        $notification = new JudgeAccountSetupNotification('opaque-synthetic-token');
        $mail = $notification->toMail($profile->user);
        $html = (string) $mail->render();
        $text = view('mail.judge-account-setup-text', [
            'actionUrl' => 'https://example.test/reset/opaque-synthetic-token',
            'expiresInMinutes' => 60,
            'userName' => $profile->user->name,
        ])->render();

        $this->assertStringContainsString('Flower Flow', $html);
        $this->assertStringContainsString('Florece Hermosillo', $html);
        $this->assertStringContainsString('Establecer mi contraseña', $html);
        $this->assertStringContainsString('Configura tu acceso de juez', $text);
        $this->assertStringNotContainsString('contraseña inicial:', mb_strtolower($html));

        $auditPayload = AuditLog::query()
            ->where('auditable_id', $profile->id)
            ->get(['action', 'metadata'])
            ->toJson();
        $this->assertStringNotContainsString('opaque-synthetic-token', $auditPayload);
        $this->assertStringNotContainsString('two_factor_secret', $auditPayload);
        $this->assertStringNotContainsString('recovery_codes', $auditPayload);
    }

    private function adminWithPassword(): User
    {
        return $this->admin([
            'email' => 'admin-m2-'.fake()->unique()->numerify('######').'@example.test',
            'password' => 'AdminPass1!',
        ]);
    }

    private function pendingJudge(User $creator, array $attributes = []): User
    {
        $user = User::factory()->unverified()->create($attributes);
        $user->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $user->id,
            'assignment_role' => JudgeAssignmentRole::Primary,
            'status' => JudgeProfileStatus::PendingSetup,
            'max_active_assignments' => null,
            'created_by_user_id' => $creator->id,
        ])->save();

        return $user->setRelation('judgeProfile', $profile);
    }

    private function activeJudge(User $creator, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
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

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Synthetic M2 test',
            'payload' => base64_encode('synthetic-session'),
            'last_activity' => now()->timestamp,
        ]);
    }
}
