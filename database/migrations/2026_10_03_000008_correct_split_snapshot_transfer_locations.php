<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sessions = DB::table('monthly_movement_sessions')
            ->where('status', 'completed')
            ->whereNotNull('period_month')
            ->get(['id', 'period_month']);

        foreach ($sessions as $session) {
            $snapshots = DB::table('monthly_record_snapshots')
                ->whereDate('month', $session->period_month)
                ->get();
            $history = DB::table('permanent_history')
                ->where('session_id', $session->id)
                ->orderBy('id')
                ->get();
            $pairedTransfers = $history->filter(fn (object $row): bool => $row->action === 'transfer'
                && preg_match('/C-\s*\/\s*(?:MS|UWP)\s*:\s*C\+/i', (string) $row->monthly_code) === 1);
            if ($pairedTransfers->isEmpty() || $snapshots->isEmpty()) {
                continue;
            }

            $exactLocations = [];
            foreach ($pairedTransfers as $transfer) {
                $previous = json_decode((string) ($transfer->previous_values ?? ''), true) ?: [];
                $next = json_decode((string) ($transfer->next_values ?? ''), true) ?: [];
                $status = mb_strtoupper(trim((string) $transfer->status));
                foreach ([
                    ['values' => $previous, 'code' => 'C-'],
                    ['values' => $next, 'code' => 'C+'],
                ] as $movement) {
                    $values = $movement['values'];
                    $segmentId = $this->segmentId($values['purok'] ?? null, $values['grupo'] ?? null);
                    $number = (int) preg_replace('/\D+/', '', (string) ($values['purok'] ?? ''));
                    if ($segmentId !== null && $number > 0 && in_array($status, ['MS', 'UWP'], true)) {
                        $exactLocations[$number][$status][$movement['code']][$segmentId] =
                            ($exactLocations[$number][$status][$movement['code']][$segmentId] ?? 0) + 1;
                    }
                }
            }

            foreach ($exactLocations as $number => $statuses) {
                $numberRows = $snapshots->where('purok_number', $number);
                foreach ($statuses as $status => $codes) {
                    foreach ($codes as $code => $segmentCounts) {
                        $snapshotTotal = 0;
                        foreach ($numberRows as $snapshot) {
                            $movementCounts = json_decode((string) $snapshot->movement_counts, true) ?: [];
                            $snapshotTotal += (int) ($movementCounts[$status][$code] ?? 0);
                        }
                        $exactTotal = array_sum($segmentCounts);
                        if ($snapshotTotal === 0 || $exactTotal > $snapshotTotal) {
                            continue;
                        }

                        $remaining = $snapshotTotal - $exactTotal;
                        $residualWeights = [];
                        foreach ($numberRows as $snapshot) {
                            $movementCounts = json_decode((string) $snapshot->movement_counts, true) ?: [];
                            $oldQuantity = (int) ($movementCounts[$status][$code] ?? 0);
                            $exactQuantity = (int) ($segmentCounts[$snapshot->purok_id] ?? 0);
                            $residualWeights[$snapshot->purok_id] = max(0, $oldQuantity - $exactQuantity);
                        }
                        $residual = $this->allocate($remaining, $residualWeights);

                        foreach ($numberRows as $snapshot) {
                            $movementCounts = json_decode((string) $snapshot->movement_counts, true) ?: [];
                            $quantity = (int) ($segmentCounts[$snapshot->purok_id] ?? 0)
                                + (int) ($residual[$snapshot->purok_id] ?? 0);
                            if ($quantity > 0) {
                                $movementCounts[$status][$code] = $quantity;
                            } else {
                                unset($movementCounts[$status][$code]);
                                if (($movementCounts[$status] ?? []) === []) {
                                    unset($movementCounts[$status]);
                                }
                            }
                            DB::table('monthly_record_snapshots')
                                ->where('id', $snapshot->id)
                                ->update(['movement_counts' => json_encode($movementCounts, JSON_THROW_ON_ERROR)]);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Exact segment locations cannot be safely collapsed back to their previous weighted allocation.
    }

    private function segmentId(mixed $purok, mixed $grupo): ?int
    {
        $number = (int) preg_replace('/\D+/', '', (string) $purok);
        $group = (int) preg_replace('/\D+/', '', (string) $grupo);
        $segment = DB::table('puroks')
            ->where('purok_number', $number)
            ->where(function ($query) use ($group): void {
                $query->where('full_purok', true)
                    ->orWhere(function ($query) use ($group): void {
                        $query->where('grupo_start', '<=', $group)
                            ->where('grupo_end', '>=', $group);
                    });
            })
            ->orderBy('grupo_start')
            ->orderBy('id')
            ->first(['id']);

        return $segment === null ? null : (int) $segment->id;
    }

    private function allocate(int $total, array $weights): array
    {
        $allocation = array_fill_keys(array_keys($weights), 0);
        if ($total <= 0 || $weights === []) {
            return $allocation;
        }

        $weightTotal = array_sum($weights);
        if ($weightTotal <= 0) {
            $allocation[array_key_first($allocation)] = $total;

            return $allocation;
        }

        $remainders = [];
        foreach ($weights as $segmentId => $weight) {
            $scaled = $total * $weight;
            $allocation[$segmentId] = intdiv($scaled, $weightTotal);
            $remainders[$segmentId] = $scaled % $weightTotal;
        }
        arsort($remainders);
        $remaining = $total - array_sum($allocation);
        foreach (array_keys($remainders) as $segmentId) {
            if ($remaining <= 0) {
                break;
            }
            $allocation[$segmentId]++;
            $remaining--;
        }

        return $allocation;
    }
};
