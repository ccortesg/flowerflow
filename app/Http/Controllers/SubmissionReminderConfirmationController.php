<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeSubmission;
use App\Enums\SubmissionReminderStatus;
use App\Http\Requests\SubmitSubmissionRequest;
use App\Models\Submission;
use App\Models\SubmissionReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class SubmissionReminderConfirmationController extends Controller
{
    public function show(Request $request, Submission $submission, SubmissionReminder $reminder): View
    {
        $this->assertReadable($submission, $reminder);

        return view('submissions.reminder-confirmation', [
            'submission' => $submission->load(['competition', 'category']),
            'reminder' => $reminder,
            'submitUrl' => URL::temporarySignedRoute(
                'submissions.reminders.submit',
                $reminder->link_expires_at,
                compact('submission', 'reminder'),
            ),
        ]);
    }

    public function store(
        SubmitSubmissionRequest $request,
        Submission $submission,
        SubmissionReminder $reminder,
        FinalizeSubmission $action,
    ): View {
        $this->assertReadable($submission, $reminder);
        $result = $action->executeFromReminder($submission, $reminder, $request->validated());

        return view('submissions.reminder-submitted', ['submission' => $result]);
    }

    private function assertReadable(Submission $submission, SubmissionReminder $reminder): void
    {
        abort_unless($reminder->submission_id === $submission->id
            && $reminder->recipient_user_id === $submission->user_id, 404);
        abort_if($reminder->consumed_at
            || ! $submission->isDraft()
            || $reminder->status !== SubmissionReminderStatus::Sent, 410, 'Este enlace ya no está disponible.');
        abort_if($reminder->link_expires_at->isPast(), 410, 'Este enlace ya venció.');
    }
}
