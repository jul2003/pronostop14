<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('season_preseason_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('season_preseason_questions', 'result_club_id')) {
                $table->foreignId('result_club_id')
                    ->nullable()
                    ->after('points')
                    ->constrained('clubs')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('season_preseason_questions', 'result_text_answer')) {
                $table->string('result_text_answer')
                    ->nullable()
                    ->after('result_club_id');
            }

            if (! Schema::hasColumn('season_preseason_questions', 'result_recorded_at')) {
                $table->dateTime('result_recorded_at')
                    ->nullable()
                    ->after('result_text_answer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('season_preseason_questions', function (Blueprint $table) {
            if (Schema::hasColumn('season_preseason_questions', 'result_club_id')) {
                $table->dropConstrainedForeignId('result_club_id');
            }

            if (Schema::hasColumn('season_preseason_questions', 'result_text_answer')) {
                $table->dropColumn('result_text_answer');
            }

            if (Schema::hasColumn('season_preseason_questions', 'result_recorded_at')) {
                $table->dropColumn('result_recorded_at');
            }
        });
    }
};
