<?php

namespace App\Services;

use App\Models\MonthlyMovementSession;
use App\Models\MonthlyRecordSnapshot;
use App\Models\Purok;
use App\Models\Record;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MonthlyResumenReport
{
    private const CODES = ['A', 'B', 'C+', 'C-', 'D', 'DD', 'E', 'G', 'H'];

    public function generate(Carbon $month): array
    {
        $month = $month->copy()->startOfMonth();
        $previousMonth = $month->copy()->subMonthNoOverflow();
        $previousSnapshots = MonthlyRecordSnapshot::query()
            ->whereDate('month', $previousMonth->toDateString())
            ->get(['purok_number', 'ms_count', 'uwp_count']);
        $monthSnapshots = MonthlyRecordSnapshot::query()
            ->whereDate('month', $month->toDateString())
            ->get(['purok_number', 'movement_counts']);
        $session = MonthlyMovementSession::query()
            ->with('movements')
            ->whereDate('period_month', $month->toDateString())
            ->first();
        $movementCounts = $this->movementCounts($monthSnapshots, $session);
        $baselineCounts = $this->baselineCounts($previousSnapshots, $monthSnapshots, $session, $month);

        $purokDetails = Purok::query()
            ->orderBy('purok_number')
            ->orderBy('grupo_start')
            ->get(['id', 'purok_number', 'purok_name', 'manggagawa', 'grupo_start', 'grupo_end', 'full_purok']);
        $records = Record::query()->get(['purok', 'grupo', 'status']);
        $purokNumbers = $purokDetails->pluck('purok_number')
            ->merge($previousSnapshots->pluck('purok_number'))
            ->merge($monthSnapshots->pluck('purok_number'))
            ->merge($records->pluck('purok')->map(fn ($value): int => $this->purokNumber((string) $value)))
            ->filter(fn ($number): bool => $number > 0)
            ->unique()
            ->sort()
            ->values();

        $rows = $purokNumbers->flatMap(function (int $number) use ($purokDetails, $records, $baselineCounts, $movementCounts): array {
            $segments = $purokDetails->where('purok_number', $number)->values();
            if ($segments->isEmpty()) {
                $segments = collect([[
                    'id' => null,
                    'purok_number' => $number,
                    'purok_name' => 'Purok '.$number,
                    'manggagawa' => '',
                    'grupo_start' => 1,
                    'grupo_end' => PHP_INT_MAX,
                    'full_purok' => true,
                ]]);
            }
            $weights = $segments->mapWithKeys(function ($segment) use ($records, $number): array {
                $id = is_array($segment) ? 'fallback' : (string) $segment->id;
                $start = is_array($segment) ? 1 : ($segment->full_purok ? 1 : (int) $segment->grupo_start);
                $end = is_array($segment) ? PHP_INT_MAX : ($segment->full_purok ? PHP_INT_MAX : (int) $segment->grupo_end);
                $memberCounts = $records->filter(fn (Record $record): bool =>
                    $this->purokNumber((string) $record->purok) === $number
                    && (int) $record->grupo >= $start
                    && (int) $record->grupo <= $end
                )->countBy(fn (Record $record): string => mb_strtoupper(trim((string) $record->status)));

                return [$id => $memberCounts->only(['MS', 'UWP'])->all()];
            });
            $allocatedBaseline = [];
            $allocatedMovements = [];
            foreach (['MS', 'UWP'] as $status) {
                $statusWeights = $weights->map(fn (array $counts): int => (int) ($counts[$status] ?? 0))->all();
                foreach (self::CODES as $code) {
                    $allocatedBaseline[$status][$code] = $this->allocate(
                        $code === 'A' ? (int) ($baselineCounts[$number][$status] ?? 0) : 0,
                        $statusWeights
                    );
                }
                foreach (($movementCounts[$number][$status] ?? []) as $code => $count) {
                    $code = strtoupper(trim((string) $code));
                    if (in_array($code, self::CODES, true)) {
                        $allocatedMovements[$status][$code] = $this->allocate((int) $count, $statusWeights);
                    }
                }
            }

            return $segments->map(function ($segment) use ($number, $weights, $allocatedBaseline, $allocatedMovements): array {
                $id = is_array($segment) ? 'fallback' : (string) $segment->id;
                $codesByStatus = [];
                foreach (['MS', 'UWP'] as $status) {
                    $codes = array_fill_keys(self::CODES, 0);
                    foreach (self::CODES as $code) {
                        $codes[$code] = $allocatedBaseline[$status][$code][$id] ?? 0;
                    }
                    foreach (($allocatedMovements[$status] ?? []) as $code => $distribution) {
                        $codes[$code] += $distribution[$id] ?? 0;
                    }
                    $codesByStatus[$status] = $codes;
                }

                return [
                    'purok_number' => $number,
                    'purok_label' => is_array($segment) ? 'Purok '.$number : ($segment->purok_name ?: 'Purok '.$number),
                    'maysakop' => trim((string) (is_array($segment) ? '' : $segment->manggagawa)),
                    'ms' => $codesByStatus['MS'],
                    'ms_total' => $this->endingCount($codesByStatus['MS']),
                    'uwp' => $codesByStatus['UWP'],
                    'uwp_total' => $this->endingCount($codesByStatus['UWP']),
                ];
            })->all();
        });

        $totals = [];
        foreach (['MS' => 'ms', 'UWP' => 'uwp'] as $status => $key) {
            $totals[$key] = array_fill_keys(self::CODES, 0);
            foreach ($rows as $row) {
                foreach (self::CODES as $code) {
                    $totals[$key][$code] += $row[$key][$code];
                }
            }
            $totals[$key.'_total'] = $this->endingCount($totals[$key]);
        }

        return [
            'month' => $month,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private function movementCounts(Collection $snapshots, ?MonthlyMovementSession $session): array
    {
        if ($snapshots->isNotEmpty()) {
            $counts = [];
            foreach ($snapshots as $snapshot) {
                foreach (($snapshot->movement_counts ?? []) as $status => $codes) {
                    $status = mb_strtoupper(trim((string) $status));
                    if (! in_array($status, ['MS', 'UWP'], true)) {
                        continue;
                    }
                    foreach ($codes as $code => $quantity) {
                        $code = strtoupper(trim((string) $code));
                        $counts[$snapshot->purok_number][$status][$code] =
                            ($counts[$snapshot->purok_number][$status][$code] ?? 0) + (int) $quantity;
                    }
                }
            }

            return $counts;
        }

        $counts = [];
        foreach ($session?->movements ?? [] as $movement) {
            $previous = $movement->previous_values ?? [];
            $next = $movement->next_values ?? [];
            if ($movement->action === 'add') {
                $this->increment($counts, $next['purok'] ?? null, $next['status'] ?? null, 'B');
            } elseif ($movement->action === 'subtract') {
                $code = strtoupper(trim(explode(':', (string) $movement->monthly_code, 2)[1] ?? ''));
                $this->increment($counts, $previous['purok'] ?? null, $previous['status'] ?? null, $code);
            } elseif ($movement->action === 'transfer') {
                $status = $previous['status'] ?? null;
                $this->increment($counts, $previous['purok'] ?? null, $status, 'C-');
                $this->increment($counts, $next['purok'] ?? null, $status, 'C+');
            } elseif ($movement->action === 'status') {
                $oldStatus = mb_strtoupper(trim((string) ($previous['status'] ?? '')));
                $newStatus = mb_strtoupper(trim((string) ($next['status'] ?? '')));
                $this->increment($counts, $previous['purok'] ?? null, $oldStatus, $newStatus === 'MK' ? 'E' : 'G');
                if ($newStatus !== 'MK') {
                    $this->increment($counts, $next['purok'] ?? null, $newStatus, 'B');
                }
            }
        }

        return $counts;
    }

    private function baselineCounts(
        Collection $previousSnapshots,
        Collection $monthSnapshots,
        ?MonthlyMovementSession $session,
        Carbon $month
    ): array {
        if ($previousSnapshots->isNotEmpty()) {
            $counts = [];
            foreach ($previousSnapshots as $snapshot) {
                $counts[$snapshot->purok_number]['MS'] = (int) $snapshot->ms_count;
                $counts[$snapshot->purok_number]['UWP'] = (int) $snapshot->uwp_count;
            }

            return $counts;
        }

        if ($monthSnapshots->isNotEmpty()) {
            $counts = [];
            foreach ($monthSnapshots as $snapshot) {
                foreach (['MS' => 'ms_count', 'UWP' => 'uwp_count'] as $status => $column) {
                    $movementCodes = ($snapshot->movement_counts ?? [])[$status] ?? [];
                    $movementNet = $this->endingCount(array_merge(['A' => 0], $movementCodes));
                    $counts[$snapshot->purok_number][$status] = (int) $snapshot->{$column} - $movementNet;
                }
            }

            return $counts;
        }

        if ($session === null && ! $month->isSameMonth(now())) {
            return [];
        }

        $counts = [];
        foreach (Record::query()->get(['purok', 'status']) as $record) {
            $number = $this->purokNumber((string) $record->purok);
            $status = mb_strtoupper(trim((string) $record->status));
            if ($number > 0 && in_array($status, ['MS', 'UWP'], true)) {
                $counts[$number][$status] = ($counts[$number][$status] ?? 0) + 1;
            }
        }

        foreach ($session?->movements ?? [] as $movement) {
            $previous = $movement->previous_values ?? [];
            $next = $movement->next_values ?? [];
            if ($movement->action === 'add') {
                $this->adjust($counts, $next['purok'] ?? null, $next['status'] ?? null, -1);
            } elseif ($movement->action === 'subtract') {
                $this->adjust($counts, $previous['purok'] ?? null, $previous['status'] ?? null, 1);
            } elseif ($movement->action === 'transfer') {
                $status = $previous['status'] ?? null;
                $this->adjust($counts, $previous['purok'] ?? null, $status, 1);
                $this->adjust($counts, $next['purok'] ?? null, $status, -1);
            } elseif ($movement->action === 'status') {
                $oldStatus = $previous['status'] ?? null;
                $newStatus = mb_strtoupper(trim((string) ($next['status'] ?? '')));
                $this->adjust($counts, $previous['purok'] ?? null, $oldStatus, 1);
                if ($newStatus !== 'MK') {
                    $this->adjust($counts, $next['purok'] ?? null, $newStatus, -1);
                }
            }
        }

        return $counts;
    }

    private function increment(array &$counts, mixed $purok, mixed $status, string $code): void
    {
        $number = $this->purokNumber((string) $purok);
        $status = mb_strtoupper(trim((string) $status));
        if ($number < 1 || ! in_array($status, ['MS', 'UWP'], true) || ! in_array($code, self::CODES, true)) {
            return;
        }
        $counts[$number][$status][$code] = ($counts[$number][$status][$code] ?? 0) + 1;
    }

    private function adjust(array &$counts, mixed $purok, mixed $status, int $amount): void
    {
        $number = $this->purokNumber((string) $purok);
        $status = mb_strtoupper(trim((string) $status));
        if ($number > 0 && in_array($status, ['MS', 'UWP'], true)) {
            $counts[$number][$status] = ($counts[$number][$status] ?? 0) + $amount;
        }
    }

    private function endingCount(array $codes): int
    {
        return (int) ($codes['A'] ?? 0)
            + (int) ($codes['B'] ?? 0)
            + (int) ($codes['C+'] ?? 0)
            - (int) ($codes['C-'] ?? 0)
            - (int) ($codes['D'] ?? 0)
            - (int) ($codes['DD'] ?? 0)
            - (int) ($codes['E'] ?? 0)
            - (int) ($codes['G'] ?? 0)
            - (int) ($codes['H'] ?? 0);
    }

    private function allocate(int $amount, array $weights): array
    {
        $keys = array_keys($weights);
        $totalWeight = array_sum($weights);
        if ($keys === []) {
            return [];
        }
        if ($totalWeight < 1) {
            $allocation = array_fill_keys($keys, 0);
            $allocation[$keys[0]] = $amount;

            return $allocation;
        }

        $allocation = [];
        $fractions = [];
        $allocated = 0;
        foreach ($weights as $key => $weight) {
            $exact = $amount * $weight / $totalWeight;
            $floor = (int) floor($exact);
            $allocation[$key] = $floor;
            $fractions[$key] = $exact - $floor;
            $allocated += $floor;
        }
        arsort($fractions);
        foreach (array_slice(array_keys($fractions), 0, max(0, $amount - $allocated)) as $key) {
            $allocation[$key]++;
        }

        return $allocation;
    }

    private function purokNumber(string $value): int
    {
        return (int) preg_replace('/\D+/', '', $value);
    }
}
