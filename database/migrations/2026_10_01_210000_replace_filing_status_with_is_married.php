<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('records', 'is_married')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->boolean('is_married')->nullable();
            });
        }

        if (Schema::hasColumn('records', 'filing_status')) {
            DB::table('records')
                ->select(['id', 'filing_status'])
                ->orderBy('id')
                ->chunkById(500, function ($records): void {
                    foreach ($records as $record) {
                        $status = mb_strtolower(trim((string) $record->filing_status));
                        $isMarried = match ($status) {
                            'married', 'yes', '1', 'true' => true,
                            'not married', 'unmarried', 'no', '0', 'false' => false,
                            default => null,
                        };
                        DB::table('records')
                            ->where('id', $record->id)
                            ->update(['is_married' => $isMarried]);
                    }
                });

            Schema::table('records', function (Blueprint $table): void {
                $table->dropColumn('filing_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('records', 'filing_status')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->string('filing_status', 50)->nullable();
            });
        }

        if (Schema::hasColumn('records', 'is_married')) {
            DB::table('records')->update([
                'filing_status' => DB::raw("CASE WHEN is_married = 1 THEN 'Married' WHEN is_married = 0 THEN 'Not married' ELSE NULL END"),
            ]);
            Schema::table('records', function (Blueprint $table): void {
                $table->dropColumn('is_married');
            });
        }
    }
};
