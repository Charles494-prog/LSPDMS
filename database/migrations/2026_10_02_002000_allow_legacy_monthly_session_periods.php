<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('monthly_movement_sessions'));
        if ($indexes->contains(fn (array $index): bool => $index['name'] === 'monthly_movement_sessions_period_month_unique')) {
            Schema::table('monthly_movement_sessions', function (Blueprint $table): void {
                $table->dropUnique('monthly_movement_sessions_period_month_unique');
            });
        }

        $indexes = collect(Schema::getIndexes('monthly_movement_sessions'));
        if (! $indexes->contains(fn (array $index): bool => $index['name'] === 'monthly_movement_sessions_period_month_index')) {
            Schema::table('monthly_movement_sessions', function (Blueprint $table): void {
                $table->index('period_month');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('monthly_movement_sessions'));
        if ($indexes->contains(fn (array $index): bool => $index['name'] === 'monthly_movement_sessions_period_month_index')) {
            Schema::table('monthly_movement_sessions', function (Blueprint $table): void {
                $table->dropIndex('monthly_movement_sessions_period_month_index');
            });
        }
    }
};
