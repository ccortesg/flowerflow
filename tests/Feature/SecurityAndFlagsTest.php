<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityAndFlagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlowerFlow();
    }

    public function test_safe_defaults_disable_registration_and_submission_mutations(): void
    {
        config(['flowerflow.flags.registration' => false, 'flowerflow.flags.submissions' => false]);
        $user = $this->participant();

        $this->get('/register')->assertNotFound();
        $this->actingAs($user)->get(route('submissions.create'))->assertStatus(503);
    }

    public function test_private_submission_cannot_be_read_horizontally(): void
    {
        $owner = $this->participant();
        $other = $this->participant();
        $category = Category::first();
        $submission = Submission::create([
            'competition_id' => $category->competition_id, 'category_id' => $category->id, 'user_id' => $owner->id,
            'participation_type' => 'individual', 'title' => 'Privada', 'summary' => 'Contenido privado',
            'description_html' => '<p>Privada</p>', 'description_text' => 'Privada',
        ]);

        $this->actingAs($other)->get(route('submissions.show', $submission))->assertForbidden();
        $this->actingAs($owner)->get(route('submissions.show', $submission))->assertOk();
    }

    public function test_deadline_is_inclusive_in_hermosillo_timezone(): void
    {
        config(['flowerflow.flags.submissions' => true]);
        $user = $this->participant();

        Carbon::setTestNow(Carbon::parse('2026-08-15 23:59:59', 'America/Hermosillo'));
        $this->actingAs($user)->get(route('submissions.create'))->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-08-16 00:00:00', 'America/Hermosillo'));
        $this->actingAs($user)->get(route('submissions.create'))->assertStatus(503);
        Carbon::setTestNow();
    }

    public function test_security_headers_are_applied(): void
    {
        $contentSecurityPolicy = "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; frame-src https://www.youtube-nocookie.com";

        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy', $contentSecurityPolicy);
    }

    public function test_utc_submission_time_is_presented_in_hermosillo(): void
    {
        $user = $this->participant();
        $category = Category::query()->firstOrFail();
        $submission = Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'participation_type' => 'individual',
            'title' => 'Conversión horaria',
            'summary' => 'Valida que UTC se presente en la zona de la convocatoria.',
            'description_html' => '<p>Detalle</p>',
            'description_text' => 'Detalle',
            'status' => 'submitted',
            'submitted_at' => '2026-07-16 00:29:09',
        ]);

        $this->actingAs($user)->get(route('submissions.show', $submission))
            ->assertOk()
            ->assertSee('15/07/2026 17:29');
    }

    public function test_mysql_session_uses_utc(): void
    {
        $timezone = DB::selectOne('select @@session.time_zone as timezone');

        $this->assertSame('+00:00', $timezone->timezone);
    }

    public function test_interface_uses_mexican_spanish_and_hermosillo_business_timezone(): void
    {
        $this->assertSame('es_MX', app()->getLocale());
        $this->assertSame('America/Hermosillo', config('flowerflow.timezone'));
        $this->get('/')->assertSee('lang="es-MX"', false);

        $message = Validator::make([], ['email' => ['required']])->errors()->first('email');
        $this->assertSame('El campo correo electrónico es obligatorio.', $message);
    }
}
