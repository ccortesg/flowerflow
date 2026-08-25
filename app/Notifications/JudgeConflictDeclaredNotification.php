<?php

namespace App\Notifications;

use App\Models\JudgeConflict;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JudgeConflictDeclaredNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(public int $conflictId)
    {
        $this->configureTransactionalMail();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $conflict = JudgeConflict::query()
            ->with('assignment.submissionVersion.submission.category:id,name')
            ->findOrFail($this->conflictId);
        $assignment = $conflict->assignment;
        $data = [
            'assignmentPublicId' => $assignment->public_id,
            'categoryName' => $assignment->submissionVersion->submission->category->name,
            'declaredAt' => $conflict->declared_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s'),
            'actionUrl' => route('panel.assignments.show', $assignment->submissionVersion->submission),
        ];

        return (new MailMessage)
            ->subject('Conflicto de evaluación pendiente · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.judge-conflict-declared', $data)
            ->text('mail.judge-conflict-declared-text', $data);
    }
}
