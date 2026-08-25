<?php

namespace App\Notifications;

use App\Models\JudgeAssignment;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class JudgeAssignmentBulkCreatedNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    /** @param list<int> $assignmentIds */
    public function __construct(public array $assignmentIds)
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
        $assignments = JudgeAssignment::query()
            ->whereIn('id', $this->assignmentIds)
            ->with('submissionVersion.submission.category:id,name')
            ->orderBy('id')
            ->get();
        $categories = $assignments
            ->groupBy(fn (JudgeAssignment $assignment): string => $assignment->submissionVersion->submission->category->name)
            ->map->count()
            ->sortKeys();
        $dueDates = $assignments
            ->map(fn (JudgeAssignment $assignment): string => $assignment->due_at
                ->timezone(config('flowerflow.timezone'))
                ->format('d/m/Y H:i'))
            ->unique()
            ->values();
        $data = [
            'assignmentCount' => $assignments->count(),
            'categories' => $categories,
            'dueDates' => $dueDates,
            'actionUrl' => route('judge.assignments.index'),
        ];

        return (new MailMessage)
            ->subject('Nuevas asignaciones de evaluación · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.judge-assignment-bulk-created', $data)
            ->text('mail.judge-assignment-bulk-created-text', $data);
    }
}
