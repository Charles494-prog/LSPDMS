<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_user', function (Blueprint $table) {
            if (! Schema::hasColumn('login_user', 'dashboard_allow')) {
                $table->boolean('dashboard_allow')->default(false);
            }
            if (! Schema::hasColumn('login_user', 'form_generator_allow')) {
                $table->boolean('form_generator_allow')->default(false);
            }
            if (! Schema::hasColumn('login_user', 'Delistment_allow')) {
                $table->boolean('Delistment_allow')->default(false);
            }
            if (! Schema::hasColumn('login_user', 'Inquire_Allow')) {
                $table->boolean('Inquire_Allow')->default(false);
            }
            if (! Schema::hasColumn('login_user', 'dark_mode')) {
                $table->boolean('dark_mode')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_user', function (Blueprint $table) {
            $table->dropColumn([
                'dashboard_allow',
                'form_generator_allow',
                'Delistment_allow',
                'Inquire_Allow',
            ]);
        });
    }
};