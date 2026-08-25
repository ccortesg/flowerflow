<?php

namespace App\Notifications;

use App\Models\EvaluationReopening;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationReopenedNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(public int $reopeningId)
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
        $reopening = EvaluationReopening::query()
            ->with(['evaluation.judgeAssignment', 'targetRevision'])
            ->findOrFail($this->reopeningId);
        $assignment = $reopening->evaluation->judgeAssignment;
        $data = [
            'assignmentPublicId' => $assignment->public_id,
            'revisionNumber' => $reopening->targetRevision->revision_number,
            'dueAt' => $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s'),
            'actionUrl' => route('judge.assignments.show', $assignment),
        ];

        return (new MailMessage)
            ->subject('Evaluación reabierta · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.evaluation-reopened', $data)
            ->text('mail.evaluation-reopened-text', $data);
    }
}
