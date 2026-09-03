<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')->updateOrInsert(
            [
                'key' => 'prediction_recap_early_visibility_enabled',
            ],
            [
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Affichage anticipé des pronostics',
                'description' => 'Permet de définir, pour chaque journée, une date à partir de laquelle les pronostics saisis deviennent visibles sur la page Résultats avant la clôture de la saisie.',
                'position' => 95,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->where(
                'key',
                'prediction_recap_early_visibility_enabled'
            )
            ->delete();
    }
};
