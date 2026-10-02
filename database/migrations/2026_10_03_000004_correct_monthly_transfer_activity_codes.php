<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activities = DB::table('monthly_session_activities')
            ->where('action', 'transfer')
            ->whereNotNull('session_movement_id')
            ->orderBy('id')
            ->get(['id', 'session_movement_id', 'direction', 'monthly_code']);

        foreach ($activities as $activity) {
            $movement = $activity->session_movement_id
                ? DB::table('monthly_session_movements')
                    ->where('id', $activity->session_movement_id)
                    ->first(['previous_values'])
                : null;
            $previousValues = $movement
                ? json_decode((string) $movement->previous_values, true)
                : [];
            $statusPrefix = explode(':', (string) $activity->monthly_code, 2)[0];
            $status = mb_strtoupper(trim((string) ($previousValues['status'] ?? $statusPrefix)));
            if (! in_array($status, ['MS', 'UWP'], true)) {
                continue;
            }

            $code = $activity->direction === 'out' ? 'C-' : 'C+';
            DB::table('monthly_session_activities')
                ->where('id', $activity->id)
                ->update(['monthly_code' => $status.': '.$code]);
        }
    }

    public function down(): void
    {
    }
};
