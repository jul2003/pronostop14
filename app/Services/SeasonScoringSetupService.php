<?php

namespace App\Services;

use App\Models\JourneeTypeScoringProfile;
use App\Models\ScoringProfile;
use App\Models\Season;
use App\Models\SeasonJourneeTypeScoringProfile;
use App\Models\SeasonScoringProfile;
use Illuminate\Support\Facades\DB;

class SeasonScoringSetupService
{
    public function copyJourneeScoringProfilesToSeason(Season $season): void
    {
        DB::transaction(function () use ($season) {
            if ($season->scoringProfiles()->exists()) {
                return;
            }

            $globalProfiles = ScoringProfile::with('rules')
                ->where('category', 'journee')
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $globalToSeasonProfileIds = [];

            foreach ($globalProfiles as $globalProfile) {
                $seasonProfile = SeasonScoringProfile::create([
                    'season_id' => $season->id,
                    'source_profile_id' => $globalProfile->id,
                    'stop_on_wrong_result' => $globalProfile->stop_on_wrong_result,
                    'code' => $globalProfile->code,
                    'name' => $globalProfile->name,
                    'description' => $globalProfile->description,
                    'position' => $globalProfile->position,
                ]);

                $globalToSeasonProfileIds[$globalProfile->id] = $seasonProfile->id;

                foreach ($globalProfile->rules as $rule) {
                    $seasonProfile->rules()->create([
                        'season_id' => $season->id,
                        'code' => $rule->code,
                        'label' => $rule->label,
                        'points' => $rule->points,
                        'position' => $rule->position,
                    ]);
                }
            }

            $mappings = JourneeTypeScoringProfile::query()
                ->orderBy('journee_type')
                ->get();

            foreach ($mappings as $mapping) {
                $seasonProfileId = $globalToSeasonProfileIds[$mapping->scoring_profile_id] ?? null;

                if (! $seasonProfileId) {
                    continue;
                }

                SeasonJourneeTypeScoringProfile::updateOrCreate(
                    [
                        'season_id' => $season->id,
                        'journee_type' => $mapping->journee_type,
                    ],
                    [
                        'season_scoring_profile_id' => $seasonProfileId,
                    ]
                );
            }
        });
    }

    public function copyFromSeason(Season $sourceSeason, Season $targetSeason): void
    {
        DB::transaction(function () use ($sourceSeason, $targetSeason) {
            if ($targetSeason->scoringProfiles()->exists()) {
                return;
            }

            $sourceSeason->load([
                'scoringProfiles.rules',
                'journeeTypeScoringProfiles',
            ]);

            $sourceToTargetProfileIds = [];

            foreach ($sourceSeason->scoringProfiles as $sourceProfile) {
                $targetProfile = SeasonScoringProfile::create([
                    'season_id' => $targetSeason->id,
                    'source_profile_id' => $sourceProfile->source_profile_id,
                    'stop_on_wrong_result' => $sourceProfile->stop_on_wrong_result,
                    'code' => $sourceProfile->code,
                    'name' => $sourceProfile->name,
                    'description' => $sourceProfile->description,
                    'position' => $sourceProfile->position,
                ]);

                $sourceToTargetProfileIds[$sourceProfile->id] = $targetProfile->id;

                foreach ($sourceProfile->rules as $sourceRule) {
                    $targetProfile->rules()->create([
                        'season_id' => $targetSeason->id,
                        'code' => $sourceRule->code,
                        'label' => $sourceRule->label,
                        'points' => $sourceRule->points,
                        'position' => $sourceRule->position,
                    ]);
                }
            }

            foreach ($sourceSeason->journeeTypeScoringProfiles as $sourceMapping) {
                $targetProfileId = $sourceToTargetProfileIds[$sourceMapping->season_scoring_profile_id] ?? null;

                if (! $targetProfileId) {
                    continue;
                }

                SeasonJourneeTypeScoringProfile::updateOrCreate(
                    [
                        'season_id' => $targetSeason->id,
                        'journee_type' => $sourceMapping->journee_type,
                    ],
                    [
                        'season_scoring_profile_id' => $targetProfileId,
                    ]
                );
            }
        });
    }
}
