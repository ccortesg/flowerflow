<?php

namespace App\Providers;

use App\Listeners\ActivateVerifiedJudgeProfile;
use App\Support\MailDispatchStatus;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(MailDispatchStatus::class, fn () => new MailDispatchStatus);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());

        Event::listen(Verified::class, ActivateVerifiedJudgeProfile::class);

        RateLimiter::for('panel-mutations', function (Request $request) {
            $user = $request->user()?->getAuthIdentifier() ?? 'guest';
            $route = $request->route()?->getName() ?? 'unnamed';

            return Limit::perMinute(10)->by($user.'|'.$route);
        });

        RateLimiter::for('account-security', function (Request $request) {
            $user = $request->user()?->getAuthIdentifier() ?? 'guest';
            $route = $request->route()?->getName() ?? 'unnamed';

            return Limit::perMinute(6)->by($user.'|'.$route);
        });

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : ''),
                ];
            }

            return [];
        });
    }
}
