<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Mail\SubmissionReceived;
use App\Models\Category;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class AuthMailHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlowerFlow();
    }

    public function test_backend_accepts_eight_character_password_and_rejects_seven(): void
    {
        $user = app(CreateNewUser::class)->create([
            ...$this->registrationData(),
            'email' => 'ocho@example.test',
            'password' => 'Aa1!aaaa',
            'password_confirmation' => 'Aa1!aaaa',
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        try {
            app(CreateNewUser::class)->create([
                ...$this->registrationData(),
                'email' => 'siete@example.test',
                'password' => 'Aa1!aaa',
                'password_confirmation' => 'Aa1!aaa',
            ]);
            $this->fail('La contraseña de siete caracteres debió rechazarse.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('password', $exception->errors());
        }
    }

    public function test_password_views_include_accessible_visual_requirements(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'token-sintetico',
            'email' => 'persona@example.test',
        ]));

        $response->assertOk()
            ->assertSee('data-password-validation', false)
            ->assertSee('minlength="8"', false)
            ->assertSee('Al menos una letra mayúscula')
            ->assertSee('data-password-match', false)
            ->assertDontSee('12 caracteres');
    }

    public function test_auth_notifications_are_queued_encrypted_and_rendered_in_spanish(): void
    {
        Notification::fake();
        $user = User::factory()->create(['name' => 'María Prueba']);

        $user->sendEmailVerificationNotification();
        $user->sendPasswordResetNotification('token-sintetico');

        Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user): bool {
            $html = $notification->toMail($user)->render();

            $this->assertInstanceOf(ShouldQueueAfterCommit::class, $notification);
            $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);
            $this->assertSame('database', $notification->connection);
            $this->assertSame('default', $notification->queue);
            $this->assertSame(4, $notification->tries);
            $this->assertSame([60, 300, 900], $notification->backoff);
            $this->assertStringContainsString('Verifica tu correo electrónico', $html);
            $this->assertStringContainsString('Flower Flow', $html);
            $this->assertStringContainsString('Florece Hermosillo', $html);
            $this->assertStringNotContainsString('Verify Email Address', $html);

            return true;
        });

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $html = $notification->toMail($user)->render();

            $this->assertInstanceOf(ShouldQueueAfterCommit::class, $notification);
            $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);
            $this->assertStringContainsString('Restablece tu contraseña', $html);
            $this->assertStringContainsString('vence en 60 minutos', $html);
            $this->assertStringNotContainsString('Reset Password Notification', $html);

            return true;
        });
    }

    public function test_submission_receipt_uses_branded_html_and_plain_text(): void
    {
        $user = $this->participant();
        $category = Category::query()->firstOrFail();
        $submission = Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'participation_type' => 'individual',
            'title' => 'Parque de prueba',
            'summary' => 'Resumen sintético',
            'description_html' => '<p>Detalle</p>',
            'description_text' => 'Detalle',
            'status' => 'submitted',
            'folio' => 'HMO26-000001',
            'submitted_at' => '2026-07-16 02:00:00',
        ]);

        $mail = new SubmissionReceived($submission);
        $html = $mail->render();

        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $mail);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $mail);
        $this->assertSame('database', $mail->connection);
        $this->assertSame('default', $mail->queue);
        $this->assertSame(4, $mail->tries);
        $this->assertSame([60, 300, 900], $mail->backoff);
        $this->assertStringContainsString('Recibimos tu propuesta', $html);
        $this->assertStringContainsString('Flower Flow', $html);
        $this->assertStringContainsString('Florece Hermosillo', $html);
        $this->assertSame('mail.submission-received-text', $mail->content()->text);
    }

    public function test_verification_dispatch_failure_returns_warning_instead_of_500(): void
    {
        $user = User::factory()->unverified()->create();
        $this->mock(Dispatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andThrow(new RuntimeException('Falla sintética de cola'));
        });

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('warning', fn (string $message): bool => str_contains($message, 'intenta reenviarlo'));
    }

    public function test_password_reset_dispatch_failure_returns_warning_instead_of_500(): void
    {
        $user = User::factory()->create();
        $this->mock(Dispatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andThrow(new RuntimeException('Falla sintética de cola'));
        });

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('warning', fn (string $message): bool => str_contains($message, 'Inténtalo de nuevo'));
    }

    public function test_submission_receipt_dispatch_failure_preserves_submission_and_returns_warning(): void
    {
        $user = $this->participant();
        $category = Category::query()->firstOrFail();
        $submission = Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'participation_type' => 'individual',
            'title' => 'Acuse resiliente',
            'summary' => 'Resumen sintético',
            'description_html' => '<p>Detalle</p>',
            'description_text' => 'Detalle',
            'status' => 'submitted',
            'folio' => 'HMO26-000099',
            'submitted_at' => now('UTC'),
        ]);

        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Falla sintética de cola'));

        $this->actingAs($user)
            ->post(route('submissions.confirmation.resend', $submission))
            ->assertRedirect()
            ->assertSessionHas('warning', fn (string $message): bool => str_contains($message, 'Conserva tu folio'));

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => 'submitted',
            'folio' => 'HMO26-000099',
        ]);
    }

    /** @return array<string, string> */
    private function registrationData(): array
    {
        return [
            'first_names' => 'Cuenta',
            'last_names' => 'Prueba',
            'mobile_national' => '662 123 4567',
            'whatsapp_opt_in' => '1',
            'birth_date' => '1990-01-01',
            'neighborhood' => 'Centro',
            'resident_declaration' => '1',
            'accept_legal' => '1',
            'future_activities_opt_in' => '1',
        ];
    }
}
