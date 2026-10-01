<?php

namespace App\Http\Controllers;

use App\Models\Purok;
use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilterBnhHelperController extends Controller
{
    public function index()
    {
        return view('filter-bnh-helper', [
            'puroks' => Purok::query()
                ->orderBy('purok_number')
                ->orderBy('grupo_start')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function matrix(Request $request): JsonResponse
    {
        [$puroks, $records] = $this->filteredData($request);
        $rows = $this->matrixRows($puroks, $records);
        $totals = $this->countTotals($records);

        return response()->json([
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function members(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purok_id' => ['required', 'string'],
            'grupo' => ['nullable', 'integer', 'min:0'],
            'gender' => ['required', Rule::in(['male', 'female', 'all'])],
            'status' => ['required', Rule::in(['MS', 'UWP', 'MK', 'ALL'])],
        ]);
        $puroks = $this->selectedPuroks($validated['purok_id']);
        $records = $this->recordsFor($puroks, $validated['status'])
            ->filter(fn (Record $record): bool => (! isset($validated['grupo']) || (int) $record->grupo === (int) $validated['grupo'])
                && ($validated['gender'] === 'all' || $this->normalizeGender($record->gender) === $validated['gender']))
            ->sortBy(fn (Record $record): string => mb_strtolower((string) $record->full_name))
            ->values();
        $allPuroks = Purok::query()->orderBy('purok_number')->orderBy('grupo_start')->orderBy('id')->get();

        return response()->json([
            'count' => $records->count(),
            'records' => $records->map(function (Record $record) use ($allPuroks): array {
                $purok = $this->resolvePurok($record, $allPuroks);

                return [
                    'name' => $record->full_name ?: 'Unnamed record',
                    'status' => mb_strtoupper(trim((string) $record->status)),
                    'location' => ($purok ? $this->shortPurokName($purok) : trim((string) $record->purok)).'-'.$record->grupo,
                ];
            }),
        ]);
    }

    public function bnhRecords(Request $request): JsonResponse
    {
        [$puroks, $records] = $this->filteredData($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 100;
        $allPuroks = Purok::query()->orderBy('purok_number')->orderBy('grupo_start')->orderBy('id')->get();
        $resolved = $this->searchBnhRecords($records, (string) $request->query('q', ''))
            ->map(fn (Record $record): array => $this->bnhRecord($record, $allPuroks))
            ->sortBy(fn (array $row): array => $this->bnhRecordSortKey($row))
            ->values();
        $total = $resolved->count();

        return response()->json([
            'records' => $resolved->forPage($page, $perPage)->values(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function updateChecks(Request $request, Record $record): JsonResponse
    {
        $validated = $request->validate([
            'is_valid1' => ['required', 'boolean'],
            'is_valid2' => ['required', 'boolean'],
        ]);

        abort_if(mb_strtoupper(trim((string) $record->status)) === 'INACTIVE', 404);

        $record->forceFill([
            'is_valid1' => (bool) $validated['is_valid1'],
            'is_valid2' => (bool) $validated['is_valid2'],
        ])->save();

        return response()->json([
            'message' => 'BNH helper checks saved.',
            'is_valid1' => (bool) $record->is_valid1,
            'is_valid2' => (bool) $record->is_valid2,
            'qualified' => (bool) $record->is_valid1 && (bool) $record->is_valid2,
        ]);
    }

    public function qualifiedCounts(Request $request): JsonResponse
    {
        [$puroks, $records] = $this->filteredData($request);
        $qualified = $records->filter(fn (Record $record): bool => $record->is_valid1 && $record->is_valid2);
        $rows = $this->matrixRows($puroks, $qualified);

        return response()->json([
            'rows' => $rows,
            'totals' => $this->countTotals($qualified),
        ]);
    }

    public function downloadBnhWord(Request $request): StreamedResponse
    {
        [, $records] = $this->filteredData($request);
        $allPuroks = Purok::query()->orderBy('purok_number')->orderBy('grupo_start')->orderBy('id')->get();
        $rows = $this->searchBnhRecords($records, (string) $request->query('q', ''))
            ->map(fn (Record $record): array => $this->bnhRecord($record, $allPuroks))
            ->sortBy(fn (array $row): array => $this->bnhRecordSortKey($row))
            ->values()
            ->all();

        return $this->wordDownload('BNH Form Helper', 'bnh-form-helper.doc', 'filter-bnh-helper.word-list', [
            'rows' => $rows,
            'filters' => $this->filterLabels($request),
        ]);
    }

    public function downloadCountsWord(Request $request): StreamedResponse
    {
        [$puroks, $records] = $this->filteredData($request);
        $rows = $this->matrixRows(
            $puroks,
            $records->filter(fn (Record $record): bool => $record->is_valid1 && $record->is_valid2)
        );
        $totals = $this->countTotals($records->filter(fn (Record $record): bool => $record->is_valid1 && $record->is_valid2));

        return $this->wordDownload('BNH Helper Form Resumen', 'bnh-helper-form-resumen.doc', 'filter-bnh-helper.word-counts', [
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $this->filterLabels($request),
        ]);
    }

    /**
     * @return array{0: Collection<int, Purok>, 1: Collection<int, Record>}
     */
    private function filteredData(Request $request): array
    {
        $validated = $request->validate([
            'purok_id' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['MS', 'UWP', 'MK', 'ALL'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);
        $puroks = $this->selectedPuroks($validated['purok_id'] ?? 'all');
        $records = $this->recordsFor($puroks, $validated['status'] ?? 'ALL');

        return [$puroks, $records];
    }

    /**
     * @param  Collection<int, Record>  $records
     * @return Collection<int, Record>
     */
    private function searchBnhRecords(Collection $records, string $term): Collection
    {
        $term = mb_strtolower(trim($term));
        if ($term === '') {
            return $records;
        }

        return $records->filter(fn (Record $record): bool => str_contains(
            mb_strtolower(trim((string) $record->full_name)),
            $term
        ))->values();
    }

    private function selectedPuroks(string $purokId): Collection
    {
        $query = Purok::query()
            ->orderBy('purok_number')
            ->orderBy('grupo_start')
            ->orderBy('id');

        if ($purokId !== 'all') {
            abort_unless(ctype_digit($purokId), 422, 'Choose a valid Purok.');
            $query->whereKey((int) $purokId);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Purok>  $puroks
     * @return Collection<int, Record>
     */
    private function recordsFor(Collection $puroks, string $status): Collection
    {
        if ($puroks->isEmpty()) {
            return collect();
        }

        $allPuroks = Purok::query()
            ->orderBy('purok_number')
            ->orderBy('grupo_start')
            ->orderBy('id')
            ->get();

        return Record::query()
            ->whereRaw("UPPER(TRIM(COALESCE(status, ''))) != 'INACTIVE'")
            ->when($status !== 'ALL', fn (Builder $query): Builder => $query->whereRaw('UPPER(TRIM(COALESCE(status, \'\'))) = ?', [$status]))
            ->get([
                'id',
                'full_name',
                'gender',
                'purok',
                'grupo',
                'status',
                'is_valid1',
                'is_valid2',
            ])
            ->filter(function (Record $record) use ($puroks, $allPuroks): bool {
                $resolved = $this->resolvePurok($record, $allPuroks);

                return $resolved !== null && $puroks->contains(fn (Purok $purok): bool => (int) $purok->id === (int) $resolved->id);
            })
            ->values();
    }

    /**
     * @param  Collection<int, Purok>  $puroks
     * @param  Collection<int, Record>  $records
     * @return list<array{type: string, id: string, purok_id: int|null, grupo: int|null, label: string, male: int, female: int, total: int}>
     */
    private function matrixRows(Collection $puroks, Collection $records): array
    {
        $allPuroks = Purok::query()
            ->orderBy('purok_number')
            ->orderBy('grupo_start')
            ->orderBy('id')
            ->get();
        $resolvedRecords = $records->map(fn (Record $record): array => [
            'record' => $record,
            'purok_id' => $this->resolvePurok($record, $allPuroks)?->id,
        ]);
        $rows = [];
        foreach ($puroks as $purok) {
            $purokMale = 0;
            $purokFemale = 0;
            $purokTotal = 0;
            for ($grupo = (int) $purok->grupo_start; $grupo <= (int) $purok->grupo_end; $grupo++) {
                $members = $resolvedRecords
                    ->filter(fn (array $item): bool => (int) $item['purok_id'] === (int) $purok->id
                        && (int) $item['record']->grupo === $grupo)
                    ->pluck('record');
                $male = $members->filter(fn (Record $record): bool => $this->normalizeGender($record->gender) === 'male')->count();
                $female = $members->filter(fn (Record $record): bool => $this->normalizeGender($record->gender) === 'female')->count();
                $purokMale += $male;
                $purokFemale += $female;
                $purokTotal += $members->count();

                $rows[] = [
                    'type' => 'group',
                    'id' => $purok->id.'-'.$grupo,
                    'purok_id' => (int) $purok->id,
                    'grupo' => $grupo,
                    'label' => $this->shortPurokName($purok).'-'.$grupo,
                    'male' => $male,
                    'female' => $female,
                    'total' => $members->count(),
                ];
            }
            $rows[] = [
                'type' => 'purok_total',
                'id' => 'purok-'.$purok->id,
                'purok_id' => (int) $purok->id,
                'grupo' => null,
                'label' => 'Total '.$this->shortPurokName($purok),
                'male' => $purokMale,
                'female' => $purokFemale,
                'total' => $purokTotal,
            ];
        }

        $overall = $this->countTotals($records);
        $rows[] = array_merge([
            'type' => 'grand_total',
            'id' => 'grand-total',
            'purok_id' => null,
            'grupo' => null,
            'label' => 'Overall Total',
        ], $overall);

        return $rows;
    }

    /**
     * @param  Collection<int, Record>  $records
     * @return array{male: int, female: int, total: int}
     */
    private function countTotals(Collection $records): array
    {
        return [
            'male' => $records->filter(fn (Record $record): bool => $this->normalizeGender($record->gender) === 'male')->count(),
            'female' => $records->filter(fn (Record $record): bool => $this->normalizeGender($record->gender) === 'female')->count(),
            'total' => $records->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{int, int, string}
     */
    private function bnhRecordSortKey(array $row): array
    {
        $location = (string) $row['location'];
        $purok = preg_match('/^(\d+)/', $location, $matches) === 1
            ? (int) $matches[1]
            : PHP_INT_MAX;
        $grupo = preg_match('/-(\d+)$/', $location, $matches) === 1
            ? (int) $matches[1]
            : PHP_INT_MAX;

        return [$purok, $grupo, mb_strtolower((string) $row['name'])];
    }

    /**
     * @param  Collection<int, Purok>  $allPuroks
     */
    private function resolvePurok(Record $record, Collection $allPuroks): ?Purok
    {
        $number = $this->normalizePurokNumber($record->purok);
        if ($number === null) {
            return null;
        }

        $matches = $allPuroks->where('purok_number', $number)->values();
        if ($matches->count() <= 1) {
            return $matches->first();
        }

        return $matches->first(fn (Purok $purok): bool => $purok->full_purok
                || (is_numeric($record->grupo)
                    && (int) $record->grupo >= (int) $purok->grupo_start
                    && (int) $record->grupo <= (int) $purok->grupo_end));
    }

    /**
     * @param  Collection<int, Purok>  $puroks
     * @return array<string, mixed>
     */
    private function bnhRecord(Record $record, Collection $puroks): array
    {
        $purok = $this->resolvePurok($record, $puroks);
        $isValid1 = (bool) $record->is_valid1;
        $isValid2 = (bool) $record->is_valid2;

        return [
            'id' => (int) $record->id,
            'name' => $record->full_name ?: 'Unnamed record',
            'status' => mb_strtoupper(trim((string) $record->status)),
            'location' => $purok ? $this->shortPurokName($purok).'-'.$record->grupo : trim((string) $record->purok).'-'.trim((string) $record->grupo),
            'is_valid1' => $isValid1,
            'is_valid2' => $isValid2,
            'qualified' => $isValid1 && $isValid2,
        ];
    }

    private function normalizeGender(mixed $gender): ?string
    {
        return match (mb_strtolower(trim((string) $gender))) {
            'male', 'm', 'lalaki', 'l' => 'male',
            'female', 'f', 'babae', 'b' => 'female',
            default => null,
        };
    }

    private function normalizePurokNumber(mixed $value): ?int
    {
        $value = trim((string) $value);
        if (preg_match('/^(?:Purok\s*)?(\d+)$/i', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function shortPurokName(Purok $purok): string
    {
        $name = trim((string) $purok->purok_name);
        if ($name === '') {
            return (string) $purok->purok_number;
        }

        return trim((string) preg_replace('/^Purok\s*/i', '', $name));
    }

    private function wordDownload(string $title, string $filename, string $view, array $data): StreamedResponse
    {
        $html = view($view, ['title' => $title] + $data)->render();

        return Response::streamDownload(
            static function () use ($html): void {
                echo $html;
            },
            $filename,
            ['Content-Type' => 'application/msword; charset=UTF-8']
        );
    }

    /**
     * @return array{purok: string, status: string}
     */
    private function filterLabels(Request $request): array
    {
        $purokId = (string) $request->query('purok_id', 'all');
        $purok = $purokId === 'all'
            ? 'All Puroks'
            : (Purok::query()->find($purokId)?->purok_name ?: 'Selected Purok');

        return [
            'purok' => $purok,
            'status' => (string) $request->query('status', 'ALL'),
        ];
    }
}
