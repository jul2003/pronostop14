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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_game_id')->constrained()->cascadeOnDelete();

            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();

            $table->integer('home_tries')->nullable();
            $table->integer('away_tries')->nullable();

            $table->boolean('home_bonus')->default(false);
            $table->boolean('away_bonus')->default(false);

            $table->integer('points')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'match_game_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
