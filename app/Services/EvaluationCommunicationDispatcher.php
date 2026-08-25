<?php

namespace App\Services;

use App\Enums\JudgeProfileStatus;
use App\Events\EvaluationReopened;
use App\Events\EvaluationSubmitted;
use App\Events\JudgeConflictDeclared;
use App\Events\JudgeConflictResolved;
use App\Models\EvaluationReopening;
use App\Models\EvaluationRevision;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
use App\Models\User;
use App\Notifications\EvaluationReopenedNotification;
use App\Notifications\EvaluationSubmittedNotification;
use App\Notifications\JudgeConflictDeclaredNotification;
use App\Notifications\JudgeConflictResolvedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

final class EvaluationCommunicationDispatcher
{
    public function __construct(
        private ResilientMailDispatcher $mail,
        private AuditLogger $audit,
    ) {}

    public function conflictDeclared(JudgeConflictDeclared $event): void
    {
        $conflict = JudgeConflict::query()->with('assignment')->find($event->conflictId);
        if (! $conflict) {
            return;
        }

        $this->safely($conflict, 'assignment.conflict_notification_skipped', $event->actorUserId, function () use ($conflict, $event): void {
            if ($reason = $this->disabledReason()) {
                $this->record($conflict, 'assignment.conflict_notification_skipped', $event->actorUserId, 'responsible_admin', $reason, [
                    'assignment_id' => $event->assignmentId,
                    'conflict_id' => $event->conflictId,
                ]);

                return;
            }

            $recipient = User::query()->with('roles')->find($conflict->assignment->assigned_by_user_id);
            if (! $this->eligibleAdmin($recipient, 'resolve evaluation conflicts')) {
                $this->record($conflict, 'assignment.conflict_notification_skipped', $event->actorUserId, 'responsible_admin', 'responsible_admin_ineligible', [
                    'assignment_id' => $event->assignmentId,
                    'conflict_id' => $event->conflictId,
                ]);

                return;
            }

            $this->dispatch(
                $conflict,
                $recipient,
                new JudgeConflictDeclaredNotification($conflict->id),
                'judge-conflict-declared:'.$conflict->public_id.':responsible-admin',
                'assignment.conflict_notification_requested',
                'assignment.conflict_notification_skipped',
                $event->actorUserId,
                'responsible_admin',
                ['assignment_id' => $event->assignmentId, 'conflict_id' => $event->conflictId],
            );
        });
    }

    public function conflictResolved(JudgeConflictResolved $event): void
    {
        $conflict = JudgeConflict::query()->with('assignment.judgeProfile.user.roles')->find($event->conflictId);
        if (! $conflict) {
            return;
        }

        $this->safely($conflict, 'assignment.conflict_resolution_notification_skipped', $event->actorUserId, function () use ($conflict, $event): void {
            if ($reason = $this->disabledReason()) {
                $this->record($conflict, 'assignment.conflict_resolution_notification_skipped', $event->actorUserId, 'outgoing_judge', $reason, [
                    'assignment_id' => $event->outgoingAssignmentId,
                    'replacement_assignment_id' => $event->replacementAssignmentId,
                    'conflict_id' => $event->conflictId,
                ]);

                return;
            }

            $profile = $conflict->assignment->judgeProfile;
            $recipient = $profile?->user;
            if (! $this->eligibleJudge($profile, $recipient)) {
                $this->record($conflict, 'assignment.conflict_resolution_notification_skipped', $event->actorUserId, 'outgoing_judge', 'outgoing_judge_ineligible', [
                    'assignment_id' => $event->outgoingAssignmentId,
                    'replacement_assignment_id' => $event->replacementAssignmentId,
                    'conflict_id' => $event->conflictId,
                ]);

                return;
            }

            $this->dispatch(
                $conflict,
                $recipient,
                new JudgeConflictResolvedNotification($conflict->id),
                'judge-conflict-resolved:'.$conflict->public_id.':outgoing-judge',
                'assignment.conflict_resolution_notification_requested',
                'assignment.conflict_resolution_notification_skipped',
                $event->actorUserId,
                'outgoing_judge',
                [
                    'assignment_id' => $event->outgoingAssignmentId,
                    'replacement_assignment_id' => $event->replacementAssignmentId,
                    'conflict_id' => $event->conflictId,
                ],
            );
        });
    }

