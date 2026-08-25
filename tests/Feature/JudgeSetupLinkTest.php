<?php

namespace Tests\Feature;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Judges\CreateJudgeAccount;
use App\Actions\Judges\SendJudgeSetupNotification;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\JudgeSetupLink;
use App\Notifications\JudgeAccountSetupNotification;
use App\Notifications\JudgeVerifyEmailNotification;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class JudgeSetupLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.flags.communication_ledger' => false,
            'flowerflow.judge_notifications.account_setup_enabled' => true,
            'flowerflow.judge_notifications.setup_link_ttl_minutes' => 2880,
        ]);
        $this->seedFlowerFlow();
    }

    public function test_get_is_read_only_and_post_configures_password_verifies_email_activates_and_consumes_once(): void
    {
        Notification::fake();
        Event::fake([Verified::class]);
        $admin = $this->admin();
        $profile = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Enlace Inicial',
            'judge-purpose-link@example.test',
            JudgeAssignmentRole::Primary,
            true,
        );
        $notification = Notification::sent($profile->user, JudgeAccountSetupNotification::class)->sole();
        $link = JudgeSetupLink::query()->sole();
        $url = URL::temporarySignedRoute('judge.setup.show', $link->expires_at, [
            'setupLink' => $link,
            'token' => $notification->token,
        ]);

        $before = $link->getAttributes();
        $this->get($url)->assertOk()->assertSee('Guardar contraseña y verificar correo');
        $this->get($url)->assertOk();
        $this->assertSame($before, $link->fresh()->getAttributes());

        $this->post($url, [
            'password' => 'JudgePurpose1!',
            'password_confirmation' => 'JudgePurpose1!',
        ])->assertRedirect(route('login', ['context' => 'judge']));
        $judge = $profile->user->fresh();
        $this->assertTrue(Hash::check('JudgePurpose1!', $judge->password));
        $this->assertTrue($judge->hasVerifiedEmail());
        $this->assertSame(JudgeProfileStatus::Active, $profile->fresh()->status);
        $this->assertNotNull($profile->fresh()->password_initialized_at);
        $this->assertNull($link->fresh()->active_slot);
        $this->assertNotNull($link->fresh()->consumed_at);
        Event::assertDispatchedTimes(Verified::class, 1);
        $this->get(route('login', ['context' => 'judge']))
            ->assertOk()
            ->assertSee('Cuenta de juez')
            ->assertSee('Acceso al área de evaluación')
            ->assertDontSee('Cuenta participante')
            ->assertDontSee('¿Aún no tienes cuenta?');
        $this->post($url, [
            'password' => 'JudgePurpose2!',
            'password_confirmation' => 'JudgePurpose2!',
        ])->assertGone();
        Event::assertDispatchedTimes(Verified::class, 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'judge.setup_link.consumed', 'auditable_id' => $link->id]);
    }

    public function test_resend_invalidates_previous_link_and_changed_email_fails_closed(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $profile = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Reenvío',
            'judge-resend@example.test',
            JudgeAssignmentRole::Substitute,
            true,
        );
        $first = JudgeSetupLink::query()->sole();
        app(SendJudgeSetupNotification::class)->execute($profile, $admin);
        $second = JudgeSetupLink::query()->where('id', '<>', $first->id)->sole();
        $this->assertNull($first->fresh()->active_slot);
        $this->assertNotNull($first->fresh()->invalidated_at);
        $this->assertSame(1, $second->active_slot);

        $secondNotification = Notification::sent($profile->user, JudgeAccountSetupNotification::class)->last();
        $url = URL::temporarySignedRoute('judge.setup.show', $second->expires_at, [
            'setupLink' => $second,
            'token' => $secondNotification->token,
        ]);
        $profile->user->forceFill(['email' => 'judge-resend-changed@example.test'])->save();
        $this->get($url)->assertGone();
    }

    public function test_generic_password_reset_never_verifies_email_or_sends_a_second_verification(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $profile = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Reset Genérico',
            'judge-generic-reset@example.test',
            JudgeAssignmentRole::Primary,
            false,
        );

        app(ResetUserPassword::class)->reset($profile->user, [
            'password' => 'JudgeGeneric1!',
            'password_confirmation' => 'JudgeGeneric1!',
        ]);

        $this->assertFalse($profile->user->fresh()->hasVerifiedEmail());
        $this->assertSame(JudgeProfileStatus::PendingSetup, $profile->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_global_flag_and_operation_checkbox_control_link_and_notification_creation(): void
    {
        Notification::fake();
        $admin = $this->admin();
        config(['flowerflow.judge_notifications.account_setup_enabled' => false]);

        $this->actingAs($admin)->post(route('panel.judges.store'), [
            'name' => 'Juez Sin Correo',
            'email' => 'judge-no-mail@example.test',
            'assignment_role' => JudgeAssignmentRole::Primary->value,
            'send_setup_notification' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('judge_setup_links', 0);
        Notification::assertNothingSent();

        $this->actingAs($admin)->post(route('panel.judges.store'), [
            'name' => 'Juez Solicitud Bloqueada',
            'email' => 'judge-blocked-mail@example.test',
            'assignment_role' => JudgeAssignmentRole::Primary->value,
            'send_setup_notification' => 1,
        ])->assertSessionHasErrors('send_setup_notification');
        $this->assertDatabaseMissing('users', ['email' => 'judge-blocked-mail@example.test']);
    }

    public function test_altered_signature_crossed_token_and_expired_link_fail_closed(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $firstProfile = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Enlace Uno',
            'judge-link-one@example.test',
            JudgeAssignmentRole::Primary,
            true,
        );
        $secondProfile = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Enlace Dos',
            'judge-link-two@example.test',
            JudgeAssignmentRole::Substitute,
            true,
        );
        $firstLink = JudgeSetupLink::query()->where('judge_profile_id', $firstProfile->id)->sole();
        $firstNotification = Notification::sent($firstProfile->user, JudgeAccountSetupNotification::class)->sole();
        $secondNotification = Notification::sent($secondProfile->user, JudgeAccountSetupNotification::class)->sole();

        $validUrl = URL::temporarySignedRoute('judge.setup.show', $firstLink->expires_at, [
            'setupLink' => $firstLink,
            'token' => $firstNotification->token,
        ]);
        $alteredUrl = preg_replace('/signature=[^&]+/', 'signature=altered', $validUrl);
        $this->assertIsString($alteredUrl);
        $this->get($alteredUrl)->assertForbidden();

        $crossedUrl = URL::temporarySignedRoute('judge.setup.show', $firstLink->expires_at, [
            'setupLink' => $firstLink,
            'token' => $secondNotification->token,
        ]);
        $this->get($crossedUrl)->assertGone();

        try {
            CarbonImmutable::setTestNow($firstLink->expires_at->addSecond());
            $this->get($validUrl)->assertForbidden();
        } finally {
            CarbonImmutable::setTestNow();
        }

        $this->assertNull($firstLink->fresh()->consumed_at);
        $this->assertFalse($firstProfile->user->fresh()->hasVerifiedEmail());
    }

    public function test_changing_judge_email_revokes_verification_and_returns_profile_to_pending_setup(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $profile = app(CreateJudgeAccount::class)->execute(
            $admin,
            'Juez Cambio Correo',
            'judge-email-before@example.test',
            JudgeAssignmentRole::Primary,
            true,
        );
        $notification = Notification::sent($profile->user, JudgeAccountSetupNotification::class)->sole();
        $link = JudgeSetupLink::query()->sole();
        $url = URL::temporarySignedRoute('judge.setup.show', $link->expires_at, [
            'setupLink' => $link,
            'token' => $notification->token,
        ]);
        $this->post($url, [
            'password' => 'JudgeEmailBefore1!',
            'password_confirmation' => 'JudgeEmailBefore1!',
        ])->assertRedirect(route('login', ['context' => 'judge']));
        $this->assertSame(JudgeProfileStatus::Active, $profile->fresh()->status);

        Notification::fake();
        app(UpdateUserProfileInformation::class)->update($profile->user->fresh(), [
            'name' => 'Juez Cambio Correo',
            'email' => 'judge-email-after@example.test',
        ]);

        $this->assertFalse($profile->user->fresh()->hasVerifiedEmail());
        $this->assertSame(JudgeProfileStatus::PendingSetup, $profile->fresh()->status);
        Notification::assertSentTo($profile->user->fresh(), JudgeVerifyEmailNotification::class);
    }
}
