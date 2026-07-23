<?php

namespace App\Actions;

use App\Mail\SubmissionReceived;
use App\Models\LegalDocument;
use App\Models\Submission;
use App\Models\User;
use App\Services\ResilientMailDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSubmission
{
    public function __construct(
        private ResilientMailDispatcher $mailDispatcher,
        private EnsureEligibilityReview $ensureEligibilityReview
    ) {}

    public function execute(Submission $submission, User $actor, array $acceptances, ?string $idempotencyKey): Submission
    {
        $result = DB::transaction(function () use ($submission, $actor, $idempotencyKey): Submission {
            $locked = Submission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($locked->status === 'submitted') {
                return $locked;
            }

            $this->assertEligible($locked, $actor);

            $locked->forceFill([
                'status' => 'submitted',
                'submitted_at' => now('UTC'),
                'folio' => sprintf('HMO26-%06d', $locked->id),
                'submission_idempotency_key' => $idempotencyKey ?: 'submission-'.$locked->public_id,
            ])->save();

            $locked->load(['category', 'competition', 'team.members', 'files', 'externalLinks', 'user.profile']);
            $snapshot = [
                'schema_version' => 1,
                'captured_at_utc' => now('UTC')->toIso8601String(),
                'submission' => $locked->only([
                    'public_id', 'folio', 'participation_type', 'title', 'summary',
                    'description_delta', 'description_html', 'description_text', 'submitted_at',
                ]),
                'competition' => $locked->competition->only(['public_id', 'slug', 'name', 'closes_at', 'source_timezone']),
                'category' => $locked->category->only(['public_id', 'slug', 'name']),
                'participant' => [
                    'public_id' => $actor->public_id,
                    'email' => $actor->email,
                    'profile' => $actor->profile?->only([
                        'first_names', 'last_names', 'mobile_e164', 'whatsapp_opt_in', 'birth_date', 'neighborhood',
                    ]),
                ],
                'team' => $locked->team?->toArray(),
                'files' => $locked->files->map->only([
                    'public_id', 'kind', 'original_name', 'mime_type', 'extension', 'size_bytes', 'sha256',
                ])->all(),
                'external_links' => $locked->externalLinks->map->only(['kind', 'url', 'normalized_host'])->all(),
            ];

            $version = $locked->versions()->create(['version' => 1, 'snapshot' => $snapshot, 'created_at' => now('UTC')]);
            $locked->events()->create([
                'actor_user_id' => $actor->id,
                'event' => 'submitted',
                'metadata' => ['idempotency_key' => $locked->submission_idempotency_key],
                'created_at' => now('UTC'),
            ]);

            $documents = LegalDocument::query()->where('active', true)->get()->keyBy('code');
            $purposes = [
                'call_rules' => ['accepted' => true, 'document' => 'mechanics'],
                'terms' => ['accepted' => true, 'document' => 'terms'],
                'privacy' => ['accepted' => true, 'document' => 'privacy'],
            ];

            foreach ($purposes as $purpose => $meta) {
                $document = $documents->get($meta['document']);
                $actor->legalAcceptances()->create([
                    'legal_document_id' => $document?->id,
                    'purpose' => $purpose,
                    'document_version' => $document?->version ?? '1.0',
                    'accepted' => $meta['accepted'],
                    'accepted_at' => now('UTC'),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'context' => ['submission_public_id' => $locked->public_id],
                ]);
            }

            if (config('flowerflow.flags.admissibility_review')) {
                $this->ensureEligibilityReview->execute($locked, $version, $actor);
            }

            DB::afterCommit(fn () => $this->mailDispatcher->queue(
                $actor,
                new SubmissionReceived($locked),
                'Tu propuesta quedó registrada, pero no pudimos programar el correo de confirmación. Conserva el folio y vuelve a intentarlo desde la propuesta más tarde.'
            ));

            return $locked;
        }, 3);

        return $result->fresh(['category', 'competition']);
    }

    private function assertEligible(Submission $submission, User $actor): void
    {
        $profile = $actor->profile;
        $closesAt = CarbonImmutable::parse(config('flowerflow.submissions_close_at'), config('flowerflow.timezone'));

        $errors = [];
        if (! $actor->hasVerifiedEmail()) {
            $errors['email'] = 'Debes verificar tu correo.';
        }
        if (! $profile?->isComplete()) {
            $errors['profile'] = 'Completa el perfil de elegibilidad.';
        }
        if ($actor->hasAnyRole(['admin', 'reviewer'])) {
            $errors['role'] = 'El personal organizador o evaluador no es elegible.';
        }
        if (now()->isAfter($closesAt)) {
            $errors['deadline'] = 'La convocatoria ya cerró.';
        }
        if ($submission->participation_type === 'team' && (! $submission->team || ! $submission->team->eligibility_declared_at || $submission->team->members()->count() > config('flowerflow.limits.team_members'))) {
            $errors['team'] = 'El equipo debe respetar el máximo de cinco integrantes incluyendo representante.';
        }
        if (blank($submission->description_text)) {
            $errors['description'] = 'La descripción de la propuesta es obligatoria.';
        }
        if (! $submission->files()->where('kind', 'document')->exists()) {
            $errors['files'] = 'Adjunta al menos un archivo de propuesta.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
