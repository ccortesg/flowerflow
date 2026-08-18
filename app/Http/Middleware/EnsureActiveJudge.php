<?php

namespace App\Http\Middleware;

use App\Enums\JudgeProfileStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveJudge
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $profile = $user?->judgeProfile()->first();

        if (! $profile
            || $profile->status !== JudgeProfileStatus::Active
            || ! $profile->password_initialized_at
            || ! $user->hasVerifiedEmail()) {
            return redirect()->route('judge.status');
        }

        return $next($request);
    }
}
