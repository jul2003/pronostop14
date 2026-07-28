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

    public function color(string $key, string $default = '#FFFFFF'): string
    {
        $value = strtoupper((string) $this->get($key, $default));

        return preg_match('/^#[0-9A-F]{6}$/', $value)
            ? $value
            : strtoupper($default);
    }

    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function time(string $key, string $default = '12:00'): string
    {
        $value = (string) $this->get($key, $default);

        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)
            ? $value
            : $default;
    }

    public function date(string $key): ?Carbon
    {
        $value = $this->get($key);

        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    public function dateTime(string $key): ?Carbon
    {
        $value = $this->get($key);

        if (! $value) {
            return null;
        }

        return Carbon::parse($value);
    }

    public function defaultFirstMatchTime(): string
    {
        return $this->time('default_first_match_time', '12:00');
    }

    public function upcomingJourneesToPrepareCount(): int
    {
        return max(1, $this->integer('upcoming_journees_to_prepare_count', 3));
    }
}
