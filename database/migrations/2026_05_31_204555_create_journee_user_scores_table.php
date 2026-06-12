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
        Schema::create('journee_user_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journee_id')->constrained('journees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->integer('match_points')->default(0);
            $table->integer('perfect_journee_bonus')->default(0);
            $table->integer('total_points')->default(0);
            $table->integer('rank')->nullable();

            $table->timestamps();

            $table->unique(['journee_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journee_user_scores');
    }
};
