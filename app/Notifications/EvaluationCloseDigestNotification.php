<?php

namespace App\Notifications;

use App\Models\Competition;
use App\Models\JudgeProfile;
use App\Services\EvaluationWindow;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationCloseDigestNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(
        public int $judgeProfileId,
        public int $competitionId,
        public int $submitted,
        public int $pending,
        public int $conflictsReplacements,
        public int $cancelled,
    ) {
        $this->configureTransactionalMail();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $profile = JudgeProfile::query()->findOrFail($this->judgeProfileId);
        $competition = Competition::query()->findOrFail($this->competitionId);
        $data = [
            'submitted' => $this->submitted,
            'pending' => $this->pending,
            'conflicts_replacements' => $this->conflictsReplacements,
            'cancelled' => $this->cancelled,
            'closedAt' => app(EvaluationWindow::class)->evaluationCloseUtc()
                ->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i:s'),
            'actionUrl' => route('judge.assignments.index'),
        ];

        return (new MailMessage)
            ->subject('Resumen de cierre de evaluación · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.evaluation-close-digest', $data)
            ->text('mail.evaluation-close-digest-text', $data);
    }
}
