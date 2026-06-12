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
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn([
                'home_score',
                'away_score',
                'home_tries',
                'away_tries',
                'home_bonus',
                'away_bonus',
            ]);

            $table->string('predicted_result', 1)->nullable(); // v, n, d
            $table->unsignedInteger('predicted_tries')->nullable();
            $table->string('predicted_home_bonus', 1)->nullable(); // o, -, d
            $table->string('predicted_away_bonus', 1)->nullable(); // o, -, d
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('predictions', function (Blueprint $table) {
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->integer('home_tries')->nullable();
            $table->integer('away_tries')->nullable();
            $table->boolean('home_bonus')->default(false);
            $table->boolean('away_bonus')->default(false);

            $table->dropColumn([
                'predicted_result',
                'predicted_tries',
                'predicted_home_bonus',
                'predicted_away_bonus',
            ]);
        });
    }
};
