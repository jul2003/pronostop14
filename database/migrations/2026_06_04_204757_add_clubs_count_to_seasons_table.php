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
        Schema::table('seasons', function (Blueprint $table) {
            $table->unsignedInteger('top14_clubs_count')->default(14)->after('is_active');
            $table->unsignedInteger('prod2_clubs_count')->default(16)->after('top14_clubs_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn([
                'top14_clubs_count',
                'prod2_clubs_count',
            ]);
        });
    }
};
