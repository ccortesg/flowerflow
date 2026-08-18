<?php

namespace App\Http\Middleware;

use App\Enums\BusinessRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExclusiveBusinessRole
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $roles = $user->getRoleNames()->values();
        $role = $roles->count() === 1 ? $roles->first() : null;
        $isKnownRole = is_string($role) && in_array($role, BusinessRole::values(), true);

        abort_unless($isKnownRole && in_array($role, $allowedRoles, true), 403);

        return $next($request);
    }
}
