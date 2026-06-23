<?php

namespace App\Services;

use App\Models\AppSetting;
use Carbon\Carbon;

class AppSettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->typedValue();
    }

    public function string(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function date(string $key): ?Carbon
    {
        $value = $this->get($key);

        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    public function upcomingJourneesToPrepareCount(): int
    {
        return max(1, $this->integer('upcoming_journees_to_prepare_count', 3));
    }
}
