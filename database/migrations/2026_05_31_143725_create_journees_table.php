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
        Schema::create('journees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('season_id')->constrained()->cascadeOnDelete();

            $table->string('type')->default('regular');
            $table->unsignedInteger('number')->nullable();

            $table->string('name');
            $table->string('slug');

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('prediction_deadline')->nullable();

            $table->timestamps();

            $table->unique(['season_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journees');
    }
};
