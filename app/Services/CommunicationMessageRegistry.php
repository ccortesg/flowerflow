<?php

namespace App\Services;

use App\Enums\BlindReviewPackageStatus;
use App\Enums\CommunicationType;
use App\Enums\EligibilityReviewStatus;
use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeConflictStatus;
use App\Enums\JudgeProfileStatus;
use App\Enums\SubmissionReminderStatus;
use App\Exceptions\CommunicationCancelledException;
use App\Mail\AdmissibilityUpdate;
use App\Mail\SubmissionAdministrativelyFinalized;
use App\Mail\SubmissionDraftReminder;
use App\Mail\SubmissionReceived;
use App\Models\CommunicationDelivery;
use App\Models\Competition;
use App\Models\EligibilityReview;
use App\Models\EvaluationReopening;
use App\Models\EvaluationRevision;
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
use App\Models\JudgeSetupLink;
use App\Models\Submission;
use App\Models\SubmissionReminder;
use App\Models\User;
use App\Notifications\EvaluationCloseDigestNotification;
use App\Notifications\EvaluationReopenedNotification;
use App\Notifications\EvaluationSubmittedNotification;
use App\Notifications\JudgeAccountSetupNotification;
use App\Notifications\JudgeAccountStatusNotification;
use App\Notifications\JudgeAssignmentBulkCreatedNotification;
use App\Notifications\JudgeAssignmentCreatedNotification;
use App\Notifications\JudgeConflictDeclaredNotification;
use App\Notifications\JudgeConflictResolvedNotification;
use App\Notifications\JudgeVerifyEmailNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

final class CommunicationMessageRegistry
{
    /** @return array{type: CommunicationType, variant: ?string, context: array<string, mixed>, related_type: ?string, related_id: ?int} */
    public function describe(User $recipient, Mailable|Notification $message): array
    {
        return match (true) {
            $message instanceof JudgeAccountSetupNotification => [
                'type' => CommunicationType::JudgeAccountSetup,
                'variant' => null,
                'context' => ['setup_link_id' => $message->setupLinkId, 'token' => $message->token],
                'related_type' => JudgeSetupLink::class,
                'related_id' => $message->setupLinkId,
            ],
            $message instanceof JudgeVerifyEmailNotification => [
                'type' => CommunicationType::JudgeEmailVerification,
                'variant' => null,
                'context' => [],
                'related_type' => User::class,
                'related_id' => $recipient->id,
            ],
            $message instanceof ResetPasswordNotification => [
                'type' => CommunicationType::AccountPasswordReset,
                'variant' => null,
                'context' => ['token' => $message->token],
                'related_type' => User::class,
                'related_id' => $recipient->id,
            ],
            $message instanceof VerifyEmailNotification => [
                'type' => CommunicationType::AccountEmailVerification,
                'variant' => null,
                'context' => [],
                'related_type' => User::class,
                'related_id' => $recipient->id,
            ],
            $message instanceof JudgeAccountStatusNotification => [
                'type' => CommunicationType::JudgeAccountStatus,
                'variant' => $message->event,
                'context' => ['event' => $message->event],
                'related_type' => User::class,
                'related_id' => $recipient->id,
            ],
            $message instanceof JudgeAssignmentCreatedNotification => [
                'type' => CommunicationType::JudgeAssignmentCreated,
                'variant' => null,
                'context' => ['judge_assignment_id' => $message->assignmentId],
                'related_type' => JudgeAssignment::class,
                'related_id' => $message->assignmentId,
            ],
            $message instanceof JudgeAssignmentBulkCreatedNotification => [
                'type' => CommunicationType::JudgeAssignmentBulkCreated,
                'variant' => 'consolidated',
                'context' => ['judge_assignment_ids' => $message->assignmentIds],
                'related_type' => JudgeProfile::class,
                'related_id' => $recipient->judgeProfile?->id,
            ],
            $message instanceof JudgeConflictDeclaredNotification => [
                'type' => CommunicationType::JudgeConflictDeclared,
                'variant' => 'responsible_admin',
                'context' => ['judge_conflict_id' => $message->conflictId],
                'related_type' => JudgeConflict::class,
                'related_id' => $message->conflictId,
            ],
            $message instanceof JudgeConflictResolvedNotification => [
                'type' => CommunicationType::JudgeConflictResolved,
                'variant' => 'outgoing_judge',
                'context' => ['judge_conflict_id' => $message->conflictId],
                'related_type' => JudgeConflict::class,
                'related_id' => $message->conflictId,
            ],
            $message instanceof EvaluationSubmittedNotification => [
                'type' => CommunicationType::EvaluationSubmitted,
                'variant' => $message->recipientPurpose,
                'context' => [
                    'evaluation_revision_id' => $message->revisionId,
                    'recipient_purpose' => $message->recipientPurpose,
                ],
                'related_type' => EvaluationRevision::class,
                'related_id' => $message->revisionId,
            ],
            $message instanceof EvaluationReopenedNotification => [
                'type' => CommunicationType::EvaluationReopened,
                'variant' => 'subject_judge',
                'context' => ['evaluation_reopening_id' => $message->reopeningId],
                'related_type' => EvaluationReopening::class,
                'related_id' => $message->reopeningId,
            ],
            $message instanceof EvaluationCloseDigestNotification => [
                'type' => CommunicationType::EvaluationCloseDigest,
                'variant' => 'subject_judge',
                'context' => [
                    'judge_profile_id' => $message->judgeProfileId,
                    'competition_id' => $message->competitionId,
                    'submitted' => $message->submitted,
                    'pending' => $message->pending,
                    'conflicts_replacements' => $message->conflictsReplacements,
                    'cancelled' => $message->cancelled,
                ],
                'related_type' => JudgeProfile::class,
                'related_id' => $message->judgeProfileId,
            ],
            $message instanceof SubmissionAdministrativelyFinalized => [
                'type' => CommunicationType::SubmissionAdministrativelyFinalized,
                'variant' => 'administrative',
                'context' => ['submission_id' => $message->submission->id],
                'related_type' => Submission::class,
                'related_id' => $message->submission->id,
            ],
            $message instanceof SubmissionReceived => [
                'type' => CommunicationType::SubmissionReceived,
                'variant' => null,
                'context' => ['submission_id' => $message->submission->id],
                'related_type' => Submission::class,
                'related_id' => $message->submission->id,
            ],
            $message instanceof SubmissionDraftReminder => [
                'type' => CommunicationType::SubmissionDraftReminder,
                'variant' => null,
                'context' => ['submission_reminder_id' => $message->reminder->id],
                'related_type' => SubmissionReminder::class,
                'related_id' => $message->reminder->id,
            ],
            $message instanceof AdmissibilityUpdate => [
                'type' => CommunicationType::AdmissibilityUpdate,
                'variant' => $message->kind,
                'context' => ['eligibility_review_id' => $message->review->id, 'kind' => $message->kind],
                'related_type' => EligibilityReview::class,
                'related_id' => $message->review->id,
            ],
            default => throw new InvalidArgumentException('Unsupported transactional communication type.'),
        };
    }

