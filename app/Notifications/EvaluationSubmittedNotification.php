<?php

namespace App\Notifications;

use App\Models\EvaluationRevision;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationSubmittedNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(public int $revisionId, public string $recipientPurpose)
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
        $revision = EvaluationRevision::query()->with('evaluation.judgeAssignment')->findOrFail($this->revisionId);
        $evaluation = $revision->evaluation;
        $assignment = $evaluation->judgeAssignment;
        $data = [
            'assignmentPublicId' => $assignment->public_id,
            'evaluationPublicId' => $evaluation->public_id,
            'revisionNumber' => $revision->revision_number,
            'submittedAt' => $revision->submitted_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s'),
            'submissionMode' => $revision->submission_mode->value === 'administrative'
                ? 'Registrada administrativamente por una cuenta autorizada'
                : 'Enviada por el juez',
            'actionUrl' => $this->recipientPurpose === 'responsible_admin'
                ? route('panel.evaluations.show', $evaluation)
                : route('judge.assignments.show', $assignment),
        ];

        return (new MailMessage)
            ->subject('Evaluación enviada · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.evaluation-submitted', $data)
            ->text('mail.evaluation-submitted-text', $data);
    }
}
