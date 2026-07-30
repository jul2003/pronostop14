<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'show_password_email_button'],
            [
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Afficher bouton envoi mot de passe',
                'description' => 'Affiche sur la fiche utilisateur un bouton permettant au super admin de générer et envoyer un nouveau mot de passe temporaire par email.',
                'position' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->where('key', 'show_password_email_button')
            ->delete();
    }
};
