<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_rejects_minor_and_non_e164_phone(): void
    {
        $this->seedFlowerFlow();
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->actingAs($user)->put('/perfil', [
            'first_names' => 'Ana', 'last_names' => 'Prueba', 'mobile_national' => '66212345',
            'birth_date' => now()->subYears(17)->toDateString(), 'neighborhood' => 'Centro',
            'adult_declaration' => '1', 'resident_declaration' => '1',
        ])->assertSessionHasErrors(['mobile_national', 'birth_date']);

        $this->assertDatabaseCount('participant_profiles', 0);
    }

    public function test_whatsapp_is_optional_and_reversible(): void
    {
        $this->seedFlowerFlow();
        $user = User::factory()->create();
        $user->assignRole('participant');
        $payload = [
            'first_names' => 'Ana', 'last_names' => 'Prueba', 'mobile_national' => '662 123 4567',
            'birth_date' => '1990-01-01', 'neighborhood' => 'Centro',
            'adult_declaration' => '1', 'resident_declaration' => '1',
        ];

        $this->actingAs($user)->put('/perfil', [...$payload, 'whatsapp_opt_in' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->profile->whatsapp_opt_in);
        $this->actingAs($user)->put('/perfil', [...$payload, 'whatsapp_opt_in' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->profile->whatsapp_opt_in);
        $this->assertSame('+526621234567', $user->fresh()->profile->mobile_e164);
    }
}