    /** @return array{recipient: User, message: Mailable|Notification} */
    public function prepare(CommunicationDelivery $delivery): array
    {
        $recipient = User::query()->with(['roles', 'judgeProfile'])->find($delivery->recipient_user_id);
        if (! $recipient || blank($delivery->recipient_address)) {
            throw new CommunicationCancelledException('recipient_unavailable');
        }

        $fingerprint = self::recipientFingerprint((string) $recipient->email);
        if (! hash_equals($delivery->recipient_fingerprint, $fingerprint)
            || ! hash_equals((string) $delivery->recipient_address, (string) $recipient->email)) {
            throw new CommunicationCancelledException('recipient_changed');
        }

        $context = $delivery->context ?? [];
        $message = match ($delivery->notification_type) {
            CommunicationType::AccountEmailVerification => $this->accountVerification($recipient),
            CommunicationType::AccountPasswordReset => $this->passwordReset($recipient, $context),
            CommunicationType::JudgeAccountSetup => $this->judgeSetup($recipient, $context),
            CommunicationType::JudgeEmailVerification => $this->judgeVerification($recipient),
            CommunicationType::JudgeAccountStatus => $this->judgeStatus($recipient, $context),
            CommunicationType::JudgeAssignmentCreated => $this->judgeAssignment($recipient, $context),
            CommunicationType::JudgeAssignmentBulkCreated => $this->judgeBulkAssignment($recipient, $context),
            CommunicationType::JudgeConflictDeclared => $this->judgeConflictDeclared($recipient, $context),
            CommunicationType::JudgeConflictResolved => $this->judgeConflictResolved($recipient, $context),
            CommunicationType::EvaluationSubmitted => $this->evaluationSubmitted($recipient, $context),
            CommunicationType::EvaluationReopened => $this->evaluationReopened($recipient, $context),
            CommunicationType::EvaluationCloseDigest => $this->evaluationCloseDigest($recipient, $context),
            CommunicationType::SubmissionReceived => $this->submissionReceipt($recipient, $context, false),
            CommunicationType::SubmissionAdministrativelyFinalized => $this->submissionReceipt($recipient, $context, true),
            CommunicationType::SubmissionDraftReminder => $this->submissionReminder($recipient, $context),
            CommunicationType::AdmissibilityUpdate => $this->admissibilityUpdate($recipient, $context),
        };

        return ['recipient' => $recipient, 'message' => $message];
    }

