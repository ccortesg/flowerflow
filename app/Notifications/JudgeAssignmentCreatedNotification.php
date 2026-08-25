<?php

namespace App\Notifications;

use App\Models\JudgeAssignment;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JudgeAssignmentCreatedNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(public int $assignmentId)
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
        $assignment = JudgeAssignment::query()
            ->with('submissionVersion.submission.category:id,name')
            ->findOrFail($this->assignmentId);
        $data = [
            'userName' => $notifiable->name,
            'assignmentPublicId' => $assignment->public_id,
            'categoryName' => $assignment->submissionVersion->submission->category->name,
            'dueAt' => $assignment->due_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i'),
            'actionUrl' => route('judge.assignments.show', $assignment),
        ];

        return (new MailMessage)
            ->subject('Nueva asignación de evaluación · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.judge-assignment-created', $data)
            ->text('mail.judge-assignment-created-text', $data);
    }
}
