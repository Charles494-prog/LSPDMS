<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_record_snapshots', function (Blueprint $table): void {
            $table->unsignedBigInteger('purok_id')->nullable()->after('purok_number');
        });
        Schema::table('monthly_record_snapshots', function (Blueprint $table): void {
            $table->dropUnique('monthly_record_snapshots_month_purok_number_unique');
        });

        foreach (DB::table('monthly_record_snapshots')->orderBy('id')->get() as $snapshot) {
            $segments = DB::table('puroks')
                ->where('purok_number', $snapshot->purok_number)
                ->orderBy('grupo_start')
                ->orderBy('id')
                ->get();
            if ($segments->isEmpty()) {
                continue;
            }

            $records = DB::table('records')
                ->where('purok', (string) $snapshot->purok_number)
                ->get(['grupo', 'status']);
            $weights = [];
            foreach ($segments as $segment) {
                $start = $segment->full_purok ? 1 : (int) $segment->grupo_start;
                $end = $segment->full_purok ? PHP_INT_MAX : (int) $segment->grupo_end;
                $weights[$segment->id] = ['MS' => 0, 'UWP' => 0, 'MK' => 0];
                foreach ($records as $record) {
                    $grupo = (int) preg_replace('/\D+/', '', (string) $record->grupo);
                    $status = mb_strtoupper(trim((string) $record->status));
                    if ($grupo >= $start && $grupo <= $end && isset($weights[$segment->id][$status])) {
                        $weights[$segment->id][$status]++;
                    }
                }
            }

            $movementCounts = json_decode((string) $snapshot->movement_counts, true) ?: [];
            $splitRows = [];
            foreach ($segments as $segment) {
                $splitRows[$segment->id] = [
                    'purok_id' => $segment->id,
                    'ms_count' => 0,
                    'uwp_count' => 0,
                    'mk_count' => 0,
                    'movement_counts' => [],
                ];
            }
            foreach (['MS' => 'ms_count', 'UWP' => 'uwp_count', 'MK' => 'mk_count'] as $status => $column) {
                foreach ($this->allocate((int) $snapshot->{$column}, array_map(
                    fn (array $counts): int => $counts[$status],
                    $weights
                )) as $segmentId => $count) {
                    $splitRows[$segmentId][$column] = $count;
                }
            }
            foreach ($movementCounts as $status => $codes) {
                $status = mb_strtoupper((string) $status);
                foreach ($codes as $code => $count) {
                    $distribution = $this->allocate(
                        (int) $count,
                        array_map(fn (array $counts): int => $counts[$status] ?? 0, $weights)
                    );
                    foreach ($distribution as $segmentId => $quantity) {
                        if ($quantity !== 0) {
                            $splitRows[$segmentId]['movement_counts'][$status][$code] = $quantity;
                        }
                    }
                }
            }

            $firstSegmentId = $segments->first()->id;
            $first = $splitRows[$firstSegmentId];
            DB::table('monthly_record_snapshots')->where('id', $snapshot->id)->update([
                'purok_id' => $firstSegmentId,
                'ms_count' => $first['ms_count'],
                'uwp_count' => $first['uwp_count'],
                'mk_count' => $first['mk_count'],
                'movement_counts' => json_encode($first['movement_counts'], JSON_THROW_ON_ERROR),
            ]);

            foreach ($segments->skip(1) as $segment) {
                $row = (array) $snapshot;
                unset($row['id']);
                $row = array_merge($row, $splitRows[$segment->id], [
                    'movement_counts' => json_encode(
                        $splitRows[$segment->id]['movement_counts'],
                        JSON_THROW_ON_ERROR
                    ),
                ]);
                DB::table('monthly_record_snapshots')->insert($row);
            }
        }

        Schema::table('monthly_record_snapshots', function (Blueprint $table): void {
            $table->unique(['month', 'purok_id']);
        });
    }

    public function down(): void
    {
        foreach (DB::table('monthly_record_snapshots')
            ->orderBy('month')
            ->orderBy('purok_number')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (object $row): string => $row->month.'|'.$row->purok_number) as $rows) {
            $first = $rows->first();
            if ($rows->count() === 1) {
                continue;
            }

            $totals = ['ms_count' => 0, 'uwp_count' => 0, 'mk_count' => 0];
            $movementCounts = [];
            foreach ($rows as $row) {
                foreach (array_keys($totals) as $column) {
                    $totals[$column] += (int) $row->{$column};
                }
                foreach (json_decode((string) $row->movement_counts, true) ?: [] as $status => $codes) {
                    foreach ($codes as $code => $quantity) {
                        $movementCounts[$status][$code] = ($movementCounts[$status][$code] ?? 0) + (int) $quantity;
                    }
                }
            }
            DB::table('monthly_record_snapshots')->where('id', $first->id)->update([
                ...$totals,
                'movement_counts' => json_encode($movementCounts, JSON_THROW_ON_ERROR),
            ]);
            DB::table('monthly_record_snapshots')->whereIn('id', $rows->skip(1)->pluck('id'))->delete();
        }

        Schema::table('monthly_record_snapshots', function (Blueprint $table): void {
            $table->dropUnique('monthly_record_snapshots_month_purok_id_unique');
            $table->dropColumn('purok_id');
        });
        Schema::table('monthly_record_snapshots', function (Blueprint $table): void {
            $table->unique(['month', 'purok_number']);
        });
    }

    private function allocate(int $total, array $weights): array
    {
        $allocation = array_fill_keys(array_keys($weights), 0);
        if ($total <= 0 || $weights === []) {
            return $allocation;
        }

        $weightTotal = array_sum($weights);
        if ($weightTotal <= 0) {
            $first = array_key_first($allocation);
            $allocation[$first] = $total;

            return $allocation;
        }

        $remainders = [];
        foreach ($weights as $segmentId => $weight) {
            $scaled = $total * max(0, $weight);
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
