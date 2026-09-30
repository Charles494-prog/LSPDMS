<?php

namespace App\Http\Controllers;

use App\Models\Purok;
use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeliberationController extends Controller
{
    public function index()
    {
        return view('forms.deliberation', [
            'puroks' => Purok::query()
                ->orderBy('purok_number')
                ->get(['purok_number', 'purok_name', 'grupo_start', 'grupo_end']),
        ]);
    }

    public function duplicates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'registry' => ['nullable', 'string', 'max:13'],
            'is_rfid' => ['required', 'boolean'],
        ]);
        $rawRegistry = strtoupper(trim((string) ($validated['registry'] ?? '')));
        $registry = (bool) $validated['is_rfid']
            ? 'RFID'
            : (preg_match('/^[A-Z0-9]{13}$/', $rawRegistry) ? $rawRegistry : null);
        $matches = $this->findDuplicates(
            trim($validated['full_name']),
            $registry
        );

        return response()->json([
            'duplicates' => $matches->map(fn (Record $record): array => $this->duplicatePayload($record))->values(),
        ]);
    }

    public function searchSubtractRecords(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $search = mb_strtolower(trim($validated['q']));

        $records = Record::query()
            ->where(function (Builder $query) use ($search): void {
                $query->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(registry) LIKE ?', ['%'.$search.'%']);
            })
            ->orderBy('full_name')
            ->limit(10)
            ->get([
                'id',
                'full_name',
                'registry',
                'gender',
                'purok',
                'grupo',
                'kapisanan',
                'date_of_birth',
                'date_of_baptism',
                'evangelist',
                'status',
            ]);
        $purokNames = Purok::query()
            ->whereIn('purok_number', $records->pluck('purok')->filter())
            ->pluck('purok_name', 'purok_number');

        return response()->json([
            'records' => $records->map(function (Record $record) use ($purokNames): array {
                return [
                    'id' => $record->id,
                    'full_name' => $record->full_name,
                    'registry' => $record->registry,
                    'gender' => match (mb_strtoupper(trim((string) $record->gender))) {
                        'L', 'MALE' => 'Male',
                        'B', 'FEMALE' => 'Female',
                        default => $record->gender,
                    },
                    'purok' => trim((string) $record->purok),
                    'purok_name' => $purokNames[$record->purok] ?? null,
                    'grupo' => $record->grupo,
                    'kapisanan' => match (mb_strtolower(trim((string) $record->kapisanan))) {
                        'b', 'buklod' => 'Buklod',
                        'k', 'kadiwa' => 'Kadiwa',
                        default => $record->kapisanan,
                    },
                    'date_of_birth' => $record->date_of_birth?->format('Y-m-d'),
                    'date_of_baptism' => $record->date_of_baptism?->format('Y-m-d'),
                    'evangelist' => $record->evangelist,
                    'status' => mb_strtoupper(trim((string) $record->status)),
                ];
            })->values(),
        ]);
    }

    public function subtract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'record_id' => ['required', 'integer', 'exists:records,id'],
            'code' => ['required', Rule::in(['D', 'DD', 'E', 'G', 'H'])],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($validated): JsonResponse {
            $record = Record::query()->lockForUpdate()->findOrFail($validated['record_id']);
            $status = mb_strtoupper(trim((string) $record->status));

            if ($status === 'INACTIVE') {
                return response()->json(['message' => 'Already Inactive.'], 422);
            }
            if ($status === 'MK') {
                return response()->json(['message' => 'MK is not removable.'], 422);
            }
            if (! in_array($status, ['MS', 'UWP'], true)) {
                return response()->json(['message' => 'Only MS or UWP records can be marked inactive.'], 422);
            }

            $description = trim((string) ($validated['description'] ?? ''));
            $entry = 'Deliberation subtract '.$validated['code'].($description === '' ? '' : ': '.$description);
            $existingReason = trim((string) $record->reason_of_delistment);
            $record->status = 'INACTIVE';
            $record->reason_of_delistment = $existingReason === ''
                ? $entry
                : $existingReason.PHP_EOL.$entry;
            $this->appendLogEntry($record, 'Subtracted');
            $record->save();

            return response()->json([
                'message' => 'Record marked INACTIVE; it remains in the database.',
                'record' => ['id' => $record->id, 'full_name' => $record->full_name, 'status' => $record->status],
            ]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'registry' => ['nullable', 'string', 'max:13'],
            'is_rfid' => ['required', 'boolean'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'purok' => ['required', 'integer', 'exists:puroks,purok_number'],
            'grupo' => ['nullable', 'integer', 'min:1', 'max:999'],
            'kapisanan' => ['required', Rule::in(['Buklod', 'Kadiwa', 'Suliranin', 'Binhi'])],
            'date_of_baptism' => ['nullable', 'date', 'before_or_equal:today'],
            'evangelist' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(['MS', 'UWP', 'MK', 'INACTIVE'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'married' => ['nullable', Rule::in(['yes', 'no'])],
            'reuse_record_id' => ['nullable', 'integer'],
        ]);

        $name = trim($validated['full_name']);
        $registry = $this->normalizeRegistry($validated['registry'] ?? null, (bool) $validated['is_rfid']);
        $grupo = $validated['grupo'] ?? null;
        $purok = Purok::query()->where('purok_number', $validated['purok'])->firstOrFail();
        if ($grupo !== null && ((int) $grupo < $purok->grupo_start || (int) $grupo > $purok->grupo_end)) {
            throw ValidationException::withMessages([
                'grupo' => ["Grupo must be between {$purok->grupo_start} and {$purok->grupo_end} for this Purok."],
            ]);
        }

        $kapisanan = $this->deriveKapisanan(
            $validated['date_of_birth'] ?? null,
            $validated['married'] ?? null
        ) ?? $validated['kapisanan'];

        $result = DB::transaction(function () use ($validated, $name, $registry, $purok, $kapisanan, $grupo): array {
            $duplicates = $this->findDuplicates($name, $registry, lockForUpdate: true);
            $reuseId = $validated['reuse_record_id'] ?? null;

            if ($duplicates->isNotEmpty()) {
                if ($reuseId === null) {
                    throw ValidationException::withMessages([
                        'full_name' => ['This person is already in the system. Reuse the existing record instead of creating a duplicate.'],
                    ]);
                }

                $existing = $duplicates->firstWhere('id', (int) $reuseId);
                if (! $existing || mb_strtoupper(trim((string) $existing->status)) !== 'INACTIVE') {
                    throw ValidationException::withMessages([
                        'reuse_record_id' => ['Only a matching inactive record can be reused.'],
                    ]);
                }
                if (! in_array($validated['status'], ['MS', 'UWP'], true)) {
                    throw ValidationException::withMessages([
                        'status' => ['A reused inactive record can only be changed to MS or UWP.'],
                    ]);
                }

                $existing->status = $validated['status'];
                $existing->save();

                return [
                    'record' => $existing->fresh(),
                    'reused' => true,
                ];
            }

            if ($reuseId !== null) {
                throw ValidationException::withMessages([
                    'reuse_record_id' => ['The selected record does not match this name or registry.'],
                ]);
            }

            $record = Record::query()->create([
                'full_name' => $name,
                'registry' => $registry,
                'gender' => $validated['gender'] === 'Male' ? 'L' : 'B',
                'purok' => (string) $purok->purok_number,
                'grupo' => $grupo === null ? null : (string) $grupo,
                'kapisanan' => $this->storeKapisanan($kapisanan),
                'date_of_baptism' => $validated['date_of_baptism'] ?? null,
                'evangelist' => isset($validated['evangelist']) ? trim($validated['evangelist']) : null,
                'status' => $validated['status'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'filing_status' => match ($validated['married'] ?? null) {
                    'yes' => 'Married',
                    'no' => 'Not married',
                    default => null,
                },
            ]);
            $this->appendLogEntry($record, 'Added');
            $record->save();

            return [
                'record' => $record,
                'reused' => false,
            ];
        });

        return response()->json([
            'message' => $result['reused']
                ? 'Existing inactive record reused; only its status was changed.'
                : 'Deliberation record added.',
            'reused' => $result['reused'],
            'record' => [
                'id' => $result['record']->id,
                'name' => $result['record']->full_name,
                'registry' => $result['record']->registry,
                'status' => $result['record']->status,
            ],
        ], $result['reused'] ? 200 : 201);
    }

    private function findDuplicates(string $name, ?string $registry, bool $lockForUpdate = false)
    {
        $query = Record::query()
            ->where(function ($query) use ($name, $registry): void {
                $query->whereRaw('LOWER(TRIM(full_name)) = ?', [mb_strtolower($name)]);
                if ($registry !== null && $registry !== 'RFID') {
                    $query->orWhereRaw('UPPER(TRIM(registry)) = ?', [$registry]);
                }
            })
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get(['id', 'full_name', 'registry', 'status']);
    }

    private function duplicatePayload(Record $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->full_name,
            'registry' => $record->registry,
            'status' => mb_strtoupper(trim((string) $record->status)),
            'inactive' => mb_strtoupper(trim((string) $record->status)) === 'INACTIVE',
        ];
    }

    private function normalizeRegistry(?string $registry, bool $isRfid): string
    {
        if ($isRfid) {
            return 'RFID';
        }

        $registry = strtoupper(trim((string) $registry));
        if (! preg_match('/^[A-Z0-9]{13}$/', $registry)) {
            throw ValidationException::withMessages([
                'registry' => ['Registry must contain exactly 13 letters or numbers, or select RFID.'],
            ]);
        }

        return $registry;
    }

    private function appendLogEntry(Record $record, string $action): void
    {
        $existing = (string) $record->log_section;
        $entry = now()->timezone('Asia/Manila')->format('d/m/Y').': '.$action.',';
        $separator = $existing === ''
            ? ''
            : (preg_match('/,\s*$/u', $existing) === 1
                ? (preg_match('/,\s+$/u', $existing) === 1 ? '' : ' ')
                : ', ');
        $record->log_section = $existing.$separator.$entry;
    }

    private function deriveKapisanan(?string $birthDate, ?string $married): ?string
    {
        if ($birthDate === null || $married === null) {
            return null;
        }
        if ($married === 'yes') {
            return 'Buklod';
        }

        return Carbon::parse($birthDate)->age < 18 ? 'Binhi' : 'Kadiwa';
    }

    private function storeKapisanan(string $kapisanan): string
    {
        return match ($kapisanan) {
            'Buklod' => 'B',
            'Kadiwa' => 'K',
            'Binhi' => 'binhi',
            default => $kapisanan,
        };
    }
}
