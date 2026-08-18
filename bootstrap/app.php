<?php

use App\Http\Middleware\EnsureActiveJudge;
use App\Http\Middleware\EnsureAdmissibilityReviewEnabled;
use App\Http\Middleware\EnsureEvaluationEnabled;
use App\Http\Middleware\EnsureExclusiveBusinessRole;
use App\Http\Middleware\EnsurePanelEnabled;
use App\Http\Middleware\EnsureSubmissionsOpen;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(LocaleMiddleware::class);
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->alias([
            'submissions.open' => EnsureSubmissionsOpen::class,
            'panel.enabled' => EnsurePanelEnabled::class,
            'admissibility.enabled' => EnsureAdmissibilityReviewEnabled::class,
            'evaluation.enabled' => EnsureEvaluationEnabled::class,
            'judge.active' => EnsureActiveJudge::class,
            'business.role' => EnsureExclusiveBusinessRole::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