    public function send(CommunicationDelivery $delivery): void
    {
        ['recipient' => $recipient, 'message' => $message] = $this->prepare($delivery);

        if ($message instanceof Notification) {
            $recipient->notifyNow($message, ['mail']);

            return;
        }

        Mail::to((string) $delivery->recipient_address)->send($message);
    }

    public static function recipientFingerprint(string $address): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($address)), (string) config('app.key'));
    }

    public static function maskAddress(string $address): string
    {
        [$local, $domain] = array_pad(explode('@', $address, 2), 2, '');
        $domainParts = explode('.', $domain);
        $domainName = array_shift($domainParts) ?: '';
        $suffix = $domainParts === [] ? '' : '.'.implode('.', $domainParts);

        return mb_substr($local, 0, 1).'***@'.mb_substr($domainName, 0, 1).'***'.$suffix;
    }

    private function accountVerification(User $recipient): VerifyEmailNotification
    {
        if ($recipient->hasVerifiedEmail() || $recipient->hasExactRoles(['judge'])) {
            throw new CommunicationCancelledException('verification_no_longer_required');
        }

        return new VerifyEmailNotification;
    }

    private function judgeVerification(User $recipient): JudgeVerifyEmailNotification
    {
        if ($recipient->hasVerifiedEmail() || ! $recipient->hasExactRoles(['judge'])) {
            throw new CommunicationCancelledException('judge_verification_no_longer_valid');
        }

        return new JudgeVerifyEmailNotification;
    }

    private function passwordReset(User $recipient, array $context): Notification
    {
        $token = $context['token'] ?? null;
        if (! is_string($token)
            || ! Password::broker('users')->tokenExists($recipient, $token)) {
            throw new CommunicationCancelledException('password_reset_token_invalid');
        }

        return new ResetPasswordNotification($token);
    }

    private function judgeSetup(User $recipient, array $context): JudgeAccountSetupNotification
    {
        $token = $context['token'] ?? null;
        $link = JudgeSetupLink::query()->with('judgeProfile')->find($context['setup_link_id'] ?? null);
        if (! is_string($token)
            || ! $link
            || ! $recipient->hasExactRoles(['judge'])
            || $link->judgeProfile?->user_id !== $recipient->id
            || $link->active_slot !== 1
            || $link->consumed_at !== null
            || $link->invalidated_at !== null
            || $link->expires_at->isPast()
            || ! hash_equals($link->token_hash, hash('sha256', $token))
            || ! hash_equals($link->email_fingerprint, self::recipientFingerprint((string) $recipient->email))) {
            throw new CommunicationCancelledException('judge_setup_link_invalid');
        }

        return new JudgeAccountSetupNotification($link->id, $token);
    }

    private function judgeStatus(User $recipient, array $context): JudgeAccountStatusNotification
    {
        $event = $context['event'] ?? null;
        if (! is_string($event) || ! $recipient->hasExactRoles(['judge']) || ! $recipient->judgeProfile) {
            throw new CommunicationCancelledException('judge_status_event_invalid');
        }
        if (($event === 'suspended' && $recipient->judgeProfile->status !== JudgeProfileStatus::Suspended)
            || ($event === 'reactivated' && $recipient->judgeProfile->status !== JudgeProfileStatus::Active)) {
            throw new CommunicationCancelledException('judge_status_changed');
        }

        return new JudgeAccountStatusNotification($event);
    }

    private function judgeAssignment(User $recipient, array $context): JudgeAssignmentCreatedNotification
    {
        $assignment = JudgeAssignment::query()->with('judgeProfile')->find($context['judge_assignment_id'] ?? null);
        if (! config('flowerflow.judge_notifications.assignment_enabled')
            || ! $assignment
            || $assignment->status !== JudgeAssignmentStatus::Active
            || $assignment->current_slot !== 1
            || $assignment->due_at->isPast()
            || $assignment->judgeProfile?->user_id !== $recipient->id
            || $assignment->judgeProfile?->status !== JudgeProfileStatus::Active
            || ! $recipient->hasExactRoles(['judge'])
            || ! $recipient->hasVerifiedEmail()) {
            throw new CommunicationCancelledException('judge_assignment_notification_invalid');
        }

        return new JudgeAssignmentCreatedNotification($assignment->id);
    }

    private function judgeBulkAssignment(User $recipient, array $context): JudgeAssignmentBulkCreatedNotification
    {
        $assignmentIds = $context['judge_assignment_ids'] ?? null;
        $limit = (int) config('flowerflow.bulk_judge_assignment.limit');
        if (! config('flowerflow.flags.bulk_judge_assignment')
            || ! config('flowerflow.flags.communication_ledger')
            || ! config('flowerflow.judge_notifications.assignment_enabled')
            || ! is_array($assignmentIds)
            || $assignmentIds === []
            || count($assignmentIds) > $limit
            || count($assignmentIds) !== count(array_unique($assignmentIds))
            || collect($assignmentIds)->contains(fn ($id): bool => ! is_int($id))) {
            throw new CommunicationCancelledException('judge_bulk_assignment_notification_invalid');
        }

        $assignments = JudgeAssignment::query()
            ->whereIn('id', $assignmentIds)
            ->with([
                'judgeProfile.user.roles',
                'submissionVersion.blindReviewPackage',
            ])
            ->orderBy('id')
            ->get();
        $validIds = $assignments
            ->filter(function (JudgeAssignment $assignment) use ($recipient): bool {
                $profile = $assignment->judgeProfile;
                $package = $assignment->submissionVersion?->blindReviewPackage;

                return $assignment->status === JudgeAssignmentStatus::Active
                    && $assignment->current_slot === 1
                    && ! $assignment->due_at->isPast()
                    && $profile?->user_id === $recipient->id
                    && $profile->status === JudgeProfileStatus::Active
                    && $profile->password_initialized_at !== null
                    && $package?->status === BlindReviewPackageStatus::Active
                    && $package->submission_version_id === $assignment->submission_version_id
                    && $recipient->hasExactRoles(['judge'])
                    && $recipient->hasVerifiedEmail();
            })
            ->pluck('id')
            ->values()
            ->all();
        if ($validIds === []) {
            throw new CommunicationCancelledException('judge_bulk_assignment_no_longer_actionable');
        }

        return new JudgeAssignmentBulkCreatedNotification($validIds);
    }

    private function judgeConflictDeclared(User $recipient, array $context): JudgeConflictDeclaredNotification
    {
        $this->assertEvaluationNotificationsEnabled();
        $conflict = JudgeConflict::query()->with('assignment')->find($context['judge_conflict_id'] ?? null);
        if (! $conflict
            || $conflict->status !== JudgeConflictStatus::Declared
            || $conflict->assignment->status !== JudgeAssignmentStatus::ConflictDeclared
            || $conflict->assignment->assigned_by_user_id !== $recipient->id
            || ! $this->eligibleAdmin($recipient, 'resolve evaluation conflicts')) {
            throw new CommunicationCancelledException('judge_conflict_declared_invalid');
        }
        $this->assertAssignmentWindow($conflict->assignment, false);

        return new JudgeConflictDeclaredNotification($conflict->id);
    }

    private function judgeConflictResolved(User $recipient, array $context): JudgeConflictResolvedNotification
    {
        $this->assertEvaluationNotificationsEnabled();
        $conflict = JudgeConflict::query()
            ->with(['assignment.judgeProfile', 'replacementAssignment'])
            ->find($context['judge_conflict_id'] ?? null);
        if (! $conflict
            || $conflict->status !== JudgeConflictStatus::ResolvedReassigned
            || $conflict->assignment->status !== JudgeAssignmentStatus::Voided
            || ! $conflict->replacementAssignment
            || $conflict->replacement_assignment_id !== $conflict->replacementAssignment->id
            || $conflict->assignment->judgeProfile?->user_id !== $recipient->id
            || ! $this->eligibleJudge($conflict->assignment->judgeProfile, $recipient)) {
            throw new CommunicationCancelledException('judge_conflict_resolved_invalid');
        }
        $this->assertAssignmentWindow($conflict->assignment, false);

        return new JudgeConflictResolvedNotification($conflict->id);
    }

    private function evaluationSubmitted(User $recipient, array $context): EvaluationSubmittedNotification
    {
        $this->assertEvaluationNotificationsEnabled();
        $purpose = $context['recipient_purpose'] ?? null;
        $revision = EvaluationRevision::query()
            ->with([
                'evaluation.judgeAssignment.judgeProfile',
                'evaluation.rubricVersion.criteria',
                'evaluation.blindReviewPackage',
                'reopeningAsTarget',
            ])
            ->find($context['evaluation_revision_id'] ?? null);
        if (! $revision
            || ! in_array($purpose, ['subject_judge', 'responsible_admin'], true)
            || $revision->status !== EvaluationRevisionStatus::Submitted
            || $revision->submitted_at === null
            || $revision->submission_mode === null
            || $revision->total_raw === null) {
            throw new CommunicationCancelledException('evaluation_submission_invalid');
        }

        $assignment = $this->assertEvaluationFoundation($revision, false);
        if ($purpose === 'subject_judge') {
            if ($revision->subject_judge_profile_id !== $assignment->judge_profile_id
                || $assignment->judgeProfile?->user_id !== $recipient->id
                || ! $this->eligibleJudge($assignment->judgeProfile, $recipient)) {
                throw new CommunicationCancelledException('evaluation_submission_judge_invalid');
            }
        } else {
            $responsibleId = $revision->revision_number === 1
                ? $assignment->assigned_by_user_id
                : $revision->reopeningAsTarget?->reopened_by_user_id;
            if ($responsibleId !== $recipient->id || ! $this->eligibleAdmin($recipient, 'view evaluations')) {
                throw new CommunicationCancelledException('evaluation_submission_admin_invalid');
            }
        }

        return new EvaluationSubmittedNotification($revision->id, $purpose);
    }

    private function evaluationReopened(User $recipient, array $context): EvaluationReopenedNotification
    {
        $this->assertEvaluationNotificationsEnabled();
        $reopening = EvaluationReopening::query()
            ->with([
                'targetRevision.evaluation.judgeAssignment.judgeProfile',
                'targetRevision.evaluation.rubricVersion.criteria',
                'targetRevision.evaluation.blindReviewPackage',
            ])
            ->find($context['evaluation_reopening_id'] ?? null);
        if (! $reopening
            || $reopening->targetRevision->status !== EvaluationRevisionStatus::Draft
            || $reopening->targetRevision->evaluation->status !== EvaluationStatus::Reopened
            || $reopening->targetRevision->evaluation->current_revision_id !== $reopening->target_revision_id) {
            throw new CommunicationCancelledException('evaluation_reopening_no_longer_actionable');
        }
        $assignment = $this->assertEvaluationFoundation($reopening->targetRevision, true);
        if ($reopening->subject_judge_profile_id !== $assignment->judge_profile_id
            || $assignment->judgeProfile?->user_id !== $recipient->id
            || ! $this->eligibleJudge($assignment->judgeProfile, $recipient)) {
            throw new CommunicationCancelledException('evaluation_reopening_judge_invalid');
        }

        return new EvaluationReopenedNotification($reopening->id);
    }

    private function evaluationCloseDigest(User $recipient, array $context): EvaluationCloseDigestNotification
    {
        $profile = JudgeProfile::query()->with('user.roles')->find($context['judge_profile_id'] ?? null);
        $competition = Competition::query()->find($context['competition_id'] ?? null);
        $counts = [];
        foreach (['submitted', 'pending', 'conflicts_replacements', 'cancelled'] as $key) {
            if (! isset($context[$key]) || ! is_int($context[$key]) || $context[$key] < 0) {
                throw new CommunicationCancelledException('evaluation_close_digest_context_invalid');
            }
            $counts[$key] = $context[$key];
        }
        if (! $profile || ! $competition || $profile->user_id !== $recipient->id) {
            throw new CommunicationCancelledException('evaluation_close_digest_context_invalid');
        }
        app(EvaluationCloseDigest::class)->assertDeliveryContext($profile, $competition, $counts);

        return new EvaluationCloseDigestNotification(
            $profile->id,
            $competition->id,
            $counts['submitted'],
            $counts['pending'],
            $counts['conflicts_replacements'],
            $counts['cancelled'],
        );
    }

    private function assertEvaluationNotificationsEnabled(): void
    {
        if (! config('flowerflow.flags.communication_ledger')
            || ! config('flowerflow.flags.evaluation_notifications')) {
            throw new CommunicationCancelledException('evaluation_notifications_disabled');
        }
    }

    private function eligibleAdmin(User $recipient, string $permission): bool
    {
        return $recipient->hasExactRoles(['admin'])
            && $recipient->hasVerifiedEmail()
            && $recipient->can($permission);
    }

    private function eligibleJudge(?JudgeProfile $profile, User $recipient): bool
    {
        return $profile?->status === JudgeProfileStatus::Active
            && $recipient->hasExactRoles(['judge'])
            && $recipient->hasVerifiedEmail()
            && $recipient->can('access judge workspace');
    }

    private function assertAssignmentWindow(JudgeAssignment $assignment, bool $forMutation): void
    {
        try {
            app(EvaluationWindow::class)->assertAssignment($assignment, $forMutation);
        } catch (\Throwable) {
            throw new CommunicationCancelledException('evaluation_assignment_window_invalid');
        }
    }

    private function assertEvaluationFoundation(EvaluationRevision $revision, bool $forMutation): JudgeAssignment
    {
        $evaluation = $revision->evaluation;
        $assignment = $evaluation->judgeAssignment;
        if ($assignment->status !== JudgeAssignmentStatus::Active
            || $assignment->current_slot !== 1
            || $evaluation->rubric_version_id !== $assignment->rubric_version_id
            || $evaluation->blindReviewPackage?->status !== BlindReviewPackageStatus::Active
            || $evaluation->blindReviewPackage?->submission_version_id !== $assignment->submission_version_id
            || $revision->evaluation_id !== $evaluation->id
            || $revision->subject_judge_profile_id !== $assignment->judge_profile_id) {
            throw new CommunicationCancelledException('evaluation_context_invalid');
        }
        try {
            app(EvaluationRubricContract::class)->assertPersisted($evaluation->rubricVersion);
        } catch (LogicException) {
            throw new CommunicationCancelledException('evaluation_rubric_invalid');
        }
        $this->assertAssignmentWindow($assignment, $forMutation);

        return $assignment;
    }

    private function submissionReceipt(User $recipient, array $context, bool $administrative): Mailable
    {
        $submission = Submission::query()->find($context['submission_id'] ?? null);
        if (! $submission || $submission->user_id !== $recipient->id || $submission->status !== 'submitted' || blank($submission->folio)) {
            throw new CommunicationCancelledException('submission_receipt_invalid');
        }

        return $administrative
            ? new SubmissionAdministrativelyFinalized($submission)
            : new SubmissionReceived($submission);
    }

    private function submissionReminder(User $recipient, array $context): SubmissionDraftReminder
    {
        $reminder = SubmissionReminder::query()->with(['submission.competition', 'recipient.roles'])->find(
            $context['submission_reminder_id'] ?? null
        );
        if (! config('flowerflow.flags.submission_reminders')
            || ! $reminder
            || $reminder->recipient_user_id !== $recipient->id
            || ! $reminder->submission->isDraft()
            || ! $recipient->hasVerifiedEmail()
            || ! $recipient->hasExactRoles(['participant'])
            || $reminder->link_expires_at->isPast()
            || in_array($reminder->status, [SubmissionReminderStatus::Sent, SubmissionReminderStatus::Skipped], true)) {
            throw new CommunicationCancelledException('submission_reminder_invalid');
        }
        try {
            app(SubmissionFinalizationEligibility::class)->assertOpen($reminder->submission);
        } catch (ValidationException) {
            throw new CommunicationCancelledException('submission_reminder_closed');
        }

        return new SubmissionDraftReminder($reminder);
    }

    private function admissibilityUpdate(User $recipient, array $context): AdmissibilityUpdate
    {
        $review = EligibilityReview::query()->with('submission')->find($context['eligibility_review_id'] ?? null);
        $kind = $context['kind'] ?? null;
        $allowed = ['clarification_requested', 'residency_requested', 'response_received', 'admitted', 'not_admitted'];
        if (! $review || $review->submission->user_id !== $recipient->id || ! is_string($kind) || ! in_array($kind, $allowed, true)) {
            throw new CommunicationCancelledException('admissibility_event_invalid');
        }
        if (($kind === 'admitted' && $review->status !== EligibilityReviewStatus::Admitted)
            || ($kind === 'not_admitted' && $review->status !== EligibilityReviewStatus::NotAdmitted)
            || ($kind === 'clarification_requested' && ! $review->clarifications()->exists())
            || ($kind === 'residency_requested' && ! $review->residencyRequests()->exists())
            || ($kind === 'response_received' && ! $review->clarifications()->whereHas('responses')->exists())) {
            throw new CommunicationCancelledException('admissibility_state_changed');
        }

        return new AdmissibilityUpdate($review, $kind);
    }
}
