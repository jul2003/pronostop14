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
        Schema::create('season_scoring_rules', function (Blueprint $table) {
                $table->id();

                $table->foreignId('season_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('code');
                $table->string('label');

                $table->integer('points');

                $table->unsignedInteger('position')
                    ->default(0);

                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('season_scoring_rules');
    }
};
