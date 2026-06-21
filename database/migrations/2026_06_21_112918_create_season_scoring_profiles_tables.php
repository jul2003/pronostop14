<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_scoring_profiles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('source_profile_id')->nullable();

            $table->boolean('stop_on_wrong_result')->default(true);

            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('position')->default(0);

            $table->timestamps();

            $table->foreign('season_id', 'ssp_season_fk')
                ->references('id')
                ->on('seasons')
                ->cascadeOnDelete();

            $table->foreign('source_profile_id', 'ssp_source_profile_fk')
                ->references('id')
                ->on('scoring_profiles')
                ->nullOnDelete();

            $table->unique(['season_id', 'code'], 'ssp_season_code_unique');
        });

        Schema::table('season_scoring_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('season_scoring_rules', 'season_scoring_profile_id')) {
                $table->unsignedBigInteger('season_scoring_profile_id')
                    ->nullable()
                    ->after('season_id');

                $table->foreign('season_scoring_profile_id', 'ssr_profile_fk')
                    ->references('id')
                    ->on('season_scoring_profiles')
                    ->cascadeOnDelete();
            }
        });

        Schema::create('season_journee_type_scoring_profiles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');
            $table->string('journee_type');
            $table->unsignedBigInteger('season_scoring_profile_id');

            $table->timestamps();

            $table->foreign('season_id', 'sjtsp_season_fk')
                ->references('id')
                ->on('seasons')
                ->cascadeOnDelete();

            $table->foreign('season_scoring_profile_id', 'sjtsp_profile_fk')
                ->references('id')
                ->on('season_scoring_profiles')
                ->cascadeOnDelete();

            $table->unique(['season_id', 'journee_type'], 'sjtsp_season_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_journee_type_scoring_profiles');

        if (Schema::hasColumn('season_scoring_rules', 'season_scoring_profile_id')) {
            Schema::table('season_scoring_rules', function (Blueprint $table) {
                $table->dropForeign('ssr_profile_fk');
                $table->dropColumn('season_scoring_profile_id');
            });
        }

        Schema::dropIfExists('season_scoring_profiles');
    }
};
