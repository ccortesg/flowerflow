<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\FlowerFlowSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedFlowerFlow(): void
    {
        $this->seed(FlowerFlowSeeder::class);
    }

    protected function participant(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('participant');
        $user->profile()->create([
            'first_names' => 'Persona',
            'last_names' => 'Prueba',
            'mobile_e164' => '+526621234567',
            'whatsapp_opt_in' => false,
            'birth_date' => '1990-01-01',
            'neighborhood' => 'Centro',
            'adult_declared_at' => now('UTC'),
            'hermosillo_resident_declared_at' => now('UTC'),
        ]);

        return $user;
    }
}