    public function evaluationSubmitted(EvaluationSubmitted $event): void
    {
        $revision = EvaluationRevision::query()
            ->with(['evaluation.judgeAssignment.judgeProfile.user.roles', 'reopeningAsTarget'])
            ->find($event->revisionId);
        if (! $revision) {
            return;
        }

        $this->safely($revision, 'evaluation.submission_notification_skipped', $event->actorUserId, function () use ($revision, $event): void {
            if ($reason = $this->disabledReason()) {
                foreach (['subject_judge', 'responsible_admin'] as $purpose) {
                    $this->record($revision, 'evaluation.submission_notification_skipped', $event->actorUserId, $purpose, $reason, [
                        'evaluation_id' => $event->evaluationId,
                        'revision_id' => $event->revisionId,
                        'revision_number' => $revision->revision_number,
                    ]);
                }

                return;
            }

            $assignment = $revision->evaluation->judgeAssignment;
            $judgeProfile = $assignment->judgeProfile;
            $judge = $judgeProfile?->user;
            if ($this->eligibleJudge($judgeProfile, $judge)) {
                $this->dispatch(
                    $revision,
                    $judge,
                    new EvaluationSubmittedNotification($revision->id, 'subject_judge'),
                    'evaluation-submitted:'.$revision->public_id.':subject-judge',
                    'evaluation.submission_notification_requested',
                    'evaluation.submission_notification_skipped',
                    $event->actorUserId,
                    'subject_judge',
                    ['evaluation_id' => $event->evaluationId, 'revision_id' => $event->revisionId, 'revision_number' => $revision->revision_number],
                );
            } else {
                $this->record($revision, 'evaluation.submission_notification_skipped', $event->actorUserId, 'subject_judge', 'subject_judge_ineligible', [
                    'evaluation_id' => $event->evaluationId,
                    'revision_id' => $event->revisionId,
                    'revision_number' => $revision->revision_number,
                ]);
            }

            $responsibleId = $revision->revision_number === 1
                ? $assignment->assigned_by_user_id
                : $revision->reopeningAsTarget?->reopened_by_user_id;
            $admin = User::query()->with('roles')->find($responsibleId);
            if ($responsibleId === null || ! $this->eligibleAdmin($admin, 'view evaluations')) {
                $this->record($revision, 'evaluation.submission_notification_skipped', $event->actorUserId, 'responsible_admin', 'responsible_admin_ineligible', [
                    'evaluation_id' => $event->evaluationId,
                    'revision_id' => $event->revisionId,
                    'revision_number' => $revision->revision_number,
                ]);

                return;
            }

            $this->dispatch(
                $revision,
                $admin,
                new EvaluationSubmittedNotification($revision->id, 'responsible_admin'),
                'evaluation-submitted:'.$revision->public_id.':responsible-admin',
                'evaluation.submission_notification_requested',
                'evaluation.submission_notification_skipped',
                $event->actorUserId,
                'responsible_admin',
                ['evaluation_id' => $event->evaluationId, 'revision_id' => $event->revisionId, 'revision_number' => $revision->revision_number],
            );
        });
    }

