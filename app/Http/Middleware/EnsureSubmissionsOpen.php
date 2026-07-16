<?php

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubmissionsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $closesAt = CarbonImmutable::parse(
            config('flowerflow.submissions_close_at'),
            config('flowerflow.timezone')
        );

        abort_unless(config('flowerflow.flags.submissions') && now()->lessThanOrEqualTo($closesAt), 503,
            'La recepción de propuestas no está habilitada.');

        return $next($request);
    }
}
