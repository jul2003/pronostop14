<?php

namespace App\Providers;

use App\Models\Journee;
use App\Models\MatchGame;
use App\Observers\JourneeObserver;
use App\Observers\MatchGameObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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
