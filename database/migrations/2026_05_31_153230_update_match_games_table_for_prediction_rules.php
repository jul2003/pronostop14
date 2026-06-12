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
        //
        Schema::table('match_games', function (Blueprint $table) {
            $table->dropColumn([
                'home_score',
                'away_score',
                'home_tries',
                'away_tries',
                'home_bonus',
                'away_bonus',
            ]);

            $table->string('actual_result', 1)->nullable(); // v, n, d
            $table->unsignedInteger('actual_tries')->nullable();
            $table->string('actual_home_bonus', 1)->nullable(); // o, -, d
            $table->string('actual_away_bonus', 1)->nullable(); // o, -, d
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('match_games', function (Blueprint $table) {
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->integer('home_tries')->nullable();
            $table->integer('away_tries')->nullable();
            $table->boolean('home_bonus')->default(false);
            $table->boolean('away_bonus')->default(false);

            $table->dropColumn([
                'actual_result',
                'actual_tries',
                'actual_home_bonus',
                'actual_away_bonus',
            ]);
        });
    }
};
