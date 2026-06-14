<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scoring_rule_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scoring_profile_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->integer('points')->default(0);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['scoring_profile_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scoring_rule_templates');
    }
};
