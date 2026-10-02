<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_movement_sessions', function (Blueprint $table): void {
            $table->date('period_month')->nullable()->after('status');
        });
        Schema::table('permanent_history', function (Blueprint $table): void {
            $table->date('period_month')->nullable()->after('session_id');
        });

        DB::table('monthly_movement_sessions')
            ->orderBy('id')
            ->get(['id', 'started_at'])
            ->each(function (object $session): void {
                $periodMonth = Carbon::parse($session->started_at)
                    ->timezone('Asia/Manila')
                    ->startOfMonth()
                    ->addMonthNoOverflow()
                    ->toDateString();
                DB::table('monthly_movement_sessions')
                    ->where('id', $session->id)
                    ->update(['period_month' => $periodMonth]);
            });

        DB::table('monthly_movement_sessions')
            ->orderBy('id')
            ->get(['id', 'period_month'])
            ->each(function (object $session): void {
                DB::table('permanent_history')
                    ->where('session_id', $session->id)
                    ->update(['period_month' => $session->period_month]);
            });

        Schema::table('monthly_movement_sessions', function (Blueprint $table): void {
            $table->index('period_month');
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('monthly_movement_sessions'));
        if ($indexes->contains(fn (array $index): bool => $index['name'] === 'monthly_movement_sessions_period_month_index')) {
            Schema::table('monthly_movement_sessions', function (Blueprint $table): void {
                $table->dropIndex('monthly_movement_sessions_period_month_index');
            });
        }
        Schema::table('monthly_movement_sessions', function (Blueprint $table): void {
            $table->dropColumn('period_month');
        });
        Schema::table('permanent_history', function (Blueprint $table): void {
            $table->dropColumn('period_month');
        });
    }
};
