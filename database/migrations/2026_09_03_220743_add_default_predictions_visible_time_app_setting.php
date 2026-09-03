<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')->updateOrInsert(
            [
                'key' => 'default_predictions_visible_time',
            ],
            [
                'value' => '17:00',
                'type' => 'time',
                'label' => 'Heure d’affichage par défaut des pronostics',
                'description' => 'Heure proposée automatiquement lorsqu’une date d’affichage anticipé des pronostics est renseignée sur une journée.',
                'position' => 96,
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
                'default_predictions_visible_time'
            )
            ->delete();
    }
};
