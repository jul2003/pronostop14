<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::updateOrCreate(
            [
                'key' => 'upcoming_journees_to_prepare_count',
            ],
            [
                'value' => '3',
                'type' => 'integer',
                'label' => 'Nombre de journées à préparer',
                'description' => 'Nombre de prochaines journées proposées dans la page admin “matchs à saisir”.',
                'position' => 10,
            ]
        );
    }
}
