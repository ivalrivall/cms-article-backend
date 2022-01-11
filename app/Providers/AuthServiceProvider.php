<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $agent = new Agent();
        $this->registerPolicies();
        Passport::routes(function ($q) {
            $q->forAccessTokens();
            $q->forPersonalAccessTokens();
            $q->forTransientTokens();
        });
        if ($agent->isDesktop()) {
            // \Log::info('agent desktop => '.json_encode($agent));
            Passport::tokensExpireIn(now()->addHours(1));
            Passport::refreshTokensExpireIn(now()->addHours(2));
        } else {
            // \Log::info('agent non desktop  => '.json_encode($agent));
            Passport::tokensExpireIn(now()->addDays(1));
            Passport::refreshTokensExpireIn(now()->addDays(2));
        }
    }
}