    public function evaluationReopened(EvaluationReopened $event): void
    {
        $reopening = EvaluationReopening::query()
            ->with('subjectJudgeProfile.user.roles')
            ->find($event->reopeningId);
        if (! $reopening) {
            return;
        }

        $this->safely($reopening, 'evaluation.reopening_notification_skipped', $event->actorUserId, function () use ($reopening, $event): void {
            if ($reason = $this->disabledReason()) {
                $this->record($reopening, 'evaluation.reopening_notification_skipped', $event->actorUserId, 'subject_judge', $reason, [
                    'evaluation_id' => $event->evaluationId,
                    'source_revision_id' => $event->sourceRevisionId,
                    'target_revision_id' => $event->targetRevisionId,
                ]);

                return;
            }

            $profile = $reopening->subjectJudgeProfile;
            $recipient = $profile?->user;
            if (! $this->eligibleJudge($profile, $recipient)) {
                $this->record($reopening, 'evaluation.reopening_notification_skipped', $event->actorUserId, 'subject_judge', 'subject_judge_ineligible', [
                    'evaluation_id' => $event->evaluationId,
                    'source_revision_id' => $event->sourceRevisionId,
                    'target_revision_id' => $event->targetRevisionId,
                ]);

                return;
            }

            $this->dispatch(
                $reopening,
                $recipient,
                new EvaluationReopenedNotification($reopening->id),
                'evaluation-reopened:'.$reopening->targetRevision->public_id.':subject-judge',
                'evaluation.reopening_notification_requested',
                'evaluation.reopening_notification_skipped',
                $event->actorUserId,
                'subject_judge',
                [
                    'evaluation_id' => $event->evaluationId,
                    'source_revision_id' => $event->sourceRevisionId,
                    'target_revision_id' => $event->targetRevisionId,
                ],
            );
        });
    }

    private function disabledReason(): ?string
    {
        if (! config('flowerflow.flags.communication_ledger')) {
            return 'communication_ledger_disabled';
        }
        if (! config('flowerflow.flags.evaluation_notifications')) {
            return 'evaluation_notifications_disabled';
        }

        return null;
    }

    private function eligibleAdmin(?User $user, string $permission): bool
    {
        return $user !== null
            && $user->hasExactRoles(['admin'])
            && $user->hasVerifiedEmail()
            && $user->can($permission);
    }

    private function eligibleJudge(?JudgeProfile $profile, ?User $user): bool
    {
        return $profile !== null
            && $profile->status === JudgeProfileStatus::Active
            && $user !== null
            && $user->hasExactRoles(['judge'])
            && $user->hasVerifiedEmail()
            && $user->can('access judge workspace');
    }

    /** @param array<string,mixed> $metadata */
    private function dispatch(
        Model $subject,
        User $recipient,
        Notification $notification,
        string $sourceEventKey,
        string $requestedAction,
        string $skippedAction,
        int $actorUserId,
        string $variant,
        array $metadata,
    ): void {
        $queued = $this->mail->recordAndDispatch(
            $recipient,
            $notification,
            'El evento quedó registrado, pero no fue posible programar su comunicación.',
            $sourceEventKey,
        );
        $this->record(
            $subject,
            $queued ? $requestedAction : $skippedAction,
            $actorUserId,
            $variant,
            $queued ? 'queued' : 'mail_enqueue_failed',
            $metadata,
        );
    }

    /** @param array<string,mixed> $metadata */
    private function record(Model $subject, string $action, int $actorUserId, string $variant, string $reasonCode, array $metadata): void
    {
        $this->audit->record($action, $subject, User::query()->find($actorUserId), [
            ...$metadata,
            'variant' => $variant,
            'reason_code' => $reasonCode,
            'communication_ledger_enabled' => (bool) config('flowerflow.flags.communication_ledger'),
            'evaluation_notifications_enabled' => (bool) config('flowerflow.flags.evaluation_notifications'),
        ]);
    }

    private function safely(Model $subject, string $skippedAction, int $actorUserId, callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            $this->record($subject, $skippedAction, $actorUserId, 'listener', 'listener_failed', []);
            Log::warning('No se pudo programar una comunicación del ciclo de evaluación.', [
                'auditable_type' => $subject->getMorphClass(),
                'auditable_id' => $subject->getKey(),
                'reason_code' => 'listener_failed',
                'exception_class' => $exception::class,
            ]);
        }
    }
}
