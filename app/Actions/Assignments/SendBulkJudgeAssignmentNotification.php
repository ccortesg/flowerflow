<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\User;
use App\Notifications\JudgeAssignmentBulkCreatedNotification;
use App\Services\AdministrativeJudgeEligibility;
use App\Services\AuditLogger;
use App\Services\ResilientMailDispatcher;

final class SendBulkJudgeAssignmentNotification
{
    public function __construct(
        private ResilientMailDispatcher $mail,
        private AuditLogger $audit,
        private AdministrativeJudgeEligibility $judgeEligibility,
    ) {}

    /** @param list<int> $assignmentIds */
    public function execute(
        JudgeProfile $judge,
        array $assignmentIds,
        User $actor,
        string $operationId,
        bool $requested,
    ): bool {
        $reasonCode = null;
        if (! $requested) {
            $reasonCode = 'not_requested';
        } elseif (! $this->judgeEligibility->isOperational($judge)) {
            $reasonCode = $judge->status === JudgeProfileStatus::PendingSetup
                ? 'judge_setup_pending'
                : 'judge_not_operational';
        } elseif ($assignmentIds === []) {
            $reasonCode = 'no_new_assignments';
        } elseif (! config('flowerflow.flags.communication_ledger')) {
            $reasonCode = 'communication_ledger_disabled';
        } elseif (! config('flowerflow.judge_notifications.assignment_enabled')) {
            $reasonCode = 'assignment_notification_disabled';
        }

        if ($reasonCode !== null) {
            $this->audit->record('assignment.bulk_notification_skipped', $judge, $actor, [
                'operation_id' => $operationId,
                'judge_profile_id' => $judge->id,
                'assignment_count' => count($assignmentIds),
                'notification_requested' => $requested,
                'reason_code' => $reasonCode,
            ]);

            return false;
        }

        $judge->loadMissing('user');
        $queued = $this->mail->recordAndDispatch(
            $judge->user,
            new JudgeAssignmentBulkCreatedNotification(array_values(array_unique($assignmentIds))),
            'Las asignaciones se guardaron, pero no pudimos programar el correo consolidado al juez.',
            'judge-assignment-bulk-created:'.$operationId.':'.$judge->public_id,
        );
        $this->audit->record(
            $queued ? 'assignment.bulk_notification_requested' : 'assignment.bulk_notification_skipped',
            $judge,
            $actor,
            [
                'operation_id' => $operationId,
                'judge_profile_id' => $judge->id,
                'assignment_count' => count($assignmentIds),
                'notification_requested' => true,
                'reason_code' => $queued ? 'queued' : 'mail_enqueue_failed',
            ],
        );

        return $queued;
    }
}
