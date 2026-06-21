<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preseason_bonus_rule_templates', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->integer('points')->default(0);
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('stop_after_match')->default(false);
            $table->timestamps();
        });

        Schema::create('preseason_bonus_rule_template_questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bonus_rule_template_id');
            $table->unsignedBigInteger('preseason_prediction_template_id');

            $table->timestamps();

            $table->foreign('bonus_rule_template_id', 'ps_bonus_tpl_bonus_fk')
                ->references('id')
                ->on('preseason_bonus_rule_templates')
                ->cascadeOnDelete();

            $table->foreign('preseason_prediction_template_id', 'ps_bonus_tpl_question_fk')
                ->references('id')
                ->on('preseason_prediction_templates')
                ->cascadeOnDelete();

            $table->unique([
                'bonus_rule_template_id',
                'preseason_prediction_template_id',
            ], 'ps_bonus_tpl_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preseason_bonus_rule_template_questions');
        Schema::dropIfExists('preseason_bonus_rule_templates');
    }
};
