<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as ResponseContract;

class VerifyEmailResponse implements ResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => 'Tu correo electrónico se verificó correctamente.'], 200)
            : redirect()->route('verification.success');
    }
}
