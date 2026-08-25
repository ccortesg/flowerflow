<?php

namespace App\Http\Controllers\Judge;

use App\Actions\Judges\ConsumeJudgeSetupLink;
use App\Exceptions\JudgeSetupLinkRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumeJudgeSetupLinkRequest;
use App\Models\JudgeSetupLink;
use App\Services\JudgeSetupLinkValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function show(JudgeSetupLink $setupLink, string $token, JudgeSetupLinkValidator $validator): View
    {
        try {
            $validator->assertValid($setupLink, $token);
        } catch (JudgeSetupLinkRejected $exception) {
            abort(410, $exception->getMessage());
        }

        return view('judge.setup', compact('setupLink'));
    }

    public function store(
        ConsumeJudgeSetupLinkRequest $request,
        JudgeSetupLink $setupLink,
        string $token,
        ConsumeJudgeSetupLink $consume,
    ): RedirectResponse {
        try {
            $consume->execute($setupLink, $token, $request->string('password')->toString());
        } catch (JudgeSetupLinkRejected $exception) {
            abort(410, $exception->getMessage());
        }

        return redirect()->route('login', ['context' => 'judge'])
            ->with('status', 'Tu contraseña quedó configurada y tu correo fue verificado. Ya puedes iniciar sesión.');
    }
}
