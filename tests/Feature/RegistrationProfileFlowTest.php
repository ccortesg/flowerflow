<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class RegistrationProfileFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlowerFlow();
    }

    public function test_registration_creates_complete_profile_role_and_separate_legal_evidence(): void
    {
        $user = app(CreateNewUser::class)->create($this->validRegistrationData());

        $this->assertTrue($user->hasRole('participant'));
        $this->assertTrue($user->profile->isComplete());
        $this->assertSame('Ana María Prueba López', $user->name);
        $this->assertSame('+526621234567', $user->profile->mobile_e164);
        $this->assertTrue($user->profile->whatsapp_opt_in);
        $this->assertDatabaseHas('legal_acceptances', ['user_id' => $user->id, 'purpose' => 'registration_terms', 'document_version' => '1.0', 'accepted' => true]);
        $this->assertDatabaseHas('legal_acceptances', ['user_id' => $user->id, 'purpose' => 'registration_privacy', 'document_version' => '1.0', 'accepted' => true]);
        $this->assertDatabaseHas('legal_acceptances', ['user_id' => $user->id, 'purpose' => 'whatsapp_contact', 'accepted' => true]);
        $this->assertDatabaseHas('legal_acceptances', ['user_id' => $user->id, 'purpose' => 'future_activities', 'accepted' => true]);
        $this->assertSame(4, $user->legalAcceptances()->count());
    }

    public function test_invalid_eligibility_or_legal_acceptance_never_creates_partial_account(): void
    {
        foreach ([
            ['birth_date' => now()->subYears(17)->toDateString()],
            ['mobile_national' => '662 123'],
            ['resident_declaration' => '0'],
            ['accept_legal' => '0'],
        ] as $overrides) {
            try {
                app(CreateNewUser::class)->create([...$this->validRegistrationData(), ...$overrides]);
                $this->fail('El registro inválido debió rechazarse.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('users', 0);
                $this->assertDatabaseCount('participant_profiles', 0);
                $this->assertDatabaseCount('legal_acceptances', 0);
            }
        }
    }

    public function test_missing_active_legal_document_blocks_registration_without_partial_data(): void
    {
        LegalDocument::query()->where('code', 'privacy')->update(['active' => false]);

        try {
            app(CreateNewUser::class)->create($this->validRegistrationData());
            $this->fail('El registro sin documentos legales activos debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('accept_legal', $exception->errors());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_view_contains_mexico_phone_and_downloadable_consents(): void
    {
        if (! Route::has('register')) {
            Route::get('/registro-prueba', fn () => view('auth.register'))
                ->middleware('web')
                ->name('register');
            Route::getRoutes()->refreshNameLookups();
        }

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('México (+52)')
            ->assertSee('data-phone-number', false)
            ->assertSee('Declaro que soy mayor de 18 años')
            ->assertSee('Términos y Condiciones')
            ->assertSee('Aviso de Privacidad')
            ->assertSee('download', false)
            ->assertSee('Acepto recibir información sobre futuras actividades de FLORECE HERMOSILLO y FLOWER FLOW')
            ->assertSee('id="future_activities_opt_in" name="future_activities_opt_in" type="checkbox" value="1" checked', false);
    }

    public function test_signed_verification_link_opens_friendly_confirmation_page(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('verification.success'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->actingAs($user)->get(route('verification.success'))
            ->assertOk()
            ->assertSee('¡Tu correo fue verificado correctamente!')
            ->assertSee('Ir a mi cuenta');
    }

    public function test_panel_statuses_and_fortify_errors_are_clear_mexican_spanish(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->withSession(['status' => Fortify::PASSWORD_UPDATED])
            ->get(route('panel.account'))
            ->assertOk()
            ->assertSee('Tu contraseña se actualizó correctamente.')
            ->assertDontSee(Fortify::PASSWORD_UPDATED);

        $this->assertSame(
            'El código de autenticación en dos pasos no es válido.',
            __('The provided two factor authentication code was invalid.')
        );
    }

    /** @return array<string, string> */
    private function validRegistrationData(): array
    {
        return [
            'first_names' => 'Ana María',
            'last_names' => 'Prueba López',
            'email' => 'ana@example.test',
            'mobile_national' => '662 123 4567',
            'whatsapp_opt_in' => '1',
            'birth_date' => '1990-01-01',
            'neighborhood' => 'Centro',
            'resident_declaration' => '1',
            'accept_legal' => '1',
            'future_activities_opt_in' => '1',
            'password' => 'Aa1!aaaa',
            'password_confirmation' => 'Aa1!aaaa',
        ];
    }
}
