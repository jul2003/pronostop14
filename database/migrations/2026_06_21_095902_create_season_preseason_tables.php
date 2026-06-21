<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_preseason_questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('source_template_id')->nullable();
            $table->unsignedBigInteger('scoring_profile_id')->nullable();

            $table->string('label');
            $table->string('answer_type');
            $table->integer('points')->default(0);
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('correct_club_id')->nullable();
            $table->string('correct_text_answer')->nullable();
            $table->timestamp('corrected_at')->nullable();

            $table->timestamps();

            $table->foreign('season_id', 'spq_season_fk')
                ->references('id')
                ->on('seasons')
                ->cascadeOnDelete();

            $table->foreign('source_template_id', 'spq_template_fk')
                ->references('id')
                ->on('preseason_prediction_templates')
                ->nullOnDelete();

            $table->foreign('scoring_profile_id', 'spq_profile_fk')
                ->references('id')
                ->on('scoring_profiles')
                ->nullOnDelete();

            $table->foreign('correct_club_id', 'spq_correct_club_fk')
                ->references('id')
                ->on('clubs')
                ->nullOnDelete();
        });

        Schema::create('season_preseason_bonus_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('source_template_id')->nullable();

            $table->string('label');
            $table->integer('points')->default(0);
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('stop_after_match')->default(false);

            $table->timestamps();

            $table->foreign('season_id', 'spbr_season_fk')
                ->references('id')
                ->on('seasons')
                ->cascadeOnDelete();

            $table->foreign('source_template_id', 'spbr_template_fk')
                ->references('id')
                ->on('preseason_bonus_rule_templates')
                ->nullOnDelete();
        });

        Schema::create('season_preseason_bonus_rule_questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bonus_rule_id');
            $table->unsignedBigInteger('question_id');

            $table->timestamps();

            $table->foreign('bonus_rule_id', 'spbrq_bonus_fk')
                ->references('id')
                ->on('season_preseason_bonus_rules')
                ->cascadeOnDelete();

            $table->foreign('question_id', 'spbrq_question_fk')
                ->references('id')
                ->on('season_preseason_questions')
                ->cascadeOnDelete();

            $table->unique([
                'bonus_rule_id',
                'question_id',
            ], 'spbrq_bonus_question_unique');
        });

        Schema::create('season_preseason_predictions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('question_id');

            $table->string('answer_type');
            $table->unsignedBigInteger('club_id')->nullable();
            $table->string('text_answer')->nullable();

            $table->boolean('is_correct')->nullable();
            $table->integer('points')->default(0);
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->foreign('season_id', 'spp_season_fk')
                ->references('id')
                ->on('seasons')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'spp_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('question_id', 'spp_question_fk')
                ->references('id')
                ->on('season_preseason_questions')
                ->cascadeOnDelete();

            $table->foreign('club_id', 'spp_club_fk')
                ->references('id')
                ->on('clubs')
                ->nullOnDelete();

            $table->unique([
                'user_id',
                'question_id',
            ], 'spp_user_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_preseason_predictions');
        Schema::dropIfExists('season_preseason_bonus_rule_questions');
        Schema::dropIfExists('season_preseason_bonus_rules');
        Schema::dropIfExists('season_preseason_questions');
    }
};
