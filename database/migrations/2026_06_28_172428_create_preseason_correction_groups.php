<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preseason_correction_group_templates', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('code')->unique('pcgt_code_unique');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('preseason_correction_group_template_questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('preseason_correction_group_template_id');
            $table->unsignedBigInteger('preseason_prediction_template_id');

            $table->timestamps();

            $table->foreign(
                'preseason_correction_group_template_id',
                'pcgtq_group_fk'
            )
                ->references('id')
                ->on('preseason_correction_group_templates')
                ->cascadeOnDelete();

            $table->foreign(
                'preseason_prediction_template_id',
                'pcgtq_question_fk'
            )
                ->references('id')
                ->on('preseason_prediction_templates')
                ->cascadeOnDelete();

            $table->unique([
                'preseason_correction_group_template_id',
                'preseason_prediction_template_id',
            ], 'pcgtq_group_question_unique');
        });

        Schema::create('season_preseason_correction_groups', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('source_template_id')->nullable();

            $table->string('label');
            $table->string('code');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('season_id', 'spcg_season_fk')
                ->references('id')
                ->on('seasons')
                ->cascadeOnDelete();

            $table->foreign('source_template_id', 'spcg_source_template_fk')
                ->references('id')
                ->on('preseason_correction_group_templates')
                ->nullOnDelete();

            $table->unique([
                'season_id',
                'code',
            ], 'spcg_season_code_unique');
        });

        Schema::create('season_preseason_correction_group_questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_preseason_correction_group_id');
            $table->unsignedBigInteger('season_preseason_question_id');

            $table->timestamps();

            $table->foreign(
                'season_preseason_correction_group_id',
                'spcgq_group_fk'
            )
                ->references('id')
                ->on('season_preseason_correction_groups')
                ->cascadeOnDelete();

            $table->foreign(
                'season_preseason_question_id',
                'spcgq_question_fk'
            )
                ->references('id')
                ->on('season_preseason_questions')
                ->cascadeOnDelete();

            $table->unique([
                'season_preseason_correction_group_id',
                'season_preseason_question_id',
            ], 'spcgq_group_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_preseason_correction_group_questions');
        Schema::dropIfExists('season_preseason_correction_groups');
        Schema::dropIfExists('preseason_correction_group_template_questions');
        Schema::dropIfExists('preseason_correction_group_templates');
    }
};
