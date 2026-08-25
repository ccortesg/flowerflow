<?php

namespace App\Notifications;

use App\Models\JudgeConflict;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JudgeConflictResolvedNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
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
        $conflict = JudgeConflict::query()->with('assignment')->findOrFail($this->conflictId);
        $data = [
            'assignmentPublicId' => $conflict->assignment->public_id,
            'resolvedAt' => $conflict->resolved_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s'),
            'actionUrl' => route('judge.assignments.index'),
        ];

        return (new MailMessage)
            ->subject('Conflicto de evaluación resuelto · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.judge-conflict-resolved', $data)
            ->text('mail.judge-conflict-resolved-text', $data);
    }
}
