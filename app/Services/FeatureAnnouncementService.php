<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\User;
use Illuminate\Support\Collection;

class FeatureAnnouncementService
{
    public function __construct(
        private readonly AppSettingService $settings
    ) {
    }

    public function unseenFor(
        User $user
    ): Collection {
        $seenFeatureIds =
            $user
                ->seenFeatures()
                ->pluck(
                    'features.id'
                );

        return Feature::query()
            ->currentlyAvailable()
            ->whereNotIn(
                'id',
                $seenFeatureIds
            )
            ->orderBy(
                'position'
            )
            ->orderBy(
                'id'
            )
            ->get()
            ->filter(
                fn (
                    Feature $feature
                ) =>
                    $feature
                        ->isEnabledByAppSetting(
                            $this->settings
                        )
            )
            ->values();
    }

    public function markSeen(
        User $user,
        Collection $features
    ): void {
        if ($features->isEmpty()) {
            return;
        }

        $now = now();

        $pivotData = [];

        foreach ($features as $feature) {
            $pivotData[
                $feature->id
            ] = [
                'seen_at' =>
                    $now,
            ];
        }

        $user
            ->seenFeatures()
            ->syncWithoutDetaching(
                $pivotData
            );
    }
}
