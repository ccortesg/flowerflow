<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_cannot_enter_panel_and_admin_can(): void
    {
        $this->seedFlowerFlow();
        $participant = $this->participant();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($participant)->get('/panel')->assertForbidden();
        $this->actingAs($admin)->get('/panel')->assertOk()->assertSee('Resumen de convocatoria');
    }
}
