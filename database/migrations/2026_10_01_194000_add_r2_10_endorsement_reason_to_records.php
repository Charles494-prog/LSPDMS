<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('records', 'r2_10_endorsement_reason')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->longText('r2_10_endorsement_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('records', 'r2_10_endorsement_reason')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->dropColumn('r2_10_endorsement_reason');
            });
        }
    }
};
