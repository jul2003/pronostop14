<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('features')->updateOrInsert(
            [
                'key' => 'mobile_journee_ranking',
            ],
            [
                'title' => 'Classement des journées sur mobile',
                'description' => 'Sur mobile, le résultat de chaque journée est désormais affiché en premier, avant le détail des matchs, avec les joueurs triés par nombre de points.',
                'icon' => '🏆',
                'details' => null,
                'is_active' => true,
                'activation_setting_key' => null,
                'action_label' => 'Voir les résultats',
                'action_url' => '/resultats',
                'position' => 40,
                'starts_at' => null,
                'ends_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('features')
            ->where('key', 'mobile_journee_ranking')
            ->delete();
    }
};
