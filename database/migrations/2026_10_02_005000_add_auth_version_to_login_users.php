<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('login_user', 'auth_version')) {
            Schema::table('login_user', function (Blueprint $table): void {
                $table->unsignedInteger('auth_version')->default(1);
                $table->string('auth_notice', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('login_user', 'auth_version')) {
            Schema::table('login_user', function (Blueprint $table): void {
                $table->dropColumn(['auth_version', 'auth_notice']);
            });
        }
    }
};
