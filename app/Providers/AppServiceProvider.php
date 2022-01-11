<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Passport:routes();
        $env = env('STAGE');
        $path = '';
        if ($env == 'local' || $env == 'production') {
            $path = 'telescope';
        }
        if ($env == 'development') {
            $path = 'public/telescope';
        }
        View::composer(['telescope::layout'], function ($view) use ($path) {
            $view->with('telescopeScriptVariables', [
                'path' => $path,
                'timezone' => config('app.timezone'),
                'recording' => !cache('telescope:pause-recording')
            ]);
        });
    }
}
