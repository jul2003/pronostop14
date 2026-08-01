<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('season_preseason_questions')
            && ! Schema::hasColumn('season_preseason_questions', 'auto_result_position')) {
            Schema::table('season_preseason_questions', function (Blueprint $table) {
                $table->unsignedTinyInteger('auto_result_position')
                    ->nullable()
                    ->after('auto_result_journee_number');
            });
        }

        if (Schema::hasTable('preseason_prediction_templates')
            && ! Schema::hasColumn('preseason_prediction_templates', 'auto_result_position')) {
            Schema::table('preseason_prediction_templates', function (Blueprint $table) {
                $table->unsignedTinyInteger('auto_result_position')
                    ->nullable()
                    ->after('auto_result_journee_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('season_preseason_questions')
            && Schema::hasColumn('season_preseason_questions', 'auto_result_position')) {
            Schema::table('season_preseason_questions', function (Blueprint $table) {
                $table->dropColumn('auto_result_position');
            });
        }

        if (Schema::hasTable('preseason_prediction_templates')
            && Schema::hasColumn('preseason_prediction_templates', 'auto_result_position')) {
            Schema::table('preseason_prediction_templates', function (Blueprint $table) {
                $table->dropColumn('auto_result_position');
            });
        }
    }
};
