<?php

namespace Tests\Feature;

use App\Models\Competition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_contains_critical_content_assets_and_legal_downloads(): void
    {
        $this->seedFlowerFlow();

        $response = $this->get('/')->assertOk()
            ->assertSeeText('¡Para mejorar aún más Hermosillo, todos a participar!')
            ->assertSee('15 de agosto de 2026, 23:59 horas')
            ->assertSee('tiempo de Hermosillo')
            ->assertSee('Movilidad con Flow')
            ->assertSee('Hermosillo Florece')
            ->assertSee('Mi familia, mi mascota')
            ->assertSee('Hermosillo sin Barreras')
            ->assertSee('Ideas para mejorar la accesibilidad y la inclusión para todas y todos.')
            ->assertSee('Cuatro formas de transformar la ciudad')
            ->assertSee('Hasta cuatro propuestas')
            ->assertSee('un máximo de cuatro por cuenta')
            ->assertSeeInOrder(['<strong>4</strong>', '<span>ganadores máximos en total</span>'], false)
            ->assertSee('ri-accessibility-line', false)
            ->assertSee('Apple')
            ->assertSee('iPad Pro')
            ->assertSee('1 ganador por categoría')
            ->assertSee('Recepción aún no habilitada')
            ->assertDontSee('iPad Pro Max');

        $response
            ->assertSee('assets/flowerflow/logo_flowerflow_transparente.png', false)
            ->assertSee('assets/flowerflow/logo_florecehermosillo_transparente.png', false)
            ->assertSee('assets/flowerflow/landing/hermosillo-atardecer.webp', false)
            ->assertSee('assets/flowerflow/landing/premio-ipad-pro.webp', false);

        $documents = [
            '01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf',
            '02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf',
            '03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf',
        ];

        foreach ($documents as $document) {
            $this->assertFileExists(public_path("documentos/2026/{$document}"));
            $response->assertSee("documentos/2026/{$document}", false);
        }

        $this->assertFileExists(public_path('assets/flowerflow/landing/hermosillo-atardecer.webp'));
        $this->assertFileExists(public_path('assets/flowerflow/landing/premio-ipad-pro.webp'));
    }

    public function test_public_flag_hides_the_landing(): void
    {
        config(['flowerflow.flags.public' => false]);

        $this->get('/')->assertNotFound();
    }

    public function test_registration_and_submission_calls_to_action_follow_their_flags(): void
    {
        $this->seedFlowerFlow();

        config([
            'flowerflow.flags.registration' => true,
            'flowerflow.flags.submissions' => true,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Crear mi cuenta')
            ->assertSee('Quiero participar')
            ->assertSee('Recepción de propuestas abierta')
            ->assertSee('href="'.url('/register').'"', false)
            ->assertDontSee('Registro próximamente');

        config([
            'flowerflow.flags.registration' => false,
            'flowerflow.flags.submissions' => false,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Registro próximamente')
            ->assertSee('Recepción aún no habilitada')
            ->assertDontSee('Crear mi cuenta')
            ->assertDontSee('Quiero participar');
    }

    public function test_landing_uses_safe_category_fallback_without_an_active_competition(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Movilidad con Flow')
            ->assertSee('Hermosillo Florece')
            ->assertSee('Mi familia, mi mascota')
            ->assertSee('Hermosillo sin Barreras')
            ->assertSee('Ideas para mejorar la movilidad')
            ->assertSee('Ideas para una ciudad más verde y sostenible')
            ->assertSee('Ideas para bienestar animal')
            ->assertSee('Ideas para mejorar la accesibilidad y la inclusión para todas y todos.')
            ->assertSee('ri-accessibility-line', false);
    }

    public function test_landing_lists_only_active_categories_and_features_hermosillo_florece_by_slug(): void
    {
        $this->seedFlowerFlow();
        $competition = Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail();
        $competition->categories()->create([
            'slug' => 'categoria-inactiva',
            'name' => 'Categoría inactiva de prueba',
            'description' => 'No debe publicarse.',
            'sort_order' => 0,
            'active' => false,
        ]);

        $response = $this->get('/')->assertOk()
            ->assertDontSee('Categoría inactiva de prueba');

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<article class="ff-category-card is-featured">\s*<span[^>]+>\s*<\/span>\s*<div>\s*<h3>Hermosillo Florece<\/h3>/s',
            $html
        );
        $this->assertStringNotContainsString('nth-child', $html);
    }

    public function test_navigation_anchors_and_faq_relationships_are_accessible(): void
    {
        $this->seedFlowerFlow();

        $this->get('/')->assertOk()
            ->assertSee('href="#categorias"', false)
            ->assertSee('id="categorias"', false)
            ->assertSee('href="#como-participar"', false)
            ->assertSee('id="como-participar"', false)
            ->assertSee('href="#requisitos"', false)
            ->assertSee('id="requisitos"', false)
            ->assertSee('href="#preguntas"', false)
            ->assertSee('id="preguntas"', false)
            ->assertSee('aria-controls="landing-navigation"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="faq-answer-1"', false)
            ->assertSee('aria-labelledby="faq-heading-1"', false)
            ->assertSee('data-bs-parent="#landing-faq"', false);
    }

    public function test_landing_chrome_does_not_replace_other_guest_pages(): void
    {
        $this->get('/login')->assertOk()
            ->assertDontSee('ff-public-header', false)
            ->assertDontSee('ff-final-cta', false)
            ->assertSee('ff-login-header', false);
    }
}
