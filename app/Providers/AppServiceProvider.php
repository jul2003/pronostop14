<?php

namespace App\Providers;

use App\Models\Journee;
use App\Models\MatchGame;
use App\Observers\JourneeObserver;
use App\Observers\MatchGameObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::setRememberDuration(1440); //1 jour : 1 * 24 * 60 = 1440 minutes

        Journee::observe(
            JourneeObserver::class
        );

        MatchGame::observe(
            MatchGameObserver::class
        );

        if (
            app()->environment([
                'production',
                'staging',
            ])
        ) {
            URL::forceScheme(
                'https'
            );
        }
    }
}
