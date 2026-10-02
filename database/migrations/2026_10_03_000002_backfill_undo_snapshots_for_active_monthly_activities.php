<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const STATE_FIELDS = [
        'status',
        'purok',
        'grupo',
        'monthly_code',
        'date_of_entry',
        'reason_of_r2_10_endorsement',
        'log_section',
    ];

    public function up(): void
    {
        $activities = DB::table('monthly_session_activities')
            ->whereNull('session_movement_id')
            ->whereIn('action', ['add', 'reactivate'])
            ->whereNull('previous_values')
            ->whereNull('next_values')
            ->orderBy('id')
            ->get();

        foreach ($activities as $activity) {
            $session = DB::table('monthly_movement_sessions')
                ->where('id', $activity->session_id)
                ->where('active_key', 'active')
                ->first();
            if (! $session || $activity->record_id === null) {
                continue;
            }

            $record = DB::table('records')->where('id', $activity->record_id)->first();
            if (! $record
                || $record->monthly_code !== $activity->monthly_code
                || (string) $record->purok !== (string) $activity->purok
                || (string) $record->grupo !== (string) $activity->grupo) {
                continue;
            }

            $laterActivityExists = DB::table('monthly_session_activities')
                ->where('session_id', $activity->session_id)
                ->where('record_id', $activity->record_id)
                ->where('id', '>', $activity->id)
                ->exists();
            $laterMovementExists = DB::table('monthly_session_movements')
                ->where('session_id', $activity->session_id)
                ->where('record_id', $activity->record_id)
                ->where('changed_at', '>', $activity->changed_at)
                ->exists();
            if ($laterActivityExists || $laterMovementExists) {
                continue;
            }

            $nextValues = $this->state($record);
            if ($record->status !== $activity->status
                || $activity->monthly_code !== $activity->status.': B') {
                continue;
            }
            $previousValues = $nextValues;
            $previousValues['status'] = 'INACTIVE';
            $previousValues['monthly_code'] = null;
            if ($activity->action === 'add') {
                if (trim((string) $record->reason_of_r2_10_endorsement) !== '') {
                    continue;
                }
                $previousValues['date_of_entry'] = null;
                $previousValues['reason_of_r2_10_endorsement'] = null;
            }
            if ($record->log_section !== $this->expectedActivityLog($activity)) {
                continue;
            }
            $previousValues['log_section'] = null;

            DB::table('monthly_session_activities')
                ->where('id', $activity->id)
                ->update([
                    'previous_values' => json_encode($previousValues, JSON_THROW_ON_ERROR),
                    'next_values' => json_encode($nextValues, JSON_THROW_ON_ERROR),
                ]);
        }
    }

    public function down(): void
    {
    }

    private function state(object $record): array
    {
        return collect(self::STATE_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $record->{$field}])
            ->all();
    }

    private function expectedActivityLog(object $activity): string
    {
        $date = \Illuminate\Support\Carbon::parse($activity->changed_at, 'UTC')
            ->timezone('Asia/Manila')
            ->format('d-m-Y');
        $log = $date.': '.$activity->monthly_code.',';
        if (trim((string) $activity->description) !== '') {
            $log .= ' '.$date.': '.trim((string) $activity->description).',';
        }

        return $log;
    }
};
