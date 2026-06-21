<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->string('journee_scoring_setup_hash')->nullable()->after('prod2_clubs_count');
            $table->string('preseason_setup_hash')->nullable()->after('journee_scoring_setup_hash');
        });
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn([
                'journee_scoring_setup_hash',
                'preseason_setup_hash',
            ]);
        });
    }
};
