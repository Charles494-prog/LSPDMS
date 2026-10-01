<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('records', 'is_valid1')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->boolean('is_valid1')->default(false);
            });
        }

        if (! Schema::hasColumn('records', 'is_valid2')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->boolean('is_valid2')->default(false);
            });
        }

        if (! Schema::hasColumn('login_user', 'filter_bnh_helper_allow')) {
            Schema::table('login_user', function (Blueprint $table): void {
                $table->boolean('filter_bnh_helper_allow')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'filter_bnh_helper_allow' => 'login_user',
            'is_valid1' => 'records',
            'is_valid2' => 'records',
        ] as $column => $tableName) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
