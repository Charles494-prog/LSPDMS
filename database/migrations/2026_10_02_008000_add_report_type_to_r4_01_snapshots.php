<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('r4_01_snapshots', 'report_type')) {
            Schema::table('r4_01_snapshots', function (Blueprint $table): void {
                $table->string('report_type', 20)->default('R4-01')->after('period_month');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('r4_01_snapshots', 'report_type')) {
            Schema::table('r4_01_snapshots', function (Blueprint $table): void {
                $table->dropColumn('report_type');
            });
        }
    }
};
