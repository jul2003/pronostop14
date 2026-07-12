<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSetting([
            'key' => 'upcoming_journees_to_prepare_count',
            'value' => '3',
            'type' => 'integer',
            'label' => 'Nombre de journées à préparer',
            'description' => 'Nombre de prochaines journées proposées dans la page admin “matchs à saisir”.',
            'position' => 10,
        ]);

        $this->seedSetting([
            'key' => 'results_color_correct',
            'value' => '#D1E7DD',
            'type' => 'color',
            'label' => 'Résultats - prono juste',
            'description' => 'Couleur utilisée dans la page résultats quand une partie du prono est juste.',
            'position' => 100,
        ]);

        $this->seedSetting([
            'key' => 'results_color_wrong',
            'value' => '#F8D7DA',
            'type' => 'color',
            'label' => 'Résultats - prono faux',
            'description' => 'Couleur utilisée dans la page résultats quand une partie du prono est fausse.',
            'position' => 110,
        ]);

        $this->seedSetting([
            'key' => 'results_color_bonus_offset',
            'value' => '#FFF3CD',
            'type' => 'color',
            'label' => 'Résultats - bonus + / - 1',
            'description' => 'Couleur utilisée dans la page résultats pour les bonus avec logique +1 ou -1.',
            'position' => 120,
        ]);

        $this->seedSetting([
            'key' => 'results_color_preseason_bonus',
            'value' => '#FFD966',
            'type' => 'color',
            'label' => 'Résultats - bonus avant-saison',
            'description' => 'Couleur dorée utilisée pour mettre en valeur les pronos avant-saison qui déclenchent un bonus.',
            'position' => 130,
        ]);
    }

    private function seedSetting(array $attributes): void
    {
        $key = $attributes['key'];
        $defaultValue = $attributes['value'];

        unset($attributes['key'], $attributes['value']);

        $setting = AppSetting::firstOrNew([
            'key' => $key,
        ]);

        if (! $setting->exists || $setting->value === null || $setting->value === '') {
            $setting->value = $defaultValue;
        }

        $setting->fill($attributes);
        $setting->save();
    }
}
