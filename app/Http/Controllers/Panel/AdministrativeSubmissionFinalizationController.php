<?php

namespace App\Http\Controllers\Panel;

use App\Actions\FinalizeSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdministrativeFinalizeSubmissionRequest;
use App\Models\Submission;
use App\Support\MailDispatchStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdministrativeSubmissionFinalizationController extends Controller
{
    public function show(Submission $submission): View
    {
        $this->authorize('administrativelyFinalize', $submission);
        abort_unless($submission->isDraft(), 409, 'La propuesta ya no está disponible para registro administrativo.');

        return view('panel.submissions.administrative-finalization', [
            'submission' => $submission->load(['user', 'category', 'competition', 'files']),
        ]);
    }

    public function store(
        AdministrativeFinalizeSubmissionRequest $request,
        Submission $submission,
        FinalizeSubmission $action,
        MailDispatchStatus $mailStatus,
    ): RedirectResponse {
        $result = $action->executeAdministratively(
            $submission,
            $request->user(),
            $request->string('reason')->toString(),
        );
        $response = redirect()->route('panel.submissions.show', $result)->with(
            'status',
            'La propuesta quedó registrada administrativamente con el folio '.$result->folio.'.',
        );

        return $mailStatus->failed()
            ? $response->with('warning', $mailStatus->warning())
            : $response;
    }
}
