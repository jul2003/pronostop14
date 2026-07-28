<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('journees', 'predictions_enabled')) {
            Schema::table('journees', function (Blueprint $table) {
                $column = $table->boolean('predictions_enabled')
                    ->default(true);

                if (Schema::hasColumn('journees', 'first_match_at')) {
                    $column->after('first_match_at');
                } else {
                    $column->after('slug');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('journees', 'predictions_enabled')) {
            Schema::table('journees', function (Blueprint $table) {
                $table->dropColumn('predictions_enabled');
            });
        }
    }
};
