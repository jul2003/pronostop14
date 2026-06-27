<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('scoring_profiles', 'stop_on_wrong_result')) {
                $table->boolean('stop_on_wrong_result')
                    ->default(true)
                    ->after('category');
            }
        });

        Schema::table('season_scoring_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('season_scoring_profiles', 'stop_on_wrong_result')) {
                $table->boolean('stop_on_wrong_result')
                    ->default(true)
                    ->after('source_profile_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('season_scoring_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('season_scoring_profiles', 'stop_on_wrong_result')) {
                $table->dropColumn('stop_on_wrong_result');
            }
        });

        Schema::table('scoring_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('scoring_profiles', 'stop_on_wrong_result')) {
                $table->dropColumn('stop_on_wrong_result');
            }
        });
    }
};
