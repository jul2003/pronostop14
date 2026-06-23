<?php

namespace App\Services;

use Carbon\Carbon;

class AppDateService
{
    public function now(): Carbon
    {
        $settings = app(AppSettingService::class);

        $simulatedDate = $settings->date('simulated_app_date');

        if ($simulatedDate) {
            return $simulatedDate->copy()->setTimeFrom(now());
        }

        return now();
    }

    public function today(): Carbon
    {
        return $this->now()->startOfDay();
    }
}
