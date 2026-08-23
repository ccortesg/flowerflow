<?php

namespace App\Http\Controllers\Panel;

use App\Actions\QueueSubmissionReminders;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\SubmissionReminderPreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionReminderController extends Controller
{
    public function create(Request $request, SubmissionReminderPreview $preview): View
    {
        abort_unless($request->user()->hasExactRoles(['admin']) && $request->user()->can('send submission reminders'), 403);

        return view('panel.submissions.reminders.create', $preview->summarize());
    }

    public function store(Request $request, QueueSubmissionReminders $action): RedirectResponse
    {
        abort_unless($request->user()->hasExactRoles(['admin']) && $request->user()->can('send submission reminders'), 403);
        $batch = $action->execute($request->user());

        return redirect()->route('panel.submissions.index')->with(
            $batch->queued_count > 0 ? 'status' : 'warning',
            $this->message($batch->queued_count, $batch->skipped_count),
        );
    }

    public function storeForSubmission(
        Request $request,
        Submission $submission,
        QueueSubmissionReminders $action,
    ): RedirectResponse {
        $this->authorize('sendReminder', $submission);
        $batch = $action->execute($request->user(), $submission);

        return back()->with(
            $batch->queued_count > 0 ? 'status' : 'warning',
            $this->message($batch->queued_count, $batch->skipped_count),
        );
    }

    private function message(int $queued, int $skipped): string
    {
        if ($queued === 0) {
            return 'No se programaron correos. Las propuestas fueron omitidas por estado, destinatario o cooldown.';
        }

        return "Se programaron {$queued} recordatorios; {$skipped} propuestas fueron omitidas.";
    }
}
