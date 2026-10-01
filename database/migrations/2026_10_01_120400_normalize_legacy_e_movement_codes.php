<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('monthly_record_snapshots')
            ->select(['id', 'movement_counts'])
            ->orderBy('id')
            ->get()
            ->each(function (object $snapshot): void {
                $movementCounts = json_decode($snapshot->movement_counts, true, 512, JSON_THROW_ON_ERROR);
                $normalizedCounts = [];

                foreach ($movementCounts as $status => $codes) {
                    foreach ($codes as $code => $count) {
                        $normalizedCode = in_array($code, ['E-1', 'E-2', 'E-3'], true) ? 'E' : $code;
                        $normalizedCounts[$status][$normalizedCode] =
                            ($normalizedCounts[$status][$normalizedCode] ?? 0) + (int) $count;
                    }
                }

                if ($normalizedCounts !== $movementCounts) {
                    DB::table('monthly_record_snapshots')
                        ->where('id', $snapshot->id)
                        ->update(['movement_counts' => json_encode($normalizedCounts, JSON_THROW_ON_ERROR)]);
                }
            });
    }

    public function down(): void
    {
        // Consolidated movement codes cannot be mapped back to their former subdivisions.
    }
};
