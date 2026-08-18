<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEvaluationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('flowerflow.flags.evaluation'), 404);

        return $next($request);
    }
}
