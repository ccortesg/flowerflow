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

    public function test_landing_contains_voting_content_assets_and_access_to_legal_documents(): void
    {
        $this->seedFlowerFlow();

        $response = $this->get('/')->assertOk()
            ->assertSeeText('¡La gente elige!')
            ->assertSee('Votación ciudadana · Hermosillo 2026')
            ->assertSee('Vota por tu proyecto favorito. Tu opinión cuenta. Hagamos florecer a Hermosillo.')
            ->assertSee('Cómo se eligen los ganadores')
            ->assertSee('Tu voto puede hacer la diferencia')
            ->assertSee('Elige tu proyecto favorito y apoya con tu voto las ideas ciudadanas para transformar Hermosillo.')
            ->assertSee('Reconocemos las mejores ideas')
            ->assertSee('Los 2 proyectos con más votos serán los ganadores')
            ->assertSee('Meta Quest 3S')
            ->assertSee('Beats Solo 4')
            ->assertDontSee('Premio aún por definir')
            ->assertSee('Tu opinión cuenta')
            ->assertSee('Elige tu proyecto favorito')
            ->assertSee('FUNXT, A.C.')
            ->assertSee('FUN110208BT0')
            ->assertSee('href="'.route('documents').'"', false)
            ->assertDontSee('Recepción aún no habilitada')
            ->assertDontSee('Recepción de propuestas abierta');

        foreach ([
            'assets/flowerflow/logo_flowerflow_transparente.png',
            'assets/flowerflow/logo_florecehermosillo_transparente.png',
            'assets/flowerflow/landing/voting-illustration-640.webp',
            'assets/flowerflow/landing/voting-illustration-1024.webp',
            'assets/flowerflow/landing/prize-metaquest3s-480.webp',
            'assets/flowerflow/landing/prize-metaquest3s-960.webp',
            'assets/flowerflow/landing/prize-beats-solo4-320.webp',
            'assets/flowerflow/landing/prize-beats-solo4-640.webp',
        ] as $asset) {
            $response->assertSee($asset, false);
            $this->assertFileExists(public_path($asset));
        }

        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $awards = $xpath->query('//*[@id="ganadores"]//ol/li');
        $this->assertSame(2, $awards->length);
        foreach ([['Primer lugar', 'Meta Quest 3S', 'prize-metaquest3s-960.webp'], ['Segundo lugar', 'Beats Solo 4', 'prize-beats-solo4-640.webp']] as $index => [$place, $name, $image]) {
            $award = $awards->item($index);
            $this->assertSame($place, $xpath->evaluate('string(p[1])', $award));
            $this->assertSame($name, $xpath->evaluate('string(h3)', $award));
            $this->assertStringEndsWith($image, $xpath->evaluate('string(.//img/@src)', $award));
            $this->assertNotEmpty($xpath->evaluate('string(.//img/@alt)', $award));
            $this->assertSame('lazy', $xpath->evaluate('string(.//img/@loading)', $award));
        }
        $response->assertSee('Audífonos inalámbricos');

        $documents = $this->get(route('documents'))->assertOk();
        foreach (['mechanics', 'terms', 'privacy'] as $type) {
            $path = config("flowerflow.legal_documents.{$type}.path");
            $this->assertFileExists(public_path($path));
            $documents->assertSee($path, false);
            if ($type !== 'mechanics') {
                $response->assertSee($path, false);
            }
        }
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
                    ->assertSee('Los 2 proyectos con más votos serán los ganadores')
                    ->assertSee('Meta Quest 3S')
                    ->assertSee('Beats Solo 4')
                    ->assertDontSee('Premio aún por definir')
                    ->assertDontSee('id="categorias"', false)
                    ->assertDontSee('id="preguntas"', false)
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

    public function test_voting_content_is_available_without_an_active_competition(): void
    {
        $this->assertFalse(Competition::query()->where('active', true)->exists());

        $this->get('/')->assertOk()
            ->assertSee('Los 2 proyectos con más votos serán los ganadores')
            ->assertSee('Meta Quest 3S')
            ->assertSee('Beats Solo 4')
            ->assertDontSee('Premio aún por definir')
            ->assertSee('Votar')
            ->assertDontSee('id="categorias"', false);
    }

    public function test_submission_sections_and_previous_prize_are_absent_from_the_rendered_landing(): void
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

        $response = $this->get('/')->assertOk();
        foreach ([
            'Cuatro formas de transformar la ciudad',
            'Un proceso sencillo',
            'Antes de comenzar',
            'Consulta antes de participar',
            'Resolvemos tus dudas',
            'Preguntas frecuentes',
            'Categoría inactiva de prueba',
            'Movilidad con Flow',
            'Mi familia, mi mascota',
            'Hermosillo sin Barreras',
            'Puedes participar por tu cuenta',
            'Finaliza tu propuesta antes',
            'Apple',
            'iPad',
            'premio-ipad-pro.webp',
            'ganador máximo por categoría',
            'ganadores máximos en total',
            'un premio por categoría',
            'Una categoría puede declararse desierta',
        ] as $hiddenText) {
            $response->assertDontSee($hiddenText);
        }

        foreach (['categorias', 'como-participar', 'requisitos', 'documentos', 'preguntas', 'landing-faq'] as $hiddenId) {
            $response->assertDontSee('id="'.$hiddenId.'"', false);
        }
    }

    public function test_public_navigation_links_resolve_to_visible_sections_and_document_routes(): void
    {
        $this->seedFlowerFlow();
        $landing = $this->get('/')->assertOk()
            ->assertSee('href="#ganadores"', false)
            ->assertSee('id="ganadores"', false)
            ->assertSee('aria-controls="landing-navigation"', false)
            ->assertSee('aria-expanded="false"', false);
        $document = new DOMDocument;
        @$document->loadHTML($landing->getContent());
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//a[starts-with(@href, "#")]') as $link) {
            $target = $document->getElementById(substr($link->getAttribute('href'), 1));
            $this->assertNotNull($target, 'Missing target for '.$link->getAttribute('href'));
            $this->assertFalse($target->hasAttribute('hidden'));
        }

        foreach (['/', '/login', '/documentos'] as $path) {
            $response = $this->get($path)->assertOk()
                ->assertSee('Ganadores')
                ->assertSee('href="'.route('documents').'"', false);
            if ($path !== '/') {
                $response->assertSee('href="'.route('landing').'#ganadores"', false);
            }
            foreach (['categorias', 'como-participar', 'requisitos', 'preguntas', 'documentos'] as $hiddenId) {
                $response->assertDontSee('#'.$hiddenId, false);
            }
        }
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
