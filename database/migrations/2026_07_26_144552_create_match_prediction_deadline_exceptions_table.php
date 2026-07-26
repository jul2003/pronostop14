<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_prediction_deadline_exceptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_game_id')
                ->unique()
                ->constrained('match_games')
                ->cascadeOnDelete();

            $table->dateTime('prediction_deadline');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_prediction_deadline_exceptions');
    }
};
