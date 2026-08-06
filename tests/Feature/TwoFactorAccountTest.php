<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_two_factor_flow_requires_password_and_supports_confirmation_recovery_and_disable(): void
    {
        $this->seedFlowerFlow();
        $admin = User::factory()->create(['password' => 'Aa1!aaaa']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('panel.account.two-factor.enable'), [
            'current_password' => 'incorrecta',
        ])->assertSessionHasErrors('current_password', errorBag: 'twoFactorAuthentication');
        $this->assertNull($admin->fresh()->two_factor_secret);

        $this->actingAs($admin)->post(route('panel.account.two-factor.enable'), [
            'current_password' => 'Aa1!aaaa',
        ])->assertRedirect()->assertSessionHas('status', Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED);

        $admin->refresh();
        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->actingAs($admin)->get(route('panel.account'))
            ->assertOk()
            ->assertSee('La activación todavía no termina')
            ->assertSee('<svg', false);

        $secret = Fortify::currentEncrypter()->decrypt($admin->two_factor_secret);
        $validCode = app(Google2FA::class)->getCurrentOtp($secret);
        $invalidCode = ($validCode[0] === '9' ? '8' : '9').substr($validCode, 1);

        $this->actingAs($admin)->post(route('panel.account.two-factor.confirm'), [
            'code' => $invalidCode,
        ])->assertSessionHasErrors('code', errorBag: 'confirmTwoFactorAuthentication');

        $this->actingAs($admin)->post(route('panel.account.two-factor.confirm'), [
            'code' => $validCode,
        ])->assertRedirect()->assertSessionHas('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);

        $admin->refresh();
        $this->assertNotNull($admin->two_factor_confirmed_at);
        $oldCodes = $admin->recoveryCodes();
        $this->assertCount(8, $oldCodes);
        $this->actingAs($admin)->get(route('panel.account'))
            ->assertOk()
            ->assertSee('2FA está activa y confirmada')
            ->assertSee($oldCodes[0]);

        $this->actingAs($admin)->post(route('panel.account.two-factor.recovery-codes'), [
            'current_password' => 'Aa1!aaaa',
        ])->assertRedirect()->assertSessionHas('status', Fortify::RECOVERY_CODES_GENERATED);
        $this->assertNotSame($oldCodes, $admin->fresh()->recoveryCodes());

        $this->actingAs($admin)->delete(route('panel.account.two-factor.disable'), [
            'current_password' => 'incorrecta',
        ])->assertSessionHasErrors('current_password', errorBag: 'twoFactorAuthentication');
        $this->assertNotNull($admin->fresh()->two_factor_secret);

        $this->actingAs($admin)->delete(route('panel.account.two-factor.disable'), [
            'current_password' => 'Aa1!aaaa',
        ])->assertRedirect()->assertSessionHas('status', Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED);
        $this->assertNull($admin->fresh()->two_factor_secret);
        $this->assertNull($admin->fresh()->two_factor_recovery_codes);
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);
    }
}
