<?php

namespace App\Http\Controllers;

use App\Models\Purok;
use App\Models\Record;
use App\Models\MonthlyRecordCodeHistory;
use App\Models\MonthlyRecordMovementHistory;
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
                ->orderBy('grupo_start')
                ->orderBy('id')
                ->get(['id', 'purok_number', 'purok_name', 'grupo_start', 'grupo_end']),
        ]);
    }

    public function modifyIndex()
    {
        return view('forms.monthly-modify', [
            'puroks' => Purok::query()
                ->orderBy('purok_number')
                ->orderBy('grupo_start')
                ->orderBy('id')
                ->get(['id', 'purok_number', 'purok_name', 'grupo_start', 'grupo_end', 'full_purok']),
        ]);
    }

    public function modifySearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $query = mb_strtolower(trim($validated['q']));
        $records = Record::query()
            ->where(fn (Builder $builder) => $builder
                ->whereRaw('LOWER(full_name) LIKE ?', ['%'.$query.'%'])
                ->orWhereRaw('LOWER(registry) LIKE ?', ['%'.$query.'%']))
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name', 'registry', 'status', 'purok', 'grupo', 'monthly_code']);

        return response()->json([
            'records' => $records->map(function (Record $record): array {
                $purokNumber = (int) preg_replace('/\D+/', '', (string) $record->purok);
                $purok = Purok::query()
                    ->where('purok_number', $purokNumber)
                    ->where(function ($query) use ($record): void {
                        $query->where('full_purok', true)
                            ->orWhere(function ($query) use ($record): void {
                                $query->where('grupo_start', '<=', (int) $record->grupo)
                                    ->where('grupo_end', '>=', (int) $record->grupo);
                            });
                    })
                    ->orderBy('id')
                    ->first();

                return [
                    'id' => $record->id,
                    'name' => $record->full_name,
                    'registry' => $record->registry,
                    'status' => $record->status,
                    'purok' => (string) $record->purok,
                    'purok_name' => $purok?->purok_name ?: 'Purok '.$record->purok,
                    'grupo' => (string) $record->grupo,
                    'purok_id' => $purok?->id,
                    'monthly_code' => trim((string) $record->monthly_code),
                ];
            })->values(),
        ]);
    }

    public function modify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'record_id' => ['required', 'integer', 'exists:records,id'],
            'action' => ['required', Rule::in(['status', 'transfer'])],
            'status' => ['required_if:action,status', Rule::in(['MS', 'UWP', 'MK'])],
            'purok_id' => ['required_if:action,transfer', 'integer', 'exists:puroks,id'],
            'grupo' => ['required_if:action,transfer', 'integer', 'min:1', 'max:999'],
            'confirmed_mk_demotion' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'endorsement_reason' => ['required_if:status,MK', 'nullable', 'string', 'max:2000'],
        ]);
        if ($validated['action'] === 'status' && (array_key_exists('purok_id', $validated) || array_key_exists('grupo', $validated))) {
            throw ValidationException::withMessages([
                'action' => ['Choose either a status change or a Purok transfer, not both.'],
            ]);
        }
        if ($validated['action'] === 'transfer' && array_key_exists('status', $validated)) {
            throw ValidationException::withMessages([
                'action' => ['Choose either a Purok transfer or a status change, not both.'],
            ]);
        }
        $destination = null;
        if ($validated['action'] === 'transfer') {
            $destination = Purok::query()->findOrFail($validated['purok_id']);
            $grupoMin = (int) $destination->grupo_start;
            $grupoMax = (int) $destination->grupo_end;
            if ((int) $validated['grupo'] < $grupoMin || (int) $validated['grupo'] > $grupoMax) {
                throw ValidationException::withMessages([
                    'grupo' => ["Grupo must be between {$grupoMin} and {$grupoMax} for {$destination->purok_name}."],
                ]);
            }
        }

        $record = DB::transaction(function () use ($validated, $destination, $request): Record {
            $record = Record::query()->lockForUpdate()->findOrFail($validated['record_id']);
            $this->ensureNoMonthlyCode($record);
            $oldStatus = mb_strtoupper(trim((string) $record->status));
            if ($oldStatus === 'INACTIVE') {
                throw ValidationException::withMessages([
                    'record_id' => ['Inactive records can be searched but cannot be changed in Monthly Modify.'],
                ]);
            }
            if (! in_array($oldStatus, ['MS', 'UWP', 'MK'], true)) {
                throw ValidationException::withMessages([
                    'record_id' => ['Only MS, UWP, or MK records can be changed in Monthly Modify.'],
                ]);
            }

            if ($validated['action'] === 'status') {
                $newStatus = $validated['status'];
                if ($oldStatus === 'MK' && $newStatus !== 'MK' && ! ($validated['confirmed_mk_demotion'] ?? false)) {
                    throw ValidationException::withMessages([
                        'confirmed_mk_demotion' => ['Confirm that you want to change this MK record to a lower status.'],
                    ]);
                }
                if ($oldStatus === $newStatus) {
                    throw ValidationException::withMessages([
                        'status' => ['Choose a status different from the member’s current status.'],
                    ]);
                }

                $historyCode = $newStatus === 'MK' ? 'E' : 'G';
                $monthlyCode = $newStatus === 'MK' ? null : $newStatus.': B';
                $endorsementReason = '';
                if ($newStatus === 'MK') {
                    $endorsementReason = $this->normalizeDescription($validated['endorsement_reason'] ?? null);
                    if ($endorsementReason === '') {
                        throw ValidationException::withMessages([
                            'endorsement_reason' => ['Enter a reason for changing this member to MK.'],
                        ]);
                    }
                }
                $movementHistory = $this->storeMonthlyMovementHistory(
                    $record,
                    $historyCode,
                    $request,
                    $newStatus
                );
                if ($newStatus === 'MK') {
                    $datedReason = now()->timezone('Asia/Manila')->format('d-m-Y').': ('.$endorsementReason.')';
                    $existingReason = trim((string) $record->reason_of_r2_10_endorsement);
                    $record->reason_of_r2_10_endorsement = $existingReason === ''
                        ? $datedReason
                        : $existingReason.PHP_EOL.$datedReason;
                }
                $this->storeMonthlyCodeHistory($record, "{$oldStatus}: {$historyCode}", $movementHistory, $request);
                if ($monthlyCode !== null) {
                    $this->storeMonthlyCodeHistory(
                        $record,
                        $monthlyCode,
                        $movementHistory,
                        $request,
                        status: $newStatus
                    );
                }
                $record->status = $newStatus;
                $record->monthly_code = $monthlyCode;
                $this->appendLogEntry(
                    $record,
                    "Monthly modify: status {$oldStatus} to {$newStatus} ({$oldStatus}: {$historyCode})"
                        .($monthlyCode === null ? '' : ", {$monthlyCode}"),
                    $validated['description'] ?? null
                );
                $record->save();

                return $record;
            }

            $oldPurok = trim((string) $record->purok) ?: 'Unassigned';
            $oldGrupo = trim((string) $record->grupo) ?: 'Unassigned';
            $oldPurokNumber = (int) preg_replace('/\D+/', '', $oldPurok);
            $source = Purok::query()
                ->where('purok_number', $oldPurokNumber)
                ->where(function ($query) use ($oldGrupo): void {
                    $query->where('full_purok', true)
                        ->orWhere(function ($query) use ($oldGrupo): void {
                            $query->where('grupo_start', '<=', (int) $oldGrupo)
                                ->where('grupo_end', '>=', (int) $oldGrupo);
                        });
                })
                ->orderBy('id')
                ->first();
            if (! $source) {
                throw ValidationException::withMessages([
                    'purok_id' => ['The member’s current Purok / Grupo does not match a configured Purok. Correct the record in Inquire / Modify before transferring.'],
                ]);
            }
            if (! $destination || $source->id === $destination->id) {
                throw ValidationException::withMessages([
                    'purok_id' => ['Monthly Modify is for transfers to a different configured Purok. For a Grupo-only correction, use Inquire / Modify.'],
                ]);
            }
            $oldPurokName = $source?->purok_name ?: 'Purok '.$oldPurok;
            $movementHistory = $this->storeMonthlyMovementHistory(
                $record,
                'C-',
                $request,
                $oldStatus
            );
            $this->storeMonthlyCodeHistory($record, "{$oldStatus}: C-", $movementHistory, $request);
            $this->storeMonthlyCodeHistory(
                $record,
                "{$oldStatus}: C+",
                $movementHistory,
                $request,
                purok: (string) $destination->purok_number,
                grupo: (string) $validated['grupo']
            );
            $record->purok = (string) $destination->purok_number;
            $record->grupo = (string) $validated['grupo'];
            $record->monthly_code = $oldStatus.': C+';
            $this->appendLogEntry(
                $record,
                "Monthly modify: {$oldPurokName} Grupo {$oldGrupo} to {$destination->purok_name} Grupo {$validated['grupo']} ({$oldStatus}: C- / {$oldStatus}: C+)",
                $validated['description'] ?? null
            );
            $record->save();

            return $record;
        });

        return response()->json([
            'message' => $validated['action'] === 'status'
                ? ($record->monthly_code
                    ? "Status updated. Current monthly code: {$record->monthly_code}; previous status movement is recorded in history."
                    : 'Status updated to MK. Previous status movement is recorded in history.')
                : "Purok and Grupo updated. Current monthly code: {$record->monthly_code}; previous location is recorded in history.",
            'record' => [
                'id' => $record->id,
                'name' => $record->full_name,
                'purok' => $record->purok,
                'grupo' => $record->grupo,
                'status' => $record->status,
                'monthly_code' => $record->monthly_code,
            ],
        ]);
    }

    private function storeMonthlyMovementHistory(
        Record $record,
        string $code,
        Request $request,
        ?string $destinationStatus
    ): MonthlyRecordMovementHistory {
        $status = mb_strtoupper(trim((string) $record->status));
        return MonthlyRecordMovementHistory::query()->create([
            'record_id' => $record->id,
            'full_name' => trim((string) $record->full_name) ?: 'Unnamed record',
            'registry' => trim((string) $record->registry) ?: null,
            'gender' => trim((string) $record->gender) ?: null,
            'kapisanan' => trim((string) $record->kapisanan) ?: null,
            'date_of_baptism' => $record->date_of_baptism?->format('Y-m-d'),
            'evangelist' => trim((string) $record->evangelist) ?: null,
            'previous_purok' => trim((string) $record->purok) ?: null,
            'previous_grupo' => trim((string) $record->grupo) ?: null,
            'status' => $status,
            'monthly_code' => $status.': '.$code,
            'destination_status' => $destinationStatus,
            'changed_by' => trim(implode(' ', array_filter([
                (string) $request->session()->get('login_user.first_name', ''),
                (string) $request->session()->get('login_user.last_name', ''),
            ]))) ?: $request->session()->get('login_user.gmail'),
            'changed_at' => now(),
        ]);
    }

    private function storeMonthlyCodeHistory(
        Record $record,
        string $monthlyCode,
        MonthlyRecordMovementHistory $movementHistory,
        Request $request,
        ?string $status = null,
        ?string $purok = null,
        ?string $grupo = null
    ): void {
        MonthlyRecordCodeHistory::query()->create([
            'record_id' => $record->id,
            'full_name' => trim((string) $record->full_name) ?: 'Unnamed record',
            'registry' => trim((string) $record->registry) ?: null,
            'gender' => trim((string) $record->gender) ?: null,
            'kapisanan' => trim((string) $record->kapisanan) ?: null,
            'date_of_baptism' => $record->date_of_baptism?->format('Y-m-d'),
            'evangelist' => trim((string) $record->evangelist) ?: null,
            'purok' => $purok ?? (trim((string) $record->purok) ?: null),
            'grupo' => $grupo ?? (trim((string) $record->grupo) ?: null),
            'status' => $status ?? mb_strtoupper(trim((string) $record->status)),
            'monthly_code' => $monthlyCode,
            'deliberated_at' => now()->timezone('Asia/Manila')->toDateString(),
            'changed_by' => $movementHistory->changed_by,
            'movement_history_id' => $movementHistory->id,
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
                'monthly_code',
            ]);
        $puroksByNumber = Purok::query()
            ->whereIn('purok_number', $records->pluck('purok')->filter())
            ->orderBy('grupo_start')
            ->get(['purok_number', 'purok_name', 'grupo_start', 'grupo_end', 'full_purok'])
            ->groupBy(fn (Purok $purok): string => (string) $purok->purok_number);

        return response()->json([
            'records' => $records->map(function (Record $record) use ($puroksByNumber): array {
                $purokSettings = $puroksByNumber->get(trim((string) $record->purok), collect());
                $grupo = (int) $record->grupo;
                $purokSetting = $purokSettings->first(fn (Purok $purok): bool => $purok->full_purok
                    || ($grupo >= (int) $purok->grupo_start && $grupo <= (int) $purok->grupo_end));

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
                    'purok_name' => $purokSetting?->purok_name,
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
                    'monthly_code' => trim((string) $record->monthly_code),
                ];
            })->values(),
        ]);
    }

    public function subtract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'record_id' => ['required', 'integer', 'exists:records,id'],
            'code' => ['required', Rule::in(['D', 'DD', 'G', 'H'])],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($validated): JsonResponse {
            $record = Record::query()->lockForUpdate()->findOrFail($validated['record_id']);
            $status = mb_strtoupper(trim((string) $record->status));

            if ($status === 'INACTIVE') {
                return response()->json(['message' => 'Already Inactive.'], 422);
            }
            $this->ensureNoMonthlyCode($record);
            if ($status === 'MK') {
                return response()->json(['message' => 'MK is not removable.'], 422);
            }
            if (! in_array($status, ['MS', 'UWP'], true)) {
                return response()->json(['message' => 'Only MS or UWP records can be marked inactive.'], 422);
            }

            $description = $this->normalizeDescription($validated['description'] ?? null);
            $entry = 'Deliberation subtract '.$validated['code'].($description === '' ? '' : ': '.$description);
            $existingReason = trim((string) $record->reason_of_r2_10_endorsement);
            $record->monthly_code = $status.': '.$validated['code'];
            $record->status = 'INACTIVE';
            $record->reason_of_r2_10_endorsement = $existingReason === ''
                ? $entry
                : $existingReason.PHP_EOL.$entry;
            $this->appendLogEntry($record, $record->monthly_code, null, 'd-m-Y');
            if ($description !== '') {
                $this->appendLogEntry($record, $description, null, 'd-m-Y');
            }
            $record->save();

            return response()->json([
                'message' => 'Record marked INACTIVE; it remains in the database.',
                'record' => [
                    'id' => $record->id,
                    'full_name' => $record->full_name,
                    'status' => $record->status,
                    'monthly_code' => $record->monthly_code,
                ],
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
            'purok_id' => ['required', 'integer', 'exists:puroks,id'],
            'grupo' => ['nullable', 'integer', 'min:1', 'max:999'],
            'kapisanan' => ['required', Rule::in(['Buklod', 'Kadiwa', 'Suliranin', 'Binhi'])],
            'date_of_baptism' => ['nullable', 'date', 'before_or_equal:today'],
            'evangelist' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(['MS', 'UWP', 'MK'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'is_married' => ['sometimes', 'required', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'reuse_record_id' => ['nullable', 'integer'],
        ]);

        $name = trim($validated['full_name']);
        $registry = $this->normalizeRegistry($validated['registry'] ?? null, (bool) $validated['is_rfid']);
        $grupo = $validated['grupo'] ?? null;
        $purok = Purok::query()->findOrFail($validated['purok_id']);
        if ($grupo !== null && ((int) $grupo < $purok->grupo_start || (int) $grupo > $purok->grupo_end)) {
            throw ValidationException::withMessages([
                'grupo' => ["Grupo must be between {$purok->grupo_start} and {$purok->grupo_end} for {$purok->purok_name}."],
            ]);
        }

        $kapisanan = $this->deriveKapisanan(
            $validated['date_of_birth'] ?? null,
            (int) ($validated['is_married'] ?? 0)
        ) ?? $validated['kapisanan'];
        $isMarried = (int) ($validated['is_married'] ?? 0) === 1
            || in_array(mb_strtolower(trim($kapisanan)), ['b', 'buklod'], true);

        $description = $this->normalizeDescription($validated['description'] ?? null);
        $result = DB::transaction(function () use ($validated, $name, $registry, $purok, $kapisanan, $grupo, $description, $isMarried): array {
            $duplicates = $this->findDuplicates($name, $registry, lockForUpdate: true);
            $reuseId = $validated['reuse_record_id'] ?? null;

            if ($duplicates->isNotEmpty()) {
                $recordWithMonthlyCode = $duplicates->first(
                    fn (Record $duplicate): bool => trim((string) $duplicate->monthly_code) !== ''
                );
                if ($recordWithMonthlyCode) {
                    throw ValidationException::withMessages([
                        'full_name' => ['This person already has a monthly movement code. Clear it in Inquire / Modify before proceeding.'],
                    ]);
                }
                throw ValidationException::withMessages([
                    ($reuseId === null ? 'full_name' : 'reuse_record_id') => [
                        'This person already has a record. Existing records, including inactive records, can be found but cannot be changed or reused in Monthly Add.',
                    ],
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
                'date_of_entry' => now()->timezone('Asia/Manila')->toDateString(),
                'status' => $validated['status'],
                'monthly_code' => $validated['status'].': B',
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'is_married' => $isMarried,
            ]);
            $this->appendLogEntry($record, $record->monthly_code, null, 'd-m-Y');
            if ($description !== '') {
                $this->appendLogEntry($record, $description, null, 'd-m-Y');
            }
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

    private function appendLogEntry(
        Record $record,
        string $action,
        ?string $description = null,
        string $dateFormat = 'd/m/Y'
    ): void
    {
        $existing = (string) $record->log_section;
        $description = $this->normalizeDescription($description);
        $entry = now()->timezone('Asia/Manila')->format($dateFormat).': '.$action
            .($description === '' ? '' : ': '.$description).',';
        $separator = $existing === ''
            ? ''
            : (preg_match('/,\s*$/u', $existing) === 1
                ? (preg_match('/,\s+$/u', $existing) === 1 ? '' : ' ')
                : ', ');
        $record->log_section = $existing.$separator.$entry;
    }

    private function normalizeDescription(?string $description): string
    {
        return trim(preg_replace('/[\r\n]+/', ' ', (string) $description) ?? '');
    }

    private function ensureNoMonthlyCode(Record $record): void
    {
        if (trim((string) $record->monthly_code) !== '') {
            throw ValidationException::withMessages([
                'record_id' => ['This record already has a monthly movement code. Clear the previous code in Inquire / Modify before proceeding.'],
            ]);
        }
    }

    private function deriveKapisanan(?string $birthDate, int $isMarried): ?string
    {
        if ($birthDate === null) {
            return null;
        }
        if ($isMarried === 1) {
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
