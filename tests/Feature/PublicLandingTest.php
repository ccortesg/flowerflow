<?php

namespace Tests\Feature;

use App\Models\Competition;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_contains_critical_content_assets_and_legal_downloads(): void
    {
        $this->seedFlowerFlow();

        $response = $this->get('/')->assertOk()
            ->assertSeeText('¡La gente elige!')
            ->assertSee('Votación ciudadana · Hermosillo 2026')
            ->assertSee('Vota por tu proyecto favorito. Tu opinión cuenta. Hagamos florecer a Hermosillo.')
            ->assertSee('Consulta la convocatoria')
            ->assertSee('Tu opinión cuenta')
            ->assertSee('Elige tu proyecto favorito')
            ->assertSee('Finaliza tu propuesta antes del 23 de agosto de 2026 a las 23:59.')
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
            ->assertSee('ganador máximo por categoría')
            ->assertSee('FUNXT, A.C.')
            ->assertSee('FUN110208BT0')
            ->assertSee('Versión 1.1')
            ->assertDontSee('Recepción aún no habilitada')
            ->assertDontSee('Recepción de propuestas abierta')
            ->assertDontSee('iPad Pro Max');

        $response
            ->assertSee('assets/flowerflow/logo_flowerflow_transparente.png', false)
            ->assertSee('assets/flowerflow/logo_florecehermosillo_transparente.png', false)
            ->assertSee('assets/flowerflow/landing/voting-illustration-640.webp', false)
            ->assertSee('assets/flowerflow/landing/voting-illustration-1024.webp', false)
            ->assertSee('assets/flowerflow/landing/premio-ipad-pro.webp', false);

        $documents = [
            '01_Mecanica_Convocatoria_Hermosillo_Florece_2026_v1.1.pdf',
            '02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026_v1.1.pdf',
            '03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026_v1.1.pdf',
        ];

        foreach ($documents as $document) {
            $this->assertFileExists(public_path("documentos/2026/{$document}"));
            $response->assertSee("documentos/2026/{$document}", false);
        }

        $this->assertFileExists(public_path('assets/flowerflow/landing/voting-illustration-640.webp'));
        $this->assertFileExists(public_path('assets/flowerflow/landing/voting-illustration-1024.webp'));
        $this->assertFileExists(public_path('assets/flowerflow/landing/premio-ipad-pro.webp'));
    }

    public function test_public_flag_hides_the_landing(): void
    {
        config(['flowerflow.flags.public' => false]);

        $this->get('/')->assertNotFound();
    }

    public function test_voting_calls_to_action_remain_available_independently_of_registration_and_submission_flags(): void
    {
        $this->seedFlowerFlow();

        foreach ([false, true] as $registration) {
            foreach ([false, true] as $submissions) {
                config([
                    'flowerflow.flags.registration' => $registration,
                    'flowerflow.flags.submissions' => $submissions,
                ]);

                $response = $this->get('/')->assertOk()
                    ->assertSee('Votar')
                    ->assertSee('href="'.route('login').'"', false)
                    ->assertDontSee('Recepción de propuestas abierta')
                    ->assertDontSee('Recepción aún no habilitada')
                    ->assertDontSee('próximamente')
                    ->assertDontSee('Próximamente')
                    ->assertDontSee('Crear mi cuenta')
                    ->assertDontSee('Quiero participar');

                $this->assertSame(4, substr_count($response->getContent(), 'data-voting-trigger'));
            }
        }
    }

    public function test_voting_has_one_shared_lazy_modal_and_functional_external_links(): void
    {
        $formUrl = 'https://forms.gle/r3jj7m8aq4GK3gSo7';
        $embedUrl = 'https://docs.google.com/forms/d/e/1FAIpQLScDanRPqq_iWsVx3NqT9fjTYVLmJqjYXMBstWZw1F7sdkQ1UA/viewform?embedded=true';
        $this->assertSame($formUrl, config('flowerflow.voting.form_url'));
        $this->assertSame($embedUrl, config('flowerflow.voting.embed_url'));

        $response = $this->get('/')->assertOk()
            ->assertSee('Google solicita iniciar sesión para responder.')
            ->assertSee('Abrir en Google')
            ->assertSee('public-voting-', false);
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);

        $this->assertSame(1, $xpath->query('//body/div[@id="public-voting-modal"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="public-voting-title"]')->length);
        $this->assertSame(1, $xpath->query('//iframe[@id="public-voting-frame"]')->length);
        $this->assertSame(0, $xpath->query('//iframe[@id="public-voting-frame"]/@src')->length);
        $this->assertSame($embedUrl, $xpath->evaluate('string(//iframe[@id="public-voting-frame"]/@data-src)'));
        $this->assertNotEmpty($xpath->evaluate('string(//iframe[@id="public-voting-frame"]/@title)'));
        $this->assertSame('public-voting-title', $xpath->evaluate('string(//*[@id="public-voting-modal"]/@aria-labelledby)'));
        $this->assertSame('public-voting-description', $xpath->evaluate('string(//*[@id="public-voting-modal"]/@aria-describedby)'));
        $this->assertSame(4, $xpath->query('//a[@data-voting-trigger]')->length);
        $links = $xpath->query('//a[@data-voting-trigger] | //*[@id="public-voting-modal"]//a');
        $this->assertSame(5, $links->length);
        foreach ($links as $link) {
            $this->assertSame($formUrl, $link->getAttribute('href'));
            $this->assertSame('_blank', $link->getAttribute('target'));
            $this->assertSame('noopener noreferrer', $link->getAttribute('rel'));
        }
    }

    public function test_authenticated_landing_keeps_the_modal_outside_the_account_shell(): void
    {
        $this->seedFlowerFlow();
        $response = $this->actingAs($this->participant())->get('/')->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);

        $this->assertSame(1, $xpath->query('//body/div[@id="public-voting-modal"]')->length);
        $this->assertSame(2, $xpath->query('//a[@data-voting-trigger]')->length);
        $this->get(route('dashboard'))->assertOk()->assertDontSee('public-voting-', false);
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

    public function test_landing_lists_only_active_categories_and_features_alternating_categories_by_slug(): void
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
        $this->assertMatchesRegularExpression(
            '/<article class="ff-category-card is-featured">\s*<span[^>]+>\s*<\/span>\s*<div>\s*<h3>Hermosillo sin Barreras<\/h3>/s',
            $html
        );
        $this->assertSame(2, substr_count($html, 'ff-category-card is-featured'));
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
            ->assertDontSee('public-voting-', false)
            ->assertSee('ff-login-header', false);
    }
}
