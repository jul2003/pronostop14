<?php

namespace Database\Seeders;

use App\Models\ScoringProfile;
use Illuminate\Database\Seeder;

class PreseasonScoringProfilesSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            [
                'code' => 'preseason_champion_top14',
                'category' => 'preseason',
                'name' => 'Avant-saison — Champion TOP 14',
                'description' => 'Pronostic du champion TOP 14.',
                'position' => 100,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 50,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_champion_automne_top14',
                'category' => 'preseason',
                'name' => 'Avant-saison — Champion d’automne TOP 14',
                'description' => 'Pronostic du champion d’automne TOP 14.',
                'position' => 110,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 25,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_demi_finaliste_top14',
                'category' => 'preseason',
                'name' => 'Avant-saison — Demi-finaliste TOP 14',
                'description' => 'Pronostic d’un demi-finaliste TOP 14.',
                'position' => 120,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 15,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_relegue_top14',
                'category' => 'preseason',
                'name' => 'Avant-saison — Relégué TOP 14',
                'description' => 'Pronostic du club relégué de TOP 14.',
                'position' => 130,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 15,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_barragiste_top14',
                'category' => 'preseason',
                'name' => 'Avant-saison — Barragiste TOP 14',
                'description' => 'Pronostic du club barragiste de TOP 14.',
                'position' => 140,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 15,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_vainqueur_accession',
                'category' => 'preseason',
                'name' => 'Avant-saison — Vainqueur accession',
                'description' => 'Pronostic du vainqueur du match d’accession.',
                'position' => 150,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 15,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_champion_prod2',
                'category' => 'preseason',
                'name' => 'Avant-saison — Champion PRO D2',
                'description' => 'Pronostic du champion PRO D2.',
                'position' => 160,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 15,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_demi_finaliste_prod2',
                'category' => 'preseason',
                'name' => 'Avant-saison — Demi-finaliste PRO D2',
                'description' => 'Pronostic d’un demi-finaliste PRO D2.',
                'position' => 170,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 15,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_meilleur_buteur_top14',
                'category' => 'preseason',
                'name' => 'Avant-saison — Meilleur buteur TOP 14',
                'description' => 'Pronostic du meilleur buteur TOP 14.',
                'position' => 180,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 10,
                        'position' => 10,
                    ],
                ],
            ],
            [
                'code' => 'preseason_meilleur_marqueur_essais_top14',
                'category' => 'preseason',
                'name' => 'Avant-saison — Meilleur marqueur d’essais TOP 14',
                'description' => 'Pronostic du meilleur marqueur d’essais TOP 14.',
                'position' => 190,
                'rules' => [
                    [
                        'code' => 'correct',
                        'label' => 'Bonne réponse',
                        'points' => 10,
                        'position' => 10,
                    ],
                ],
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
    }
}
