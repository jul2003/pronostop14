<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            if (! Schema::hasColumn('seasons', 'is_locked')) {
                $table->boolean('is_locked')
                    ->default(false)
                    ->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            if (Schema::hasColumn('seasons', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
