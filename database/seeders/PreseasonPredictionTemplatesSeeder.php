<?php

namespace Database\Seeders;

use App\Models\PreseasonPredictionTemplate;
use App\Models\ScoringProfile;
use Illuminate\Database\Seeder;

class PreseasonPredictionTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = ScoringProfile::where('category', 'preseason')
            ->get()
            ->keyBy('code');

        $templates = [
            [
                'label' => 'Champion TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_champion_top14',
                'position' => 10,
            ],
            [
                'label' => 'Champion d’automne TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_champion_automne_top14',
                'position' => 20,
            ],

            [
                'label' => 'Demi-finaliste TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_demi_finaliste_top14',
                'position' => 30,
            ],
            [
                'label' => 'Demi-finaliste TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_demi_finaliste_top14',
                'position' => 40,
            ],
            [
                'label' => 'Demi-finaliste TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_demi_finaliste_top14',
                'position' => 50,
            ],
            [
                'label' => 'Demi-finaliste TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_demi_finaliste_top14',
                'position' => 60,
            ],

            [
                'label' => 'Relégué TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_relegue_top14',
                'position' => 70,
            ],
            [
                'label' => 'Barragiste TOP 14',
                'answer_type' => 'top14_club',
                'profile_code' => 'preseason_barragiste_top14',
                'position' => 80,
            ],
            [
                'label' => 'Vainqueur accession',
                'answer_type' => 'season_club',
                'profile_code' => 'preseason_vainqueur_accession',
                'position' => 90,
            ],
            [
                'label' => 'Champion PRO D2',
                'answer_type' => 'prod2_club',
                'profile_code' => 'preseason_champion_prod2',
                'position' => 100,
            ],

            [
                'label' => 'Demi-finaliste PRO D2',
                'answer_type' => 'prod2_club',
                'profile_code' => 'preseason_demi_finaliste_prod2',
                'position' => 110,
            ],
            [
                'label' => 'Demi-finaliste PRO D2',
                'answer_type' => 'prod2_club',
                'profile_code' => 'preseason_demi_finaliste_prod2',
                'position' => 120,
            ],
            [
                'label' => 'Demi-finaliste PRO D2',
                'answer_type' => 'prod2_club',
                'profile_code' => 'preseason_demi_finaliste_prod2',
                'position' => 130,
            ],
            [
                'label' => 'Demi-finaliste PRO D2',
                'answer_type' => 'prod2_club',
                'profile_code' => 'preseason_demi_finaliste_prod2',
                'position' => 140,
            ],

            [
                'label' => 'Meilleur buteur TOP 14',
                'answer_type' => 'free_text',
                'profile_code' => 'preseason_meilleur_buteur_top14',
                'position' => 150,
            ],
            [
                'label' => 'Meilleur marqueur d’essais TOP 14',
                'answer_type' => 'free_text',
                'profile_code' => 'preseason_meilleur_marqueur_essais_top14',
                'position' => 160,
            ],
        ];

        foreach ($templates as $template) {
            $profile = $profiles->get($template['profile_code']);

            if (! $profile) {
                continue;
            }

            PreseasonPredictionTemplate::updateOrCreate(
                [
                    'label' => $template['label'],
                    'position' => $template['position'],
                ],
                [
                    'answer_type' => $template['answer_type'],
                    'scoring_profile_id' => $profile->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
