<?php

namespace App\Observers;

use App\Models\Record;
use App\Models\RecordAuditLog;
use DateTimeInterface;
use Illuminate\Http\Request;

class RecordObserver
{
    private const FIELD_LABELS = [
        'full_name' => 'NAME',
        'registry' => 'REGISTRY',
        'gender' => 'GENDER',
        'purok' => 'PUROK',
        'grupo' => 'GRUPO',
        'kapisanan' => 'KAPISANAN',
        'date_of_baptism' => 'DATE OF BAPTISM',
        'evangelist' => 'EVANGELIST',
        'date_of_entry' => 'DATE OF ENTRY',
        'status' => 'KALAGAYAN',
        'tarheta_status' => 'TARHETA STATUS',
        'monthly_code' => 'MONTHLY CODE',
        'date_of_birth' => 'DATE OF BIRTH',
        'is_married' => 'MARRIED',
        'r2_10_endorsement' => 'R2-10 ENDORSEMENT',
        'reason_of_r2_10_endorsement' => 'R2-10 REASON',
        'log_section' => 'RECORD LOG',
    ];
    private const EXCLUDED_FIELDS = ['is_valid1', 'is_valid2'];

    public function created(Record $record): void
    {
        $changes = [];
        foreach ($record->getAttributes() as $field => $value) {
            if ($field !== $record->getKeyName() && ! in_array($field, self::EXCLUDED_FIELDS, true)) {
                $changes[$field] = ['from' => null, 'to' => $this->normalizeValue($value)];
            }
        }

        $this->write($record, 'created', $changes);
    }

    public function updated(Record $record): void
    {
        $changes = [];
        foreach ($record->getChanges() as $field => $value) {
            if ($field !== $record->getKeyName() && ! in_array($field, self::EXCLUDED_FIELDS, true)) {
                $changes[$field] = [
                    'from' => $this->normalizeValue($record->getRawOriginal($field)),
                    'to' => $this->normalizeValue($value),
                ];
            }
        }

        $changes = array_diff_key($changes, array_flip(self::EXCLUDED_FIELDS));
        if ($changes !== []) {
            $this->write($record, 'updated', $changes);
        }
    }

    public function deleted(Record $record): void
    {
        $changes = [];
        foreach ($record->getRawOriginal() as $field => $value) {
            if ($field !== $record->getKeyName() && ! in_array($field, self::EXCLUDED_FIELDS, true)) {
                $changes[$field] = ['from' => $this->normalizeValue($value), 'to' => null];
            }
        }

        $this->write($record, 'deleted', $changes);
    }

    private function write(Record $record, string $action, array $changes): void
    {
        $name = $this->value($changes, 'full_name', 'to')
            ?? $this->value($changes, 'full_name', 'from')
            ?? trim((string) $record->getAttribute('full_name'));
        $name = $name !== '' ? $name : 'Unnamed record';

        RecordAuditLog::query()->create([
            'record_id' => $record->getKey(),
            'record_name' => $name,
            'actor_email' => $this->actorEmail(),
            'action' => $action,
            'summary' => $this->summary($action, $name, $changes),
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    private function summary(string $action, string $name, array $changes): string
    {
        if ($action === 'created') {
            $status = $this->value($changes, 'status', 'to') ?? 'unspecified status';

            return "Added {$name} as {$status}.";
        }

        if ($action === 'deleted') {
            return "Deleted record {$name}.";
        }

        $details = [];
        if (isset($changes['status'])) {
            $from = $this->formatValue($changes['status']['from'], 'status');
            $to = $this->formatValue($changes['status']['to'], 'status');
            $isStatusTransfer = in_array($changes['status']['from'], ['MS', 'UWP', 'MK'], true)
                && in_array($changes['status']['to'], ['MS', 'UWP', 'MK'], true);
            $details[] = $isStatusTransfer
                ? "Transferred {$name} from {$from} to {$to}."
                : "Changed the status of {$name} from {$from} to {$to}.";
        }

        foreach ($changes as $field => $change) {
            if ($field === 'status') {
                continue;
            }
            $label = self::FIELD_LABELS[$field] ?? strtoupper(str_replace('_', ' ', $field));
            $details[] = sprintf(
                'Updated %s of %s from %s to %s.',
                $label,
                $name,
                $this->formatValue($change['from'], $field),
                $this->formatValue($change['to'], $field)
            );
        }

        return implode(' ', $details);
    }

    private function actorEmail(): ?string
    {
        $request = app('request');
        if (! $request instanceof Request || ! $request->hasSession()) {
            return null;
        }

        $email = trim((string) $request->session()->get('login_user.gmail', ''));

        return $email === '' ? null : $email;
    }

    private function value(array $changes, string $field, string $side): ?string
    {
        $value = $changes[$field][$side] ?? null;

        return $value === null ? null : (string) $value;
    }

    private function formatValue(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'is_married') {
            return in_array($value, [true, 1, '1'], true) ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    private function normalizeValue(mixed $value): mixed
    {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : $value;
    }
}
