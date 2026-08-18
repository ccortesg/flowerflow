<?php

namespace App\Http\Controllers\Panel;

use App\Actions\Judges\CreateJudgeAccount;
use App\Actions\Judges\ReactivateJudge;
use App\Actions\Judges\RecoverJudgeTwoFactor;
use App\Actions\Judges\SendJudgeSetupNotification;
use App\Actions\Judges\SuspendJudge;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReactivateJudgeRequest;
use App\Http\Requests\RecoverJudgeTwoFactorRequest;
use App\Http\Requests\StoreJudgeRequest;
use App\Http\Requests\SuspendJudgeRequest;
use App\Models\JudgeProfile;
use App\Support\MailDispatchStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JudgeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', JudgeProfile::class);

        return view('panel.judges.index', [
            'judgeProfiles' => JudgeProfile::query()
                ->with('user:id,public_id,name,email,email_verified_at')
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', JudgeProfile::class);

        return view('panel.judges.create');
    }

    public function store(StoreJudgeRequest $request, CreateJudgeAccount $createJudge): RedirectResponse
    {
        $profile = $createJudge->execute(
            $request->user(),
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            JudgeAssignmentRole::from($request->string('assignment_role')->toString()),
        );

        return $this->mailAwareResponse(
            redirect()->route('panel.judges.show', $profile)
                ->with('status', 'La cuenta de juez quedó creada en configuración pendiente.'),
        );
    }

    public function show(JudgeProfile $judgeProfile): View
    {
        Gate::authorize('view', $judgeProfile);

        return view('panel.judges.show', [
            'judgeProfile' => $judgeProfile->load(['user', 'createdBy:id,name']),
        ]);
    }

    public function resendSetup(Request $request, JudgeProfile $judgeProfile, SendJudgeSetupNotification $send): RedirectResponse
    {
        Gate::authorize('manage', $judgeProfile);
        if ($judgeProfile->status !== JudgeProfileStatus::PendingSetup) {
            throw ValidationException::withMessages(['judge' => 'El correo de configuración sólo puede reenviarse mientras el perfil está pendiente.']);
        }

        $send->execute($judgeProfile);

        return $this->mailAwareResponse(back()->with('status', 'Se solicitó un nuevo correo de configuración.'));
    }

    public function suspend(SuspendJudgeRequest $request, JudgeProfile $judgeProfile, SuspendJudge $suspend): RedirectResponse
    {
        $suspend->execute($judgeProfile, $request->user(), $request->string('reason')->toString());

        return $this->mailAwareResponse(back()->with('status', 'La cuenta de juez quedó suspendida y sus sesiones fueron revocadas.'));
    }

    public function reactivate(ReactivateJudgeRequest $request, JudgeProfile $judgeProfile, ReactivateJudge $reactivate): RedirectResponse
    {
        $profile = $reactivate->execute($judgeProfile, $request->user(), $request->string('reason')->toString());

        return $this->mailAwareResponse(back()->with(
            'status',
            $profile->status === JudgeProfileStatus::Active
                ? 'La cuenta de juez quedó reactivada.'
                : 'La suspensión terminó, pero la cuenta volvió a configuración pendiente porque faltan prerrequisitos.',
        ));
    }

    public function recoverTwoFactor(
        RecoverJudgeTwoFactorRequest $request,
        JudgeProfile $judgeProfile,
        RecoverJudgeTwoFactor $recover,
    ): RedirectResponse {
        $recover->execute($judgeProfile, $request->user(), $request->string('reason')->toString());

        return $this->mailAwareResponse(back()->with('status', 'El material 2FA fue eliminado y las sesiones del juez quedaron revocadas.'));
    }

    private function mailAwareResponse(RedirectResponse $response): RedirectResponse
    {
        $mailStatus = app(MailDispatchStatus::class);

        return $mailStatus->failed() ? $response->with('warning', $mailStatus->warning()) : $response;
    }
}
