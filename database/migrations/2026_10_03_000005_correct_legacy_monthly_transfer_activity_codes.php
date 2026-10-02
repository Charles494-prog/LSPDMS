<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activities = DB::table('monthly_session_activities')
            ->where('action', 'transfer')
            ->orderBy('id')
            ->get(['id', 'direction', 'monthly_code']);

        foreach ($activities as $activity) {
            $status = mb_strtoupper(trim(explode(':', (string) $activity->monthly_code, 2)[0]));
            if (! in_array($status, ['MS', 'UWP'], true)) {
                continue;
            }

            $expectedCode = $status.': '.($activity->direction === 'out' ? 'C-' : 'C+');
            if ($activity->monthly_code !== $expectedCode) {
                DB::table('monthly_session_activities')
                    ->where('id', $activity->id)
                    ->update(['monthly_code' => $expectedCode]);
            }
        }
    }

    public function down(): void
    {
    }
};
