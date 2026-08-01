<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('season_preseason_questions')) {
            Schema::table('season_preseason_questions', function (Blueprint $table) {
                if (! Schema::hasColumn('season_preseason_questions', 'auto_result_rule')) {
                    $table->string('auto_result_rule', 50)->nullable()->after('answer_type');
                }

                if (! Schema::hasColumn('season_preseason_questions', 'auto_result_journee_number')) {
                    $table->unsignedTinyInteger('auto_result_journee_number')->nullable()->after('auto_result_rule');
                }
            });
        }

        if (Schema::hasTable('preseason_prediction_templates')) {
            Schema::table('preseason_prediction_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('preseason_prediction_templates', 'auto_result_rule')) {
                    $table->string('auto_result_rule', 50)->nullable()->after('answer_type');
                }

                if (! Schema::hasColumn('preseason_prediction_templates', 'auto_result_journee_number')) {
                    $table->unsignedTinyInteger('auto_result_journee_number')->nullable()->after('auto_result_rule');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('season_preseason_questions')) {
            Schema::table('season_preseason_questions', function (Blueprint $table) {
                if (Schema::hasColumn('season_preseason_questions', 'auto_result_journee_number')) {
                    $table->dropColumn('auto_result_journee_number');
                }

                if (Schema::hasColumn('season_preseason_questions', 'auto_result_rule')) {
                    $table->dropColumn('auto_result_rule');
                }
            });
        }

        if (Schema::hasTable('preseason_prediction_templates')) {
            Schema::table('preseason_prediction_templates', function (Blueprint $table) {
                if (Schema::hasColumn('preseason_prediction_templates', 'auto_result_journee_number')) {
                    $table->dropColumn('auto_result_journee_number');
                }

                if (Schema::hasColumn('preseason_prediction_templates', 'auto_result_rule')) {
                    $table->dropColumn('auto_result_rule');
                }
            });
        }
    }
};
