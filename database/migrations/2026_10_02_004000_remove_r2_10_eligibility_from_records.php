<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('records') && Schema::hasColumn('records', 'r2_10_eligibility')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->dropColumn('r2_10_eligibility');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('records') && ! Schema::hasColumn('records', 'r2_10_eligibility')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->string('r2_10_eligibility', 50)->nullable();
            });
        }
    }
};
