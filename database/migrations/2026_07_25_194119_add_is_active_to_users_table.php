<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            $afterColumn = Schema::hasColumn('users', 'must_change_password')
                ? 'must_change_password'
                : null;

            Schema::table('users', function (Blueprint $table) use ($afterColumn) {
                $column = $table->boolean('is_active')->default(true);

                if ($afterColumn) {
                    $column->after($afterColumn);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
