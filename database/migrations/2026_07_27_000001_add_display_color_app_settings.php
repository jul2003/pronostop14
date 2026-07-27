<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $settings = [
        [
            'key' => 'results_color_bonus_correct',
            'value' => '#D1E7DD',
            'label' => 'Résultats - bonus juste',
            'description' => 'Couleur utilisée quand un pronostic de bonus offensif/défensif est juste.',
            'position' => 250,
        ],
        [
            'key' => 'results_color_bonus_wrong',
            'value' => '#F8D7DA',
            'label' => 'Résultats - bonus faux',
            'description' => 'Couleur utilisée quand un pronostic de bonus offensif/défensif est faux.',
            'position' => 251,
        ],
        [
            'key' => 'bilan_color_highest_journee_score',
            'value' => '#D1E7DD',
            'label' => 'Bilan - plus gros score journée',
            'description' => 'Couleur utilisée pour mettre en avant le plus gros score sur une journée.',
            'position' => 300,
        ],
        [
            'key' => 'bilan_color_lowest_journee_score',
            'value' => '#F8D7DA',
            'label' => 'Bilan - plus petit score journée',
            'description' => 'Couleur utilisée pour mettre en avant le plus petit score sur une journée.',
            'position' => 301,
        ],
        [
            'key' => 'bilan_color_highest_cumulative_score',
            'value' => '#CFE2FF',
            'label' => 'Bilan - plus gros score cumulé',
            'description' => 'Couleur utilisée pour mettre en avant le plus gros score cumulé.',
            'position' => 302,
        ],
        [
            'key' => 'bilan_color_lowest_cumulative_score',
            'value' => '#F8D7DA',
            'label' => 'Bilan - plus petit score cumulé',
            'description' => 'Couleur utilisée pour mettre en avant le plus petit score cumulé.',
            'position' => 303,
        ],
        [
            'key' => 'bilan_color_rank_first',
            'value' => '#FFD966',
            'label' => 'Bilan - classement 1er',
            'description' => 'Couleur utilisée pour mettre en avant la première place dans le bilan.',
            'position' => 304,
        ],
        [
            'key' => 'bilan_color_rank_second',
            'value' => '#E7E7E7',
            'label' => 'Bilan - classement 2e',
            'description' => 'Couleur utilisée pour mettre en avant la deuxième place dans le bilan.',
            'position' => 305,
        ],
        [
            'key' => 'bilan_color_rank_third',
            'value' => '#FCE5CD',
            'label' => 'Bilan - classement 3e',
            'description' => 'Couleur utilisée pour mettre en avant la troisième place dans le bilan.',
            'position' => 306,
        ],
        [
            'key' => 'top14_standings_color_first',
            'value' => '#D1E7DD',
            'label' => 'Classement TOP 14 - 1er',
            'description' => 'Couleur utilisée pour mettre en avant le premier du classement TOP 14.',
            'position' => 350,
        ],
        [
            'key' => 'top14_standings_color_last',
            'value' => '#F8D7DA',
            'label' => 'Classement TOP 14 - dernier',
            'description' => 'Couleur utilisée pour mettre en avant le dernier du classement TOP 14.',
            'position' => 351,
        ],
    ];

    public function up(): void
    {
        foreach ($this->settings as $setting) {
            $exists = DB::table('app_settings')
                ->where('key', $setting['key'])
                ->exists();

            if ($exists) {
                DB::table('app_settings')
                    ->where('key', $setting['key'])
                    ->update([
                        'type' => 'color',
                        'label' => $setting['label'],
                        'description' => $setting['description'],
                        'position' => $setting['position'],
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('app_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'type' => 'color',
                'label' => $setting['label'],
                'description' => $setting['description'],
                'position' => $setting['position'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->whereIn('key', collect($this->settings)->pluck('key')->all())
            ->delete();
    }
};
