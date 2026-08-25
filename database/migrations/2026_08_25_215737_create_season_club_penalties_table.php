<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_club_penalties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('season_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('club_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Nombre de points retirés.
             *
             * On enregistre 4 pour une pénalité de -4 points.
             * Cela évite de mélanger valeurs négatives et positives.
             */
            $table->unsignedSmallInteger('points_deduction')
                ->default(0);

            /*
             * Journée à partir de laquelle la pénalité doit
             * être prise en compte dans le classement.
             */
            $table->unsignedTinyInteger('effective_from_journee')
                ->default(1);

            $table->string('reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'season_id',
                'club_id',
            ]);

            $table->index([
                'season_id',
                'effective_from_journee',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'season_club_penalties'
        );
    }
};
