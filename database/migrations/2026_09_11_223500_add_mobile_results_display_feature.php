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
                'key' => 'mobile_results_display',
            ],
            [
                'title' => 'Nouvel affichage mobile des résultats',
                'description' => 'La page Résultats a été entièrement repensée sur smartphone : affichage plus lisible, matchs présentés sous forme de cartes, journées repliables et accès direct à la journée en cours.',
                'icon' => null,
                'details' => null,
                'is_active' => true,
                'activation_setting_key' => null,
                'action_label' => 'Voir les résultats',
                'action_url' => '/resultats',
                'position' => 30,
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
            ->where('key', 'mobile_results_display')
            ->delete();
    }
};
