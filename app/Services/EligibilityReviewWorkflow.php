<?php

namespace App\Services;

use App\Enums\ClarificationStatus;
use App\Enums\EligibilityReviewStatus;
use App\Enums\ResidencyDocumentType;
use App\Enums\ResidencyVerificationStatus;
use App\Mail\AdmissibilityUpdate;
use App\Models\ClarificationRequest;
use App\Models\ClarificationResponse;
use App\Models\EligibilityReview;
use App\Models\ResidencyDocumentRequest;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EligibilityReviewWorkflow
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PrivateEvidenceFileStore $files,
        private readonly ResilientMailDispatcher $mailDispatcher
    ) {}

    public function start(EligibilityReview $review, User $actor): EligibilityReview
    {
        $result = DB::transaction(function () use ($review, $actor): EligibilityReview {
            $locked = EligibilityReview::query()->lockForUpdate()->findOrFail($review->id);
            $this->assertNotFinal($locked);

            if ($locked->status === EligibilityReviewStatus::InReview) {
                return $locked;
            }

            $from = $locked->status;
            $locked->update([
                'status' => EligibilityReviewStatus::InReview,
                'reviewer_user_id' => $locked->reviewer_user_id ?: $actor->id,
                'started_at' => $locked->started_at ?: now('UTC'),
            ]);
            $this->event($locked, $actor, 'review_started', $from, EligibilityReviewStatus::InReview);
            $this->audit->record('admissibility.review_started', $locked, $actor);

            return $locked;
        }, 3);

        return $result;
    }

    public function requestClarification(
        EligibilityReview $review,
        User $actor,
        string $message,
        ?string $dueAt
    ): ClarificationRequest {
        $clarification = DB::transaction(function () use ($review, $actor, $message, $dueAt): ClarificationRequest {
            $locked = EligibilityReview::query()->lockForUpdate()->findOrFail($review->id);
            $this->assertNotFinal($locked);

            $clarification = $locked->clarifications()->create([
                'requested_by_user_id' => $actor->id,
                'status' => ClarificationStatus::Open,
                'message' => $message,
                'due_at' => $dueAt ? CarbonImmutable::parse($dueAt, config('flowerflow.timezone'))->utc() : null,
            ]);

            $from = $locked->status;
            $locked->update([
                'status' => EligibilityReviewStatus::ClarificationRequested,
                'reviewer_user_id' => $locked->reviewer_user_id ?: $actor->id,
                'started_at' => $locked->started_at ?: now('UTC'),
            ]);
            $this->event($locked, $actor, 'clarification_requested', $from, EligibilityReviewStatus::ClarificationRequested, $message, [
                'clarification_public_id' => $clarification->public_id,
                'due_at_utc' => $clarification->due_at?->toIso8601String(),
            ]);
            $this->audit->record('clarification.requested', $clarification, $actor, [
                'review_public_id' => $locked->public_id,
            ]);

            DB::afterCommit(fn () => $this->send($locked, 'clarification_requested'));

            return $clarification;
        }, 3);

        return $clarification;
    }

    /** @param array<int, UploadedFile> $files */
    public function respondToClarification(
        ClarificationRequest $clarification,
        User $actor,
        string $body,
        array $files
    ): ClarificationResponse {
        $storedFiles = collect();
        try {
            $response = DB::transaction(function () use ($clarification, $actor, $body, $files, $storedFiles): ClarificationResponse {
                $locked = ClarificationRequest::query()->lockForUpdate()->findOrFail($clarification->id);
                if ($locked->status !== ClarificationStatus::Open) {
                    throw ValidationException::withMessages(['response' => 'Esta solicitud ya no admite respuestas.']);
                }

                $existingFiles = $locked->responses()->with('files:id,clarification_response_id,size_bytes')->get()->flatMap->files;
                $incomingBytes = collect($files)->sum(fn (UploadedFile $file) => $file->getSize());
                if ($existingFiles->count() + count($files) > config('flowerflow.admissibility.files_per_person_request')) {
                    throw ValidationException::withMessages(['files' => 'Puedes adjuntar como máximo tres archivos en esta aclaración.']);
                }
                if ($existingFiles->sum('size_bytes') + $incomingBytes > config('flowerflow.admissibility.files_total_kib_per_person_request') * 1024) {
                    throw ValidationException::withMessages(['files' => 'Los archivos de esta aclaración no pueden superar 10 MiB acumulados.']);
                }

                $response = $locked->responses()->create([
                    'responder_user_id' => $actor->id,
                    'body' => $body,
                    'created_at' => now('UTC'),
                ]);
                foreach ($files as $file) {
                    $storedFiles->push($this->files->storeClarification($response, $actor, $file));
                }

                $locked->update(['status' => ClarificationStatus::Answered, 'answered_at' => now('UTC')]);
                $review = EligibilityReview::query()->lockForUpdate()->findOrFail($locked->eligibility_review_id);
                if (! $review->status->isFinal()) {
                    $from = $review->status;
                    $review->update(['status' => EligibilityReviewStatus::InReview]);
                    $this->event($review, $actor, 'clarification_answered', $from, EligibilityReviewStatus::InReview, null, [
                        'clarification_public_id' => $locked->public_id,
                        'response_public_id' => $response->public_id,
                        'files_count' => count($files),
                    ]);
                }
                $this->audit->record('clarification.answered', $response, $actor, ['files_count' => count($files)]);

                DB::afterCommit(fn () => $this->send($review, 'response_received'));

                return $response;
            }, 3);
        } catch (Throwable $exception) {
            foreach ($storedFiles as $file) {
                Storage::disk($file->disk)->delete($file->path);
            }
            throw $exception;
        }

        return $response;
    }

    public function closeClarification(ClarificationRequest $clarification, User $actor): ClarificationRequest
    {
        $result = DB::transaction(function () use ($clarification, $actor): ClarificationRequest {
            $locked = ClarificationRequest::query()->lockForUpdate()->findOrFail($clarification->id);
            if ($locked->status === ClarificationStatus::Closed) {
                return $locked;
            }

            $locked->update([
                'status' => ClarificationStatus::Closed,
                'closed_by_user_id' => $actor->id,
                'closed_at' => now('UTC'),
            ]);
            $review = EligibilityReview::query()->lockForUpdate()->findOrFail($locked->eligibility_review_id);
            if ($review->status === EligibilityReviewStatus::ClarificationRequested
                && ! $review->clarifications()->where('status', ClarificationStatus::Open->value)->exists()) {
                $from = $review->status;
                $review->update(['status' => EligibilityReviewStatus::InReview]);
                $this->event($review, $actor, 'clarification_closed', $from, EligibilityReviewStatus::InReview, null, [
                    'clarification_public_id' => $locked->public_id,
                ]);
            }

            foreach ($locked->residencyRequests()->whereNotNull('validated_at')->get() as $residencyRequest) {
                $this->updateRetention($residencyRequest, $locked->closed_at);
            }
            $this->audit->record('clarification.closed', $locked, $actor);

            return $locked;
        }, 3);

        return $result;
    }

    public function requestResidency(
        EligibilityReview $review,
        User $actor,
        string $subjectType,
        ?TeamMember $teamMember,
        ?ClarificationRequest $clarification,
        ?string $instructions
    ): ResidencyDocumentRequest {
        if (! in_array($subjectType, ['representative', 'team_member'], true)) {
            throw ValidationException::withMessages(['subject_type' => 'Selecciona una persona válida para la solicitud.']);
        }

        $request = DB::transaction(function () use ($review, $actor, $subjectType, $teamMember, $clarification, $instructions): ResidencyDocumentRequest {
            $locked = EligibilityReview::query()->with('submission.team.members')->lockForUpdate()->findOrFail($review->id);
            $this->assertNotFinal($locked);

            $subjectUserId = $subjectType === 'representative' ? $locked->submission->user_id : null;
            if ($subjectType === 'team_member') {
                if (! $teamMember || $teamMember->team_id !== $locked->submission->team_id || $teamMember->is_representative) {
                    throw ValidationException::withMessages(['subject_team_member_id' => 'Selecciona un integrante válido de esta propuesta.']);
                }
            }

            $duplicate = $locked->residencyRequests()
                ->where('subject_user_id', $subjectUserId)
                ->where('subject_team_member_id', $teamMember?->id)
                ->whereIn('status', [
                    ResidencyVerificationStatus::Requested->value,
                    ResidencyVerificationStatus::UnderReview->value,
                    ResidencyVerificationStatus::Verified->value,
                ])->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['subject_type' => 'Ya existe una solicitud activa para esta persona.']);
            }

            $request = $locked->residencyRequests()->create([
                'subject_user_id' => $subjectUserId,
                'subject_team_member_id' => $teamMember?->id,
                'requested_by_user_id' => $actor->id,
                'clarification_request_id' => $clarification?->id,
                'status' => ResidencyVerificationStatus::Requested,
                'instructions' => $instructions,
            ]);
            $from = $locked->status;
            $to = $from === EligibilityReviewStatus::Pending ? EligibilityReviewStatus::InReview : $from;
            $locked->update([
                'status' => $to,
                'reviewer_user_id' => $locked->reviewer_user_id ?: $actor->id,
                'started_at' => $locked->started_at ?: now('UTC'),
            ]);
            $this->event($locked, $actor, 'residency_requested', $from, $to, null, [
                'residency_request_public_id' => $request->public_id,
                'subject_type' => $subjectType,
            ]);
            $this->audit->record('residency.requested', $request, $actor, ['review_public_id' => $locked->public_id]);
            DB::afterCommit(fn () => $this->send($locked, 'residency_requested'));

            return $request;
        }, 3);

        return $request;
    }

    public function markResidencyUnderReview(ResidencyDocumentRequest $request, User $actor): ResidencyDocumentRequest
    {
        $result = DB::transaction(function () use ($request, $actor): ResidencyDocumentRequest {
            $locked = ResidencyDocumentRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->status === ResidencyVerificationStatus::UnderReview) {
                return $locked;
            }
            if (! in_array($locked->status, [ResidencyVerificationStatus::Requested, ResidencyVerificationStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'La solicitud no puede pasar a revisión desde su estado actual.']);
            }
            if (! $locked->documents()->exists()) {
                throw ValidationException::withMessages(['status' => 'No hay documentos cargados para revisar.']);
            }
            $locked->update(['status' => ResidencyVerificationStatus::UnderReview]);
            $this->event($locked->review, $actor, 'residency_under_review', null, null, null, [
                'residency_request_public_id' => $locked->public_id,
            ]);
            $this->audit->record('residency.under_review', $locked, $actor);

            return $locked;
        }, 3);

        return $result;
    }

    public function resolveResidency(
        ResidencyDocumentRequest $request,
        User $actor,
        ResidencyVerificationStatus $status,
        string $reason
    ): ResidencyDocumentRequest {
        if (! in_array($status, [ResidencyVerificationStatus::Verified, ResidencyVerificationStatus::Rejected, ResidencyVerificationStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['residency_status' => 'Selecciona una resolución válida.']);
        }

        $result = DB::transaction(function () use ($request, $actor, $status, $reason): ResidencyDocumentRequest {
            $locked = ResidencyDocumentRequest::query()->with(['documents', 'clarification'])->lockForUpdate()->findOrFail($request->id);
            if ($locked->status === $status) {
                return $locked;
            }
            if ($locked->status === ResidencyVerificationStatus::Cancelled || $locked->status === ResidencyVerificationStatus::Verified) {
                throw ValidationException::withMessages(['residency_status' => 'La solicitud ya tiene una resolución final.']);
            }
            if ($status !== ResidencyVerificationStatus::Cancelled && $locked->documents->isEmpty()) {
                throw ValidationException::withMessages(['residency_status' => 'No hay documentos para resolver esta solicitud.']);
            }
            if ($status === ResidencyVerificationStatus::Rejected && blank($reason)) {
                throw ValidationException::withMessages(['review_reason' => 'Explica de forma clara por qué se rechaza el documento.']);
            }
            if ($status === ResidencyVerificationStatus::Verified
                && $locked->documents->contains('document_type', ResidencyDocumentType::Equivalent)
                && blank($reason)) {
                throw ValidationException::withMessages(['review_reason' => 'Documenta la justificación manual para aceptar un documento equivalente.']);
            }

            $validatedAt = $status === ResidencyVerificationStatus::Verified ? now('UTC') : null;
            $locked->update([
                'status' => $status,
                'review_reason' => $reason,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now('UTC'),
                'validated_at' => $validatedAt,
            ]);
            if ($validatedAt) {
                $this->updateRetention($locked, $locked->clarification?->closed_at);
            }
            $this->event($locked->review, $actor, 'residency_'.$status->value, null, null, null, [
                'residency_request_public_id' => $locked->public_id,
                'reason_recorded' => filled($reason),
            ]);
            $this->audit->record('residency.'.$status->value, $locked, $actor, ['reason_recorded' => filled($reason)]);

            return $locked;
        }, 3);

        return $result;
    }

    public function decide(
        EligibilityReview $review,
        User $actor,
        EligibilityReviewStatus $decision,
        string $participantReason,
        ?string $internalNotes
    ): EligibilityReview {
        if (! $decision->isFinal()) {
            throw ValidationException::withMessages(['decision' => 'Selecciona una resolución final válida.']);
        }

        $result = DB::transaction(function () use ($review, $actor, $decision, $participantReason, $internalNotes): EligibilityReview {
            $locked = EligibilityReview::query()->with(['clarifications', 'residencyRequests'])->lockForUpdate()->findOrFail($review->id);
            if ($locked->status === $decision) {
                return $locked;
            }
            $this->assertNotFinal($locked);
            if ($locked->clarifications->contains('status', ClarificationStatus::Open)) {
                throw ValidationException::withMessages(['decision' => 'Responde o cierra expresamente todas las aclaraciones abiertas antes de resolver.']);
            }
            if ($decision === EligibilityReviewStatus::Admitted
                && $locked->residencyRequests->whereNotIn('status', [ResidencyVerificationStatus::Verified, ResidencyVerificationStatus::Cancelled])->isNotEmpty()) {
                throw ValidationException::withMessages(['decision' => 'Verifica a todas las personas con solicitud activa de residencia antes de admitir.']);
            }

            $from = $locked->status;
            $locked->update([
                'status' => $decision,
                'participant_reason' => $participantReason,
                'internal_notes' => $internalNotes,
                'reviewer_user_id' => $locked->reviewer_user_id ?: $actor->id,
                'resolved_by_user_id' => $actor->id,
                'started_at' => $locked->started_at ?: now('UTC'),
                'resolved_at' => now('UTC'),
            ]);
            $this->event($locked, $actor, 'review_'.$decision->value, $from, $decision, $participantReason, [
                'internal_notes_recorded' => filled($internalNotes),
                'submission_version_id' => $locked->submission_version_id,
            ]);
            $this->audit->record('admissibility.'.$decision->value, $locked, $actor, [
                'submission_version_id' => $locked->submission_version_id,
                'internal_notes_recorded' => filled($internalNotes),
            ]);
            DB::afterCommit(fn () => $this->send($locked, $decision->value));

            return $locked;
        }, 3);

        return $result;
    }

    private function updateRetention(ResidencyDocumentRequest $request, mixed $clarificationClosedAt): void
    {
        $basis = collect([$request->validated_at, $clarificationClosedAt])->filter()->max();
        if (! $basis) {
            return;
        }

        $basis = $basis instanceof CarbonInterface ? $basis : CarbonImmutable::parse($basis, 'UTC');
        $request->update([
            'retention_basis_at' => $basis,
            'retention_due_at' => $basis->copy()->addDays(config('flowerflow.admissibility.retention_days')),
            'retention_reason' => $clarificationClosedAt
                ? 'Noventa días después de la validación o del cierre de la aclaración relacionada, sujeto a la determinación futura de ganadores.'
                : 'Noventa días después de la validación, sujeto a la determinación futura de ganadores.',
        ]);
    }

    private function assertNotFinal(EligibilityReview $review): void
    {
        if ($review->status->isFinal()) {
            throw ValidationException::withMessages(['status' => 'El expediente ya tiene una resolución final.']);
        }
    }

    private function event(
        EligibilityReview $review,
        User $actor,
        string $event,
        ?EligibilityReviewStatus $from,
        ?EligibilityReviewStatus $to,
        ?string $participantMessage = null,
        ?array $metadata = null
    ): void {
        $review->events()->create([
            'actor_user_id' => $actor->id,
            'event' => $event,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'participant_message' => $participantMessage,
            'metadata' => $metadata,
            'created_at' => now('UTC'),
        ]);
    }

    private function send(EligibilityReview $review, string $kind): void
    {
        $review->loadMissing('submission.user');
        $this->mailDispatcher->queue(
            $review->submission->user,
            new AdmissibilityUpdate($review, $kind),
            'La actualización quedó guardada, pero no pudimos programar el correo. La información permanece disponible dentro de Flower Flow.'
        );
    }
}
