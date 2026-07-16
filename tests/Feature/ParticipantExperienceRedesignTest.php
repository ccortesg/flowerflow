<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Competition;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantExperienceRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlowerFlow();
    }

    public function test_participant_and_panel_login_variants_preserve_their_real_contracts(): void
    {
        config(['flowerflow.flags.registration' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ff-auth-login-page', false)
            ->assertSee('Cuenta participante')
            ->assertSee('¿Quién puede participar?')
            ->assertSee('Apple iPad Pro')
            ->assertSee('convocatoria@flowerflow.com.mx')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('href="'.url('/register').'"', false);

        $this->get(route('panel.login'))
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee('Acceso al panel')
            ->assertDontSee('¿Quién puede participar?')
            ->assertDontSee('href="'.url('/register').'"', false);

        $this->actingAs($this->participant())->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_profile_uses_real_data_completion_and_functional_edit_controls(): void
    {
        $user = $this->participant([
            'name' => 'Participante Sintética',
            'email' => 'participante-ui@example.test',
        ]);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('ff-participant-shell', false)
            ->assertSee('Mi perfil')
            ->assertSee('¡Tu perfil está completo!')
            ->assertSee('Perfil completado: 100%')
            ->assertSee('participante-ui@example.test')
            ->assertSee('Persona')
            ->assertSee('Prueba')
            ->assertSee('+52 662 123 4567')
            ->assertSee('Número registrado')
            ->assertDontSee('Número verificado')
            ->assertSee('data-profile-edit', false)
            ->assertSee('data-profile-cancel', false)
            ->assertSee('action="'.route('profile.update').'"', false)
            ->assertSee('name="mobile_national"', false)
            ->assertSee('03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf', false)
            ->assertDontSee('ri-notification', false);

        $incomplete = User::factory()->create(['email' => 'perfil-pendiente@example.test']);
        $incomplete->assignRole('participant');

        $this->actingAs($incomplete)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Tu perfil necesita atención')
            ->assertDontSee('Perfil completado: 100%');
    }

    public function test_proposals_show_only_owned_real_states_actions_and_hermosillo_time(): void
    {
        config(['flowerflow.flags.submissions' => true]);
        $owner = $this->participant(['email' => 'propietaria-ui@example.test']);
        $other = $this->participant(['email' => 'otra-ui@example.test']);
        $categories = Category::query()->orderBy('sort_order')->get();

        $draft = $this->submission($owner, $categories[0], 'Cruces seguros', 'draft', '2026-07-16 00:29:00');
        $submitted = $this->submission($owner, $categories[1], 'Parques con sombra', 'submitted', '2026-07-16 02:00:00');
        $this->submission($other, $categories[2], 'Propuesta ajena', 'draft', '2026-07-16 03:00:00');

        $this->actingAs($owner)->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Cruces seguros')
            ->assertSee('Parques con sombra')
            ->assertDontSee('Propuesta ajena')
            ->assertSee('Borrador')
            ->assertSee('Enviada')
            ->assertDontSee('En revisión')
            ->assertSee('15/07/2026 · 17:29 h')
            ->assertSee('data-submissions-browser', false)
            ->assertSee('data-submissions-search', false)
            ->assertSee('data-submissions-status', false)
            ->assertSee('href="'.route('submissions.show', $draft).'"', false)
            ->assertSee('href="'.route('submissions.edit', $draft).'"', false)
            ->assertSee('href="'.route('submissions.show', $submitted).'"', false)
            ->assertDontSee('href="'.route('submissions.edit', $submitted).'"', false);
    }

    public function test_proposal_empty_limit_and_disabled_states_never_offer_invalid_actions(): void
    {
        config(['flowerflow.flags.submissions' => true]);
        $user = $this->participant(['email' => 'limite-ui@example.test']);

        $this->actingAs($user)->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Aún no tienes propuestas registradas')
            ->assertSee('Crear mi primera propuesta')
            ->assertSee('href="'.route('submissions.create').'"', false);

        foreach (Category::query()->orderBy('sort_order')->get() as $index => $category) {
            $this->submission($user, $category, 'Propuesta '.($index + 1));
        }

        $this->actingAs($user)->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Ya alcanzaste el máximo de 3 propuestas.')
            ->assertDontSee('href="'.route('submissions.create').'"', false);

        config(['flowerflow.flags.submissions' => false]);
        $draft = $user->submissions()->firstOrFail();

        $this->actingAs($user)->get(route('submissions.index'))
            ->assertOk()
            ->assertDontSee('href="'.route('submissions.edit', $draft).'"', false);
    }

    public function test_dashboard_uses_real_participant_competition_profile_and_submission_data(): void
    {
        config(['flowerflow.flags.submissions' => true]);
        $owner = $this->participant([
            'name' => 'Nombre alterno',
            'email' => 'dashboard@example.test',
        ]);
        $owner->profile()->update([
            'first_names' => 'Ana María',
            'last_names' => 'López Díaz',
        ]);
        $other = $this->participant(['email' => 'dashboard-otra@example.test']);
        $categories = Category::query()->orderBy('sort_order')->get();

        $this->submission($owner, $categories[0], 'Borrador propio');
        $this->submission($owner, $categories[1], 'Envío propio', 'submitted', '2026-07-16 02:00:00');
        $this->submission($other, $categories[2], 'Propuesta ajena');

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ff-participant-dashboard-page', false)
            ->assertSee('Hola, Ana María López Díaz')
            ->assertSee('data-testid="dashboard-total-submissions">2', false)
            ->assertSee('data-testid="dashboard-submitted-submissions">1', false)
            ->assertSee('data-testid="dashboard-profile-state">', false)
            ->assertSee('Completo')
            ->assertSee('Máximo 3 por participante')
            ->assertSee('href="'.route('submissions.create').'"', false)
            ->assertSee('15 de agosto de 2026')
            ->assertSee('23:59 horas · Tiempo de Hermosillo')
            ->assertSeeInOrder($categories->pluck('name')->all())
            ->assertSee('Un Apple iPad Pro por categoría')
            ->assertDontSee('laptop', false)
            ->assertDontSee('iPad Pro Max', false)
            ->assertDontSee('Participa en la evaluación')
            ->assertDontSee('Propuesta ajena');
    }

    public function test_dashboard_creation_action_respects_profile_limit_and_feature_flag(): void
    {
        config(['flowerflow.flags.submissions' => true]);
        $incomplete = User::factory()->create([
            'name' => 'Cuenta pendiente',
            'email' => 'dashboard-pendiente@example.test',
        ]);
        $incomplete->assignRole('participant');

        $this->actingAs($incomplete)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Hola, Cuenta pendiente')
            ->assertSee('Pendiente')
            ->assertSee('Completar perfil')
            ->assertDontSee('href="'.route('submissions.create').'"', false);

        $atLimit = $this->participant(['email' => 'dashboard-limite@example.test']);
        foreach (Category::query()->orderBy('sort_order')->get() as $index => $category) {
            $this->submission($atLimit, $category, 'Propuesta '.($index + 1));
        }

        $this->actingAs($atLimit)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Máximo alcanzado')
            ->assertDontSee('href="'.route('submissions.create').'"', false);

        config(['flowerflow.flags.submissions' => false]);
        $available = $this->participant(['email' => 'dashboard-cerrado@example.test']);

        $this->actingAs($available)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recepción cerrada')
            ->assertDontSee('href="'.route('submissions.create').'"', false);
    }

    public function test_dashboard_handles_missing_competition_and_redirects_privileged_roles(): void
    {
        Competition::query()->update(['active' => false]);
        $participant = $this->participant(['email' => 'dashboard-sin-convocatoria@example.test']);

        $this->actingAs($participant)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No hay una convocatoria activa con fecha de cierre disponible.')
            ->assertSee('No hay categorías activas disponibles en este momento.')
            ->assertSee('Recepción cerrada')
            ->assertDontSee('href="'.route('submissions.create').'"', false);

        foreach (['admin', 'reviewer'] as $role) {
            $privileged = User::factory()->create(['email' => "$role-dashboard@example.test"]);
            $privileged->assignRole($role);

            $this->actingAs($privileged)->get(route('dashboard'))
                ->assertRedirect(route('panel.dashboard'));
        }
    }

    public function test_participant_navigation_is_reduced_across_representative_routes(): void
    {
        config(['flowerflow.flags.submissions' => true]);
        $user = $this->participant(['email' => 'navegacion-dashboard@example.test']);
        $draft = $this->submission(
            $user,
            Category::query()->orderBy('sort_order')->firstOrFail(),
            'Borrador para navegación'
        );

        $urls = [
            route('dashboard'),
            route('submissions.index'),
            route('profile.edit'),
            route('submissions.create'),
            route('submissions.edit', $draft),
            route('submissions.show', $draft),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url)->assertOk();
            $navigation = $this->participantNavigationHtml($response->getContent());

            $this->assertStringContainsString('Inicio', $navigation);
            $this->assertStringContainsString('Mis propuestas', $navigation);
            $this->assertStringContainsString('Nueva propuesta', $navigation);
            $this->assertStringContainsString('Mi perfil', $navigation);
            $this->assertStringNotContainsString('Documentos', $navigation);
            $this->assertStringNotContainsString('Preguntas frecuentes', $navigation);
            $this->assertStringNotContainsString(route('documents'), $navigation);
            $this->assertStringNotContainsString('#preguntas', $navigation);
        }
    }

    public function test_public_documents_faq_pdfs_and_panel_navigation_are_preserved(): void
    {
        $this->get(route('documents'))
            ->assertOk()
            ->assertSee('01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf', false)
            ->assertSee('02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf', false)
            ->assertSee('03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf', false);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('id="preguntas"', false)
            ->assertSee('Preguntas frecuentes');

        $admin = User::factory()->create(['email' => 'panel-preservado@example.test']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('panel.submissions.index').'"', false)
            ->assertDontSee('data-testid="participant-menu"', false);
    }

    private function participantNavigationHtml(string $html): string
    {
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $menus = $xpath->query('//*[@data-testid="participant-menu"]');
        $navigation = '';

        foreach ($menus ?: [] as $menu) {
            $navigation .= $dom->saveHTML($menu);
        }

        $this->assertNotSame('', $navigation, 'No se encontró el menú participante compartido.');

        return $navigation;
    }

    private function submission(
        User $user,
        Category $category,
        string $title,
        string $status = 'draft',
        ?string $updatedAt = null,
    ): Submission {
        return Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'participation_type' => 'individual',
            'title' => $title,
            'summary' => 'Resumen sintético para validar la interfaz.',
            'description_html' => '<p>Contenido sintético.</p>',
            'description_text' => 'Contenido sintético.',
            'status' => $status,
            'folio' => $status === 'submitted' ? 'HMO26-000001' : null,
            'submitted_at' => $status === 'submitted' ? $updatedAt : null,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
    }
}
