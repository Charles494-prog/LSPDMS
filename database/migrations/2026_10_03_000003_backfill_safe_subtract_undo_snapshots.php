<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
            ->where('action', 'subtract')
            ->whereNull('previous_values')
            ->whereNull('next_values')
            ->orderBy('id')
            ->get();

        foreach ($activities as $activity) {
            $session = DB::table('monthly_movement_sessions')
                ->where('id', $activity->session_id)
                ->where('active_key', 'active')
                ->first();
            $record = $activity->record_id === null
                ? null
                : DB::table('records')->where('id', $activity->record_id)->first();
            if (! $session || ! $record
                || $record->status !== 'INACTIVE'
                || $record->monthly_code !== $activity->monthly_code
                || (string) $record->purok !== (string) $activity->purok
                || (string) $record->grupo !== (string) $activity->grupo
                || DB::table('monthly_session_activities')
                    ->where('session_id', $activity->session_id)
                    ->where('record_id', $activity->record_id)
                    ->where('id', '>', $activity->id)
                    ->exists()
                || DB::table('monthly_session_movements')
                    ->where('session_id', $activity->session_id)
                    ->where('record_id', $activity->record_id)
                    ->where('changed_at', '>', $activity->changed_at)
                    ->exists()) {
                continue;
            }

            [$status, $code] = array_pad(explode(':', (string) $activity->monthly_code, 2), 2, '');
            $status = strtoupper(trim($status));
            $code = strtoupper(trim($code));
            if (! in_array($status, ['MS', 'UWP'], true)
                || $status !== $activity->status
                || ! in_array($code, ['D', 'DD', 'G', 'H'], true)
                || $activity->monthly_code !== $status.': '.$code
                || $record->log_section !== $this->expectedLog($activity)) {
                continue;
            }

            $previousReason = $this->removeSubtractReason(
                $record->reason_of_r2_10_endorsement,
                $code,
                $activity->description
            );
            if ($previousReason === null) {
                continue;
            }

            $nextValues = collect(self::STATE_FIELDS)
                ->mapWithKeys(fn (string $field): array => [$field => $record->{$field}])
                ->all();
            $previousValues = $nextValues;
            $previousValues['status'] = $status;
            $previousValues['monthly_code'] = null;
            $previousValues['reason_of_r2_10_endorsement'] = $previousReason;
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

    private function expectedLog(object $activity): string
    {
        $date = Carbon::parse($activity->changed_at, 'UTC')
            ->timezone('Asia/Manila')
            ->format('d-m-Y');
        $log = $date.': '.$activity->monthly_code.',';
        if (trim((string) $activity->description) !== '') {
            $log .= ' '.$date.': '.trim((string) $activity->description).',';
        }

        return $log;
    }

    private function removeSubtractReason(?string $reason, string $code, ?string $description): ?string
    {
        $entry = 'Deliberation subtract '.$code;
        if (trim((string) $description) !== '') {
            $entry .= ': '.trim((string) $description);
        }
        $reason = (string) $reason;
        if ($reason === $entry) {
            return null;
        }
        $suffix = PHP_EOL.$entry;

        return str_ends_with($reason, $suffix)
            ? substr($reason, 0, -strlen($suffix))
            : null;
    }
};
