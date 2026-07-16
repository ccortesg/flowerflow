<?php

namespace Tests\Feature;

use App\Models\Category;
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
