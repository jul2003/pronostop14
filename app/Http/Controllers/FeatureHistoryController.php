<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FeatureHistoryController extends Controller
{
    public function index(
        Request $request
    ) {
        $features =
            $request
                ->user()
                ->seenFeatures()
                ->orderByPivot(
                    'seen_at',
                    'desc'
                )
                ->limit(10)
                ->get();

        $announcements =
            $features
                ->map(
                    function ($feature) {
                        $snapshot =
                            json_decode(
                                (string)
                                $feature
                                    ->pivot
                                    ->snapshot,
                                true
                            );

                        /*
                         * Sécurité pour un ancien enregistrement
                         * sans snapshot.
                         */
                        if (! is_array($snapshot)) {
                            $snapshot = [
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
                                    $feature->details
                                    ?? [],

                                'action_label' =>
                                    $feature
                                        ->action_label,

                                'action_url' =>
                                    $feature
                                        ->action_url,
                            ];
                        }

                        return [
                            'feature_id' =>
                                $feature->id,

                            'seen_at' =>
                                Carbon::parse(
                                    $feature
                                        ->pivot
                                        ->seen_at
                                ),

                            'snapshot' =>
                                $snapshot,
                        ];
                    }
                )
                ->values();

        return view(
            'features.index',
            [
                'announcements' =>
                    $announcements,
            ]
        );
    }
}
