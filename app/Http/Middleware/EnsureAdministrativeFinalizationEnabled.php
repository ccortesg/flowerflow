<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrativeFinalizationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('flowerflow.flags.administrative_finalization'), 404);

        return $next($request);
    }
}
