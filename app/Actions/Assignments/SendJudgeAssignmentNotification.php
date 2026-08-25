<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeAssignment;
use App\Models\User;
use App\Notifications\JudgeAssignmentCreatedNotification;
use App\Services\AdministrativeJudgeEligibility;
use App\Services\AuditLogger;
use App\Services\ResilientMailDispatcher;

final class SendJudgeAssignmentNotification
{
    public function __construct(
        private ResilientMailDispatcher $mail,
        private AuditLogger $audit,
        private AdministrativeJudgeEligibility $judgeEligibility,
    ) {}

    public function execute(JudgeAssignment $assignment, User $actor): bool
    {
        if (! config('flowerflow.judge_notifications.assignment_enabled')) {
            $this->audit->record('assignment.notification_skipped', $assignment, $actor, [
                'assignment_id' => $assignment->id,
                'notification_requested' => true,
                'reason_code' => 'global_flag_disabled',
            ]);

            return false;
        }

        $assignment->loadMissing('judgeProfile.user.roles');
        if (! $this->judgeEligibility->isOperational($assignment->judgeProfile)) {
            $this->audit->record('assignment.notification_skipped', $assignment, $actor, [
                'assignment_id' => $assignment->id,
                'notification_requested' => true,
                'reason_code' => $assignment->judgeProfile->status === JudgeProfileStatus::PendingSetup
                    ? 'judge_setup_pending'
                    : 'judge_not_operational',
            ]);

            return false;
        }

        $sent = $this->mail->notify(
            $assignment->judgeProfile->user,
            new JudgeAssignmentCreatedNotification($assignment->id),
            'La asignación se guardó, pero no pudimos programar el correo al juez.',
            'judge-assignment-created:'.$assignment->public_id,
        );
        $this->audit->record($sent ? 'assignment.notification_requested' : 'assignment.notification_skipped', $assignment, $actor, [
            'assignment_id' => $assignment->id,
            'notification_requested' => true,
            'reason_code' => $sent ? 'queued' : 'mail_enqueue_failed',
        ]);

        return $sent;
    }
}
