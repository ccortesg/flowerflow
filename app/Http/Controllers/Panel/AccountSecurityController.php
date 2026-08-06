<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

class AccountSecurityController extends Controller
{
    public function enableTwoFactor(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $this->validateCurrentPassword($request);
        $enable($request->user());

        return back()->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED);
    }

    public function confirmTwoFactor(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $validated = $request->validateWithBag('confirmTwoFactorAuthentication', [
            'code' => ['required', 'string', 'size:6'],
        ]);
        $confirm($request->user(), $validated['code']);

        return back()->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $this->validateCurrentPassword($request);
        if (! $request->user()->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages([
                'two_factor' => 'Confirma primero la autenticación en dos pasos.',
            ])->errorBag('twoFactorAuthentication');
        }

        $generate($request->user());

        return back()->with('status', Fortify::RECOVERY_CODES_GENERATED);
    }

    public function disableTwoFactor(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $this->validateCurrentPassword($request);
        $disable($request->user());

        return back()->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED);
    }

    private function validateCurrentPassword(Request $request): void
    {
        $request->validateWithBag('twoFactorAuthentication', [
            'current_password' => ['required', 'string', 'current_password'],
        ]);
    }
}
