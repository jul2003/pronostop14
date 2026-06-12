<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('match_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journee_id')->constrained('journees')->cascadeOnDelete();

            $table->foreignId('home_club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('away_club_id')->constrained('clubs')->cascadeOnDelete();

            $table->dateTime('starts_at')->nullable();

            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();

            $table->integer('home_tries')->nullable();
            $table->integer('away_tries')->nullable();

            $table->boolean('home_bonus')->default(false);
            $table->boolean('away_bonus')->default(false);

            $table->boolean('is_finished')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_games');
    }
};
