<?php

namespace App\Http\Responses;

use App\Support\MailDispatchStatus;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class RegisterResponse implements RegisterResponseContract
{
    public function __construct(private MailDispatchStatus $mailStatus) {}

    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(array_filter([
                'message' => 'La cuenta se creó correctamente.',
                'warning' => $this->mailStatus->warning(),
            ]), 201);
        }

        $response = redirect()->intended(Fortify::redirects('register'));

        return $this->mailStatus->failed()
            ? $response->with('warning', $this->mailStatus->warning())
            : $response;
    }
}
