<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBulkJudgeAssignmentEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('flowerflow.flags.bulk_judge_assignment'), 404);

        return $next($request);
    }
}
