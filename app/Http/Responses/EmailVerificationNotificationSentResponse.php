<?php

namespace App\Http\Responses;

use App\Support\MailDispatchStatus;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as ResponseContract;

class EmailVerificationNotificationSentResponse implements ResponseContract
{
    public function __construct(private MailDispatchStatus $mailStatus) {}

    public function toResponse($request)
    {
        if ($this->mailStatus->failed()) {
            return $request->wantsJson()
                ? new JsonResponse(['message' => $this->mailStatus->warning()], 503)
                : back()->with('warning', $this->mailStatus->warning());
        }

        $message = 'Programamos un nuevo correo de verificación. Revisa también la carpeta de correo no deseado.';

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 202)
            : back()->with('status', $message);
    }
}
