<?php

namespace App\Services;

use App\Enums\SubmissionFinalizationMode;
use App\Models\Submission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class SubmissionFinalizationEligibility
{
    public function assertEligible(Submission $submission, User $actor, SubmissionFinalizationMode $mode): void
    {
        $submission->loadMissing(['competition', 'team']);
        $this->assertOpen($submission);
        $this->assertMinimumContent($submission);

        if ($mode === SubmissionFinalizationMode::Administrative) {
            $this->assertExactRole($actor, 'admin', 'Sólo una cuenta administrativa autorizada puede registrar esta excepción.');

            return;
        }

        $owner = $submission->user()->with('profile')->firstOrFail();
        $errors = [];
        if ($actor->id !== $owner->id) {
            $errors['owner'] = 'Sólo la persona representante puede enviar la propuesta.';
        }
        if (! $owner->hasVerifiedEmail()) {
            $errors['email'] = 'Debes verificar tu correo.';
        }
        if (! $owner->profile?->isComplete()) {
            $errors['profile'] = 'Completa el perfil de elegibilidad.';
        }

        $roles = $owner->getRoleNames();
        if ($roles->count() !== 1 || $roles->first() !== 'participant') {
            $errors['role'] = 'La cuenta propietaria no tiene un rol participante válido.';
        }

        if ($submission->participation_type === 'team'
            && (! $submission->team
                || ! $submission->team->eligibility_declared_at
                || $submission->team->members()->count() > config('flowerflow.limits.team_members'))) {
            $errors['team'] = 'El equipo debe respetar el máximo de cinco integrantes incluyendo representante.';
        }

        if ($mode === SubmissionFinalizationMode::Participant
            && ! $submission->files()->where('kind', 'document')->exists()) {
            $errors['files'] = 'Adjunta al menos un archivo de propuesta.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function assertOpen(Submission $submission): void
    {
        $submission->loadMissing('competition');
        $competition = $submission->competition;
        $timezone = (string) config('flowerflow.timezone');
        $configuredClose = CarbonImmutable::parse((string) config('flowerflow.submissions_close_at'), $timezone)->utc();

        $errors = [];
        if (! config('flowerflow.flags.submissions')) {
            $errors['submissions'] = 'La recepción de propuestas no está habilitada.';
        }
        if (! $competition || ! $competition->active) {
            $errors['competition'] = 'La convocatoria de la propuesta no está activa.';
        } elseif ($competition->source_timezone !== $timezone
            || ! $competition->closes_at
            || ! $competition->closes_at->utc()->equalTo($configuredClose)) {
            $errors['deadline'] = 'El plazo configurado no coincide con la convocatoria. La operación se detuvo de forma segura.';
        } elseif (now()->isAfter($configuredClose)) {
            $errors['deadline'] = 'La convocatoria ya cerró.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function assertMinimumContent(Submission $submission): void
    {
        $errors = [];
        if (blank(trim((string) $submission->title))) {
            $errors['title'] = 'El título del proyecto es obligatorio.';
        }
        if (blank(trim((string) $submission->summary))) {
            $errors['summary'] = 'El resumen del proyecto es obligatorio.';
        }
        if (blank(trim((string) $submission->description_text))) {
            $errors['description'] = 'La descripción de la propuesta es obligatoria.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertExactRole(User $user, string $role, string $message): void
    {
        $roles = $user->getRoleNames();
        if ($roles->count() !== 1 || $roles->first() !== $role) {
            throw ValidationException::withMessages(['role' => $message]);
        }
    }
}
