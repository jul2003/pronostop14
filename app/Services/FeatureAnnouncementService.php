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

    /**
     * Contenu exact qui sera envoyé à la modale.
     *
     * Ce même payload est enregistré dans
     * feature_user.snapshot.
     */
    public function payloadFor(
        Collection $features
    ): array {
        return $features
            ->map(
                fn (
                    Feature $feature
                ) => [
                    'id' =>
                        $feature->id,

                    'key' =>
                        $feature->key,

                    'title' =>
                        $feature->title,

                    'description' =>
                        $feature->description,

                    'icon' =>
                        $feature->icon,

                    'details' =>
                        $feature->details ?? [],

                    'action_label' =>
                        $feature->action_label,

                    'action_url' =>
                        $feature->action_url,
                ]
            )
            ->values()
            ->all();
    }

    public function markSeen(
        User $user,
        Collection $features,
        array $payloads
    ): void {
        if ($features->isEmpty()) {
            return;
        }

        $payloadsById =
            collect(
                $payloads
            )
                ->keyBy(
                    'id'
                );

        $now = now();

        $pivotData = [];

        foreach ($features as $feature) {
            $payload =
                $payloadsById
                    ->get(
                        $feature->id
                    );

            if (! $payload) {
                continue;
            }

            $pivotData[
                $feature->id
            ] = [
                'seen_at' =>
                    $now,

                'snapshot' =>
                    json_encode(
                        $payload,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    ),
            ];
        }

        if ($pivotData === []) {
            return;
        }

        $user
            ->seenFeatures()
            ->syncWithoutDetaching(
                $pivotData
            );
    }
}
