<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('season_user', function (Blueprint $table) {
            if (! Schema::hasColumn('season_user', 'preseason_prediction_deadline')) {
                $table->dateTime('preseason_prediction_deadline')
                    ->nullable()
                    ->after('display_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('season_user', function (Blueprint $table) {
            if (Schema::hasColumn('season_user', 'preseason_prediction_deadline')) {
                $table->dropColumn('preseason_prediction_deadline');
            }
        });
    }
};
