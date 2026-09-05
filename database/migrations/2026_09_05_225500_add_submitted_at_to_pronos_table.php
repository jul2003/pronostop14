<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pronos', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable();
        });

        /*
         * Pour les anciens pronostics, updated_at n'est plus fiable :
         * il a pu être modifié lors du calcul des résultats.
         *
         * On initialise donc submitted_at avec created_at.
         * À partir de maintenant, submitted_at sera mis à jour
         * uniquement lors d'un véritable enregistrement du joueur.
         */
        DB::table('pronos')
            ->whereNull('submitted_at')
            ->update([
                'submitted_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('pronos', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
