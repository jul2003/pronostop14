<?php

namespace App\Models;

use App\Services\AppSettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = [
        'key',
        'title',
        'description',
        'icon',
        'details',
        'is_active',
        'activation_setting_key',
        'action_label',
        'action_url',
        'position',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'details' =>
                'array',

            'is_active' =>
                'boolean',

            'starts_at' =>
                'datetime',

            'ends_at' =>
                'datetime',
        ];
    }

    public function usersWhoHaveSeen()
    {
        return $this
            ->belongsToMany(
                User::class,
                'feature_user'
            )
            ->withPivot(
                'seen_at'
            )
            ->withTimestamps();
    }

    public function scopeCurrentlyAvailable(
        Builder $query
    ): Builder {
        $now = now();

        return $query
            ->where(
                'is_active',
                true
            )
            ->where(
                function (
                    Builder $query
                ) use ($now) {
                    $query
                        ->whereNull(
                            'starts_at'
                        )
                        ->orWhere(
                            'starts_at',
                            '<=',
                            $now
                        );
                }
            )
            ->where(
                function (
                    Builder $query
                ) use ($now) {
                    $query
                        ->whereNull(
                            'ends_at'
                        )
                        ->orWhere(
                            'ends_at',
                            '>',
                            $now
                        );
                }
            );
    }

    public function isEnabledByAppSetting(
        AppSettingService $settings
    ): bool {
        if (
            ! $this
                ->activation_setting_key
        ) {
            return true;
        }

        return $settings->boolean(
            $this->activation_setting_key,
            false
        );
    }
}
