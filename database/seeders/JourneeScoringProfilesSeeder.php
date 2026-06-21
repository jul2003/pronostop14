<?php

namespace Database\Seeders;

use App\Models\JourneeTypeScoringProfile;
use App\Models\ScoringProfile;
use Illuminate\Database\Seeder;

class JourneeScoringProfilesSeeder extends Seeder
{
    public function run(): void
    {
        $regularRules = [
            ['code' => 'home_win', 'label' => 'Résultat juste — victoire domicile', 'points' => 2, 'position' => 10],
            ['code' => 'away_win', 'label' => 'Résultat juste — victoire extérieur', 'points' => 5, 'position' => 20],
            ['code' => 'draw', 'label' => 'Résultat juste — match nul', 'points' => 10, 'position' => 30],
            ['code' => 'tries_exact', 'label' => 'Nombre d’essais exact', 'points' => 2, 'position' => 40],
            ['code' => 'tries_near', 'label' => 'Nombre d’essais à +/- 1', 'points' => 1, 'position' => 50],
            ['code' => 'bonus_correct', 'label' => 'Bonus pronostiqué juste', 'points' => 2, 'position' => 60],
            ['code' => 'bonus_wrong', 'label' => 'Bonus pronostiqué faux', 'points' => -1, 'position' => 70],
            ['code' => 'perfect_round', 'label' => 'Bonus journée parfaite', 'points' => 3, 'position' => 80],
        ];

        $homeAwayNoDrawRules = [
            ['code' => 'home_win', 'label' => 'Résultat juste — victoire domicile', 'points' => 2, 'position' => 10],
            ['code' => 'away_win', 'label' => 'Résultat juste — victoire extérieur', 'points' => 5, 'position' => 20],
            ['code' => 'draw', 'label' => 'Résultat juste — match nul', 'points' => 0, 'position' => 30],
            ['code' => 'tries_exact', 'label' => 'Nombre d’essais exact', 'points' => 2, 'position' => 40],
            ['code' => 'tries_near', 'label' => 'Nombre d’essais à +/- 1', 'points' => 1, 'position' => 50],
            ['code' => 'bonus_correct', 'label' => 'Bonus pronostiqué juste', 'points' => 2, 'position' => 60],
            ['code' => 'bonus_wrong', 'label' => 'Bonus pronostiqué faux', 'points' => -1, 'position' => 70],
            ['code' => 'perfect_round', 'label' => 'Bonus journée parfaite', 'points' => 0, 'position' => 80],
        ];

        $neutralNoDrawRules = [
            ['code' => 'home_win', 'label' => 'Résultat juste — victoire équipe 1', 'points' => 5, 'position' => 10],
            ['code' => 'away_win', 'label' => 'Résultat juste — victoire équipe 2', 'points' => 5, 'position' => 20],
            ['code' => 'draw', 'label' => 'Résultat juste — match nul', 'points' => 0, 'position' => 30],
            ['code' => 'tries_exact', 'label' => 'Nombre d’essais exact', 'points' => 2, 'position' => 40],
            ['code' => 'tries_near', 'label' => 'Nombre d’essais à +/- 1', 'points' => 1, 'position' => 50],
            ['code' => 'bonus_correct', 'label' => 'Bonus pronostiqué juste', 'points' => 2, 'position' => 60],
            ['code' => 'bonus_wrong', 'label' => 'Bonus pronostiqué faux', 'points' => -1, 'position' => 70],
            ['code' => 'perfect_round', 'label' => 'Bonus journée parfaite', 'points' => 0, 'position' => 80],
        ];

        $profiles = [
            [
                'code' => 'journee_regular',
                'category' => 'journee',
                'stop_on_wrong_result' => true,
                'name' => 'Journée régulière',
                'description' => 'Barème standard pour les journées de championnat.',
                'position' => 10,
                'rules' => $regularRules,
            ],
            [
                'code' => 'journee_prod2_final',
                'category' => 'journee',
                'stop_on_wrong_result' => true,
                'name' => 'Finale PRO D2',
                'description' => 'Barème de la finale PRO D2 sur terrain neutre, match nul impossible.',
                'position' => 20,
                'rules' => $neutralNoDrawRules,
            ],
            [
                'code' => 'journee_access_match',
                'category' => 'journee',
                'stop_on_wrong_result' => true,
                'name' => 'Access match',
                'description' => 'Barème de l’access match. Le club PRO D2 reçoit, match nul impossible.',
                'position' => 30,
                'rules' => $homeAwayNoDrawRules,
            ],
            [
                'code' => 'journee_top14_playoff',
                'category' => 'journee',
                'stop_on_wrong_result' => true,
                'name' => 'Barrages TOP 14',
                'description' => 'Barème des barrages TOP 14. Le mieux classé reçoit, match nul impossible.',
                'position' => 40,
                'rules' => $homeAwayNoDrawRules,
            ],
            [
                'code' => 'journee_top14_semifinal',
                'category' => 'journee',
                'stop_on_wrong_result' => true,
                'name' => 'Demi-finales TOP 14',
                'description' => 'Barème des demi-finales TOP 14 sur terrain neutre, match nul impossible.',
                'position' => 50,
                'rules' => $neutralNoDrawRules,
            ],
            [
                'code' => 'journee_top14_final',
                'category' => 'journee',
                'stop_on_wrong_result' => true,
                'name' => 'Finale TOP 14',
                'description' => 'Barème de la finale TOP 14 sur terrain neutre, match nul impossible.',
                'position' => 60,
                'rules' => $neutralNoDrawRules,
            ],
        ];

        foreach ($profiles as $profileData) {
            $rules = $profileData['rules'];

            unset($profileData['rules']);

            $profile = ScoringProfile::updateOrCreate(
                ['code' => $profileData['code']],
                $profileData
            );

            $profile->rules()->delete();

            foreach ($rules as $rule) {
                $profile->rules()->create($rule);
            }
        }

        $mappings = [
            'regular' => 'journee_regular',
            'prod2_final' => 'journee_prod2_final',
            'access_match' => 'journee_access_match',
            'top14_playoff' => 'journee_top14_playoff',
            'top14_semifinal' => 'journee_top14_semifinal',
            'top14_final' => 'journee_top14_final',
        ];

        foreach ($mappings as $journeeType => $profileCode) {
            $profile = ScoringProfile::where('code', $profileCode)->first();

            if (! $profile) {
                continue;
            }

            JourneeTypeScoringProfile::updateOrCreate(
                ['journee_type' => $journeeType],
                ['scoring_profile_id' => $profile->id]
            );
        }
    }
}
