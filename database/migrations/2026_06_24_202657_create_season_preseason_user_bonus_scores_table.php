<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_preseason_user_bonus_scores', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('season_preseason_bonus_rule_id');

            $table->boolean('is_awarded')->default(false);
            $table->integer('points')->default(0);

            $table->timestamps();

            $table->unique([
                'season_id',
                'user_id',
                'season_preseason_bonus_rule_id',
            ], 'spubs_unique');
        });

        Schema::table('season_preseason_user_bonus_scores', function (Blueprint $table) {
            $table->foreign('season_id', 'spubs_season_fk')
                ->references('id')
                ->on('seasons')
                ->onDelete('cascade');

            $table->foreign('user_id', 'spubs_user_fk')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('season_preseason_bonus_rule_id', 'spubs_bonus_fk')
                ->references('id')
                ->on('season_preseason_bonus_rules')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_preseason_user_bonus_scores');
    }
};
