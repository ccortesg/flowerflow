<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_contains_critical_semantic_content_and_legal_downloads(): void
    {
        $this->seedFlowerFlow();

        $this->get('/')->assertOk()
            ->assertSee('Hermosillo Florece 2026')
            ->assertSee('15 de agosto de 2026, 23:59 horas')
            ->assertSee('Movilidad con Flow')
            ->assertSee('Hermosillo Florece')
            ->assertSee('Mi familia, mi mascota')
            ->assertSee('Apple iPad Pro')
            ->assertSee('Recepción aún no habilitada');

        foreach (glob(public_path('documentos/2026/*.pdf')) as $pdf) {
            $this->assertFileExists($pdf);
        }
    }
}
