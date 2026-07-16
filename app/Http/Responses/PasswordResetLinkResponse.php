<?php

namespace App\Http\Responses;

use App\Support\MailDispatchStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public function __construct(private string $status, private MailDispatchStatus $mailStatus) {}

    public function toResponse($request)
    {
        if ($this->mailStatus->failed()) {
            return $request->wantsJson()
                ? new JsonResponse(['message' => $this->mailStatus->warning()], 503)
                : back()->withInput($request->only('email'))->with('warning', $this->mailStatus->warning());
        }

        if ($this->status === Password::RESET_THROTTLED) {
            return $request->wantsJson()
                ? new JsonResponse(['message' => trans($this->status)], 422)
                : back()->withInput($request->only('email'))->withErrors(['email' => trans($this->status)]);
        }

        $message = trans('passwords.sent');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 200)
            : back()->with('status', $message);
    }
}
