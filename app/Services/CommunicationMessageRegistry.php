<?php

namespace App\Services;

use App\Enums\CommunicationType;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeProfileStatus;
use App\Enums\SubmissionReminderStatus;
use App\Exceptions\CommunicationCancelledException;
use App\Mail\AdmissibilityUpdate;
use App\Mail\SubmissionAdministrativelyFinalized;
use App\Mail\SubmissionDraftReminder;
use App\Mail\SubmissionReceived;
use App\Models\CommunicationDelivery;
use App\Models\EligibilityReview;
use App\Models\Submission;
use App\Models\SubmissionReminder;
use App\Models\User;
use App\Notifications\JudgeAccountSetupNotification;
use App\Notifications\JudgeAccountStatusNotification;
use App\Notifications\JudgeVerifyEmailNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class CommunicationMessageRegistry
{
    /** @return array{type: CommunicationType, variant: ?string, context: array<string, mixed>, related_type: ?string, related_id: ?int} */
    public function describe(User $recipient, Mailable|Notification $message): array
    {
        return match (true) {
            $message instanceof JudgeAccountSetupNotification => [
                'type' => CommunicationType::JudgeAccountSetup,
                'variant' => null,
                'context' => ['token' => $message->token],
                'related_type' => User::class,
                'related_id' => $recipient->id,
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
            CommunicationType::AccountPasswordReset => $this->passwordReset($recipient, $context, false),
            CommunicationType::JudgeAccountSetup => $this->passwordReset($recipient, $context, true),
            CommunicationType::JudgeEmailVerification => $this->judgeVerification($recipient),
            CommunicationType::JudgeAccountStatus => $this->judgeStatus($recipient, $context),
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

    private function passwordReset(User $recipient, array $context, bool $judgeSetup): Notification
    {
        $token = $context['token'] ?? null;
        if (! is_string($token)
            || ! Password::broker('users')->tokenExists($recipient, $token)
            || ($judgeSetup && ! $recipient->hasExactRoles(['judge']))) {
            throw new CommunicationCancelledException($judgeSetup ? 'judge_setup_token_invalid' : 'password_reset_token_invalid');
        }

        return $judgeSetup
            ? new JudgeAccountSetupNotification($token)
            : new ResetPasswordNotification($token);
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
