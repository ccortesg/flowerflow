<?php

namespace App\Actions;

use App\Enums\SubmissionFinalizationMode;
use App\Mail\SubmissionAdministrativelyFinalized;
use App\Mail\SubmissionReceived;
use App\Models\LegalDocument;
use App\Models\Submission;
use App\Models\SubmissionReminder;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ResilientMailDispatcher;
use App\Services\SubmissionFinalizationEligibility;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSubmission
{
    public function __construct(
        private ResilientMailDispatcher $mailDispatcher,
        private EnsureEligibilityReview $ensureEligibilityReview,
        private SubmissionFinalizationEligibility $eligibility,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(Submission $submission, User $actor, array $acceptances, ?string $idempotencyKey): Submission
    {
        return $this->finalize(
            $submission,
            $actor,
            SubmissionFinalizationMode::Participant,
            $acceptances,
            $idempotencyKey,
        );
    }

    public function executeFromReminder(Submission $submission, SubmissionReminder $reminder, array $acceptances): Submission
    {
        $owner = $submission->user()->firstOrFail();

        return $this->finalize(
            $submission,
            $owner,
            SubmissionFinalizationMode::SignedReminder,
            $acceptances,
            'reminder-'.$reminder->public_id,
            $reminder,
        );
    }

    public function executeAdministratively(Submission $submission, User $actor, string $reason): Submission
    {
        if (! $actor->hasExactRoles(['admin']) || ! $actor->can('administratively finalize submissions')) {
            throw ValidationException::withMessages(['role' => 'La cuenta no tiene permiso para registrar esta excepción.']);
        }

        return $this->finalize(
            $submission,
            $actor,
            SubmissionFinalizationMode::Administrative,
            [],
            'administrative-'.$submission->public_id,
            administrativeReason: trim($reason),
        );
    }

    private function finalize(
        Submission $submission,
        User $actor,
        SubmissionFinalizationMode $mode,
        array $acceptances,
        ?string $idempotencyKey,
        ?SubmissionReminder $reminder = null,
        ?string $administrativeReason = null,
    ): Submission {
        $result = DB::transaction(function () use (
            $submission,
            $actor,
            $mode,
            $acceptances,
            $idempotencyKey,
            $reminder,
            $administrativeReason,
        ): Submission {
            $locked = Submission::query()->lockForUpdate()->findOrFail($submission->id);
            $lockedReminder = $reminder
                ? SubmissionReminder::query()->lockForUpdate()->findOrFail($reminder->id)
                : null;

            if ($locked->status === 'submitted') {
                if ($mode === SubmissionFinalizationMode::Administrative) {
                    $existingMode = data_get(
                        $locked->versions()->orderByDesc('version')->first()?->snapshot,
                        'finalization.mode',
                    );
                    if ($existingMode !== SubmissionFinalizationMode::Administrative->value) {
                        throw ValidationException::withMessages([
                            'submission' => 'La propuesta ya fue enviada mediante un flujo distinto y no puede registrarse como excepción administrativa.',
                        ]);
                    }
                }
                if ($lockedReminder && ! $lockedReminder->consumed_at) {
                    $lockedReminder->forceFill(['consumed_at' => now('UTC')])->save();
                }

                return $locked;
            }

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages(['submission' => 'La propuesta ya no está disponible para envío.']);
            }

            if ($mode === SubmissionFinalizationMode::SignedReminder) {
                $this->assertReminderUsable($locked, $lockedReminder);
            }
            if ($mode !== SubmissionFinalizationMode::Administrative) {
                $this->assertAcceptances($acceptances);
            }
            if ($mode === SubmissionFinalizationMode::Administrative
                && (mb_strlen((string) $administrativeReason) < 20 || mb_strlen((string) $administrativeReason) > 1000)) {
                throw ValidationException::withMessages(['reason' => 'La razón administrativa debe tener entre 20 y 1,000 caracteres.']);
            }

            $this->eligibility->assertEligible($locked, $actor, $mode);
            $now = now('UTC');

            $locked->forceFill([
                'status' => 'submitted',
                'submitted_at' => $now,
                'folio' => sprintf('HMO26-%06d', $locked->id),
                'submission_idempotency_key' => $idempotencyKey ?: 'submission-'.$locked->public_id,
            ])->save();

            $locked->load(['category', 'competition', 'team.members', 'files', 'externalLinks', 'user.profile']);
            $owner = $locked->user;
            $snapshot = [
                'schema_version' => 1,
                'captured_at_utc' => $now->toIso8601String(),
                'submission' => $locked->only([
                    'public_id', 'folio', 'participation_type', 'title', 'summary',
                    'description_delta', 'description_html', 'description_text', 'submitted_at',
                ]),
                'competition' => $locked->competition->only(['public_id', 'slug', 'name', 'closes_at', 'source_timezone']),
                'category' => $locked->category->only(['public_id', 'slug', 'name']),
                'participant' => [
                    'public_id' => $owner->public_id,
                    'email' => $owner->email,
                    'profile' => $owner->profile?->only([
                        'first_names', 'last_names', 'mobile_e164', 'whatsapp_opt_in', 'birth_date', 'neighborhood',
                    ]),
                ],
                'team' => $locked->team?->toArray(),
                'files' => $locked->files->map->only([
                    'public_id', 'kind', 'original_name', 'mime_type', 'extension', 'size_bytes', 'sha256',
                ])->all(),
                'external_links' => $locked->externalLinks->map->only(['kind', 'url', 'normalized_host'])->all(),
                'finalization' => [
                    'mode' => $mode->value,
                    'actor_user_id' => $actor->id,
                    'attachment_requirement_waived' => $mode !== SubmissionFinalizationMode::Participant,
                    'requirements_waived' => match ($mode) {
                        SubmissionFinalizationMode::Participant => [],
                        SubmissionFinalizationMode::SignedReminder => ['document_attachment'],
                        SubmissionFinalizationMode::Administrative => [
                            'document_attachment',
                            'verified_participant_email',
                            'participant_profile',
                            'team_eligibility',
                            'submission_legal_acceptances',
                        ],
                    },
                    'administrative_reason' => $mode === SubmissionFinalizationMode::Administrative
                        ? $administrativeReason
                        : null,
                ],
            ];

            $version = $locked->versions()->create(['version' => 1, 'snapshot' => $snapshot, 'created_at' => $now]);
            $event = match ($mode) {
                SubmissionFinalizationMode::Participant => 'submitted',
                SubmissionFinalizationMode::SignedReminder => 'submission.submitted_from_reminder',
                SubmissionFinalizationMode::Administrative => 'submission.submitted_administratively',
            };
            $locked->events()->create([
                'actor_user_id' => $actor->id,
                'event' => $event,
                'metadata' => [
                    'finalization_mode' => $mode->value,
                    'idempotency_key' => $locked->submission_idempotency_key,
                    'reminder_id' => $lockedReminder?->id,
                ],
                'created_at' => $now,
            ]);

            if ($mode !== SubmissionFinalizationMode::Administrative) {
                $this->recordLegalAcceptances($owner, $locked, $mode, $lockedReminder, $now);
            }

            if (config('flowerflow.flags.admissibility_review')) {
                $this->ensureEligibilityReview->execute($locked, $version, $actor);
            }

            if ($lockedReminder) {
                $lockedReminder->forceFill(['consumed_at' => $now])->save();
            }

            if ($mode !== SubmissionFinalizationMode::Participant) {
                $this->auditLogger->record($event, $locked, $actor, [
                    'submission_id' => $locked->id,
                    'submission_version_id' => $version->id,
                    'reminder_id' => $lockedReminder?->id,
                    'reason_code' => $mode === SubmissionFinalizationMode::Administrative
                        ? 'administrative_minimum_content_exception'
                        : 'signed_reminder_attachment_waiver',
                ]);
            }

            DB::afterCommit(function () use ($mode, $owner, $locked): void {
                $mail = $mode === SubmissionFinalizationMode::Administrative
                    ? new SubmissionAdministrativelyFinalized($locked)
                    : new SubmissionReceived($locked);
                $warning = $mode === SubmissionFinalizationMode::Administrative
                    ? 'La propuesta quedó registrada administrativamente, pero no pudimos programar el aviso al participante.'
                    : 'Tu propuesta quedó registrada, pero no pudimos programar el correo de confirmación. Conserva el folio y vuelve a intentarlo desde la propuesta más tarde.';

                $this->mailDispatcher->queue($owner, $mail, $warning);
            });

            return $locked;
        }, 3);

        return $result->fresh(['category', 'competition']);
    }

    private function assertAcceptances(array $acceptances): void
    {
        $errors = [];
        foreach (['accept_call_rules', 'accept_terms', 'accept_privacy'] as $field) {
            if (! filter_var($acceptances[$field] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $errors[$field] = 'Debes confirmar esta aceptación para enviar la propuesta.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertReminderUsable(Submission $submission, ?SubmissionReminder $reminder): void
    {
        if (! $reminder
            || $reminder->submission_id !== $submission->id
            || $reminder->recipient_user_id !== $submission->user_id) {
            throw ValidationException::withMessages(['reminder' => 'El recordatorio no corresponde a esta propuesta.']);
        }
        if ($reminder->consumed_at) {
            throw ValidationException::withMessages(['reminder' => 'Este enlace ya fue utilizado.']);
        }
        if ($reminder->link_expires_at->isPast()) {
            throw ValidationException::withMessages(['reminder' => 'Este enlace ya venció.']);
        }
    }

    private function recordLegalAcceptances(
        User $owner,
        Submission $submission,
        SubmissionFinalizationMode $mode,
        ?SubmissionReminder $reminder,
        mixed $acceptedAt,
    ): void {
        $activeDocuments = LegalDocument::query()
            ->where('active', true)
            ->whereIn('code', ['mechanics', 'terms', 'privacy'])
            ->get();
        $documents = $activeDocuments->keyBy('code');

        if ($activeDocuments->count() !== 3
            || ! $documents->has('mechanics')
            || ! $documents->has('terms')
            || ! $documents->has('privacy')) {
            throw ValidationException::withMessages([
                'legal_documents' => 'No podemos enviar la propuesta porque no existe una única versión vigente de cada documento legal.',
            ]);
        }

        foreach ([
            'call_rules' => 'mechanics',
            'terms' => 'terms',
            'privacy' => 'privacy',
        ] as $purpose => $documentCode) {
            $document = $documents->get($documentCode);
            $owner->legalAcceptances()->create([
                'legal_document_id' => $document->id,
                'purpose' => $purpose,
                'document_version' => $document->version,
                'accepted' => true,
                'accepted_at' => $acceptedAt,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'context' => [
                    'submission_public_id' => $submission->public_id,
                    'finalization_mode' => $mode->value,
                    'reminder_public_id' => $reminder?->public_id,
                ],
            ]);
        }
    }
}
