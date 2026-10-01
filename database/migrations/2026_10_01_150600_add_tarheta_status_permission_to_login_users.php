<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('login_user', 'tarheta_status_allow')) {
            Schema::table('login_user', function (Blueprint $table): void {
                $table->boolean('tarheta_status_allow')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('login_user', 'tarheta_status_allow')) {
            Schema::table('login_user', function (Blueprint $table): void {
                $table->dropColumn('tarheta_status_allow');
            });
        }
    }
};
