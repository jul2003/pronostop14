<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preseason_prediction_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('preseason_prediction_templates', 'correction_group')) {
                $table->string('correction_group')->nullable()->after('answer_type');
            }

            if (! Schema::hasColumn('preseason_prediction_templates', 'correction_mode')) {
                $table->string('correction_mode')->nullable()->after('correction_group');
            }
        });

        Schema::table('season_preseason_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('season_preseason_questions', 'correction_group')) {
                $table->string('correction_group')->nullable()->after('answer_type');
            }

            if (! Schema::hasColumn('season_preseason_questions', 'correction_mode')) {
                $table->string('correction_mode')->nullable()->after('correction_group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('preseason_prediction_templates', function (Blueprint $table) {
            if (Schema::hasColumn('preseason_prediction_templates', 'correction_mode')) {
                $table->dropColumn('correction_mode');
            }

            if (Schema::hasColumn('preseason_prediction_templates', 'correction_group')) {
                $table->dropColumn('correction_group');
            }
        });

        Schema::table('season_preseason_questions', function (Blueprint $table) {
            if (Schema::hasColumn('season_preseason_questions', 'correction_mode')) {
                $table->dropColumn('correction_mode');
            }

            if (Schema::hasColumn('season_preseason_questions', 'correction_group')) {
                $table->dropColumn('correction_group');
            }
        });
    }
};
