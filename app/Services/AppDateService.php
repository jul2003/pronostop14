<?php

namespace App\Services;

use Carbon\Carbon;

class AppDateService
{
    public function now(): Carbon
    {
        $settings = app(AppSettingService::class);

        $simulatedDateTime = $settings->dateTime('simulated_app_date');

        if ($simulatedDateTime) {
            return $simulatedDateTime->copy();
        }

        return now();
    }

    public function today(): Carbon
    {
        return $this->now()->copy()->startOfDay();
    }
}
