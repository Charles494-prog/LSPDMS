<?php

namespace App\Http\Controllers;

use App\Models\MonthlyMovementSession;
use App\Models\MonthlyRecordSnapshot;
use App\Models\MonthlySessionMovement;
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
    private const MOVEMENT_STATE_FIELDS = [
        'status',
        'purok',
        'grupo',
        'monthly_code',
        'date_of_entry',
        'reason_of_r2_10_endorsement',
        'log_section',
    ];

    public function startSession(Request $request): JsonResponse
    {
        $session = DB::transaction(fn (): MonthlyMovementSession => $this->activeMonthlySession($request, true));

        return response()->json(['session' => $this->sessionPayload($session)]);
    }

    public function sessionStatus(Request $request): JsonResponse
    {
        $session = MonthlyMovementSession::query()->where('active_key', 'active')->first();
        $response = [
            'session' => $session ? $this->sessionPayload($session) : null,
            'start_availability' => $session ? null : $this->sessionStartAvailability(),
        ];
        if ($session && $request->boolean('include_movements')) {
            $response['movements'] = $session->movements()
                ->orderByDesc('changed_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (MonthlySessionMovement $movement): array => [
                    'id' => $movement->id,
                    'name' => $movement->full_name,
                    'registry' => $movement->registry,
                    'gender' => $movement->gender,
                    'kapisanan' => $movement->kapisanan,
                    'date_of_baptism' => $movement->date_of_baptism?->format('d/m/Y'),
                    'evangelist' => $movement->evangelist,
                    'action' => ucfirst($movement->action),
                    'destination_description' => $movement->destination_description,
                    'monthly_code' => $movement->monthly_code,
                    'description' => $movement->description,
                    'changed_by' => $movement->changed_by,
                    'changed_at' => $movement->changed_at?->timezone('Asia/Manila')->format('d/m/Y H:i:s'),
                    'undo_url' => route('forms.monthly.session.undo', $movement),
                ])
                ->values();
        }

        return response()->json($response);
    }

    public function sessionMonitor()
    {
        $session = MonthlyMovementSession::query()
            ->where('active_key', 'active')
            ->with(['movements' => fn ($query) => $query->orderByDesc('changed_at')->orderByDesc('id')])
            ->first();

        return view('forms.monthly-session', compact('session'));
    }

    public function undoMovement(Request $request, MonthlySessionMovement $movement): JsonResponse
    {
        DB::transaction(function () use ($movement): void {
            $session = MonthlyMovementSession::query()
                ->whereKey($movement->session_id)
                ->where('active_key', 'active')
                ->lockForUpdate()
                ->firstOrFail();
            $movement = MonthlySessionMovement::query()->whereKey($movement->id)->lockForUpdate()->firstOrFail();
            if ($movement->record_id === null) {
                throw ValidationException::withMessages(['movement' => ['This movement no longer has a record to restore.']]);
            }
            if (MonthlySessionMovement::query()
                ->where('session_id', $session->id)
                ->where('record_id', $movement->record_id)
                ->where('id', '>', $movement->id)
                ->exists()) {
                throw ValidationException::withMessages(['movement' => ['Undo later movements for this member first.']]);
            }

            $record = Record::query()->lockForUpdate()->findOrFail($movement->record_id);
            if ($this->movementState($record) != $movement->next_values) {
                throw ValidationException::withMessages([
                    'movement' => ['This member changed after the logged movement. Review the record before undoing it.'],
                ]);
            }
            if ($movement->action === 'add') {
                $record->status = 'INACTIVE';
                $record->date_of_entry = null;
                $record->monthly_code = null;
                foreach (['reason_of_r2_10_endorsement', 'log_section'] as $field) {
                    $record->{$field} = $movement->previous_values[$field] ?? null;
                }
            } else {
                $record->forceFill($movement->previous_values);
            }
            $record->save();
            $movement->delete();
        });

        return response()->json(['message' => 'Movement undone and removed from the temporary session log.']);
    }

    public function finishSession(Request $request): JsonResponse
    {
        $result = DB::transaction(function () use ($request): array {
            $session = MonthlyMovementSession::query()
                ->where('active_key', 'active')
                ->lockForUpdate()
                ->firstOrFail();
            $movements = MonthlySessionMovement::query()
                ->where('session_id', $session->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $count = $movements->count();
            $actor = $this->changedBy($request);

            foreach ($movements as $movement) {
                DB::table('permanent_history')->insert([
                    'session_id' => $session->id,
                    'period_month' => $session->period_month?->toDateString(),
                    'record_id' => $movement->record_id,
                    'action' => $movement->action,
                    'full_name' => $movement->full_name,
                    'registry' => $movement->registry,
                    'gender' => $movement->gender,
                    'kapisanan' => $movement->kapisanan,
                    'status' => $movement->status,
                    'monthly_code' => $movement->monthly_code,
                    'destination_description' => $movement->destination_description,
                    'description' => $movement->description,
                    'changed_by' => $movement->changed_by ?: $actor,
                    'changed_at' => $movement->changed_at,
                    'date_of_baptism' => $movement->date_of_baptism?->format('Y-m-d'),
                    'evangelist' => $movement->evangelist,
                    'previous_values' => json_encode($movement->previous_values, JSON_THROW_ON_ERROR),
                    'next_values' => json_encode($movement->next_values, JSON_THROW_ON_ERROR),
                ]);
            }

            $this->snapshotSession($session, $movements);
            MonthlySessionMovement::query()->where('session_id', $session->id)->delete();
            $clearedMonthlyCodes = Record::query()->whereNotNull('monthly_code')->count();
            Record::query()->whereNotNull('monthly_code')->update(['monthly_code' => null]);
            DB::table('system_maintenance_audits')->insert([
                'action' => 'finish_monthly_clear_codes',
                'affected_count' => $clearedMonthlyCodes,
                'changed_by' => $actor,
                'changed_at' => now(),
            ]);
            $session->forceFill([
                'status' => 'completed',
                'active_key' => null,
                'completed_by' => $actor,
                'completed_at' => now(),
            ])->save();

            return [
                'movements' => $count,
                'cleared_monthly_codes' => $clearedMonthlyCodes,
            ];
        });
        $count = $result['movements'];
        $clearedMonthlyCodes = $result['cleared_monthly_codes'];

        return response()->json([
            'message' => "Monthly session finalized. {$count} movement".($count === 1 ? '' : 's')
                ." copied to permanent history; {$clearedMonthlyCodes} monthly code".($clearedMonthlyCodes === 1 ? '' : 's').' cleared.',
            'movements' => $count,
            'cleared_monthly_codes' => $clearedMonthlyCodes,
            'cleared_monthly_codes' => $clearedMonthlyCodes,
        ]);
    }

    public function index()
    {
        return view('forms.deliberation', [
            'monthlySession' => MonthlyMovementSession::query()->where('active_key', 'active')->first(),
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
            'monthlySession' => MonthlyMovementSession::query()->where('active_key', 'active')->first(),
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
            $session = $this->requireActiveMonthlySession($request);
            $previousValues = $this->movementState($record);
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
                if ($newStatus === 'MK') {
                    $datedReason = now()->timezone('Asia/Manila')->format('d-m-Y').': ('.$endorsementReason.')';
                    $existingReason = trim((string) $record->reason_of_r2_10_endorsement);
                    $record->reason_of_r2_10_endorsement = $existingReason === ''
                        ? $datedReason
                        : $existingReason.PHP_EOL.$datedReason;
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
                $this->storeSessionMovement(
                    $session,
                    $record,
                    'status',
                    $previousValues,
                    "{$oldStatus} -> {$newStatus}",
                    $validated['description'] ?? $endorsementReason,
                    $request,
                    $oldStatus.': '.$historyCode
                );

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
            $record->purok = (string) $destination->purok_number;
            $record->grupo = (string) $validated['grupo'];
            $record->monthly_code = $oldStatus.': C+';
            $this->appendLogEntry(
                $record,
                "Monthly modify: {$oldPurokName} Grupo {$oldGrupo} to {$destination->purok_name} Grupo {$validated['grupo']} ({$oldStatus}: C- / {$oldStatus}: C+)",
                $validated['description'] ?? null
            );
            $record->save();
            $this->storeSessionMovement(
                $session,
                $record,
                'transfer',
                $previousValues,
                "{$oldPurok}-{$oldGrupo} -> {$destination->purok_number}-{$validated['grupo']}",
                $validated['description'] ?? null,
                $request,
                $oldStatus.': C- / '.$oldStatus.': C+'
            );

            return $record;
        });

        return response()->json([
            'message' => $validated['action'] === 'status'
                ? ($record->monthly_code
                    ? "Status updated. Current monthly code: {$record->monthly_code}; movement is in the active session."
                    : 'Status updated to MK. Movement is in the active session.')
                : "Purok and Grupo updated. Current monthly code: {$record->monthly_code}; movement is in the active session.",
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

    public function addSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $search = mb_strtolower(trim($validated['q']));
        $query = Record::query()
            ->where(function (Builder $query) use ($search): void {
                $query->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%']);
                if (preg_match('/^[a-z]+$/', $search) && strlen($search) >= 3) {
                    $query->orWhereRaw('LOWER(full_name) LIKE ?', [substr($search, 0, 1).'%']);
                }
            })
            ->orderByRaw('CASE WHEN LOWER(full_name) LIKE ? THEN 0 ELSE 1 END', ['%'.$search.'%'])
            ->orderBy('full_name');
        $candidates = $query->limit(500)->get([
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
            'is_married',
            'status',
            'monthly_code',
        ]);
        $records = $candidates
            ->map(function (Record $record) use ($search): array {
                $name = mb_strtolower((string) $record->full_name);
                $exact = str_contains($name, $search);
                $distanceLimit = min(2, max(1, (int) floor(strlen($search) * 0.2)));
                $distance = $exact ? 0 : min(array_map(function (string $word) use ($search, $distanceLimit): int {
                    $wordLengths = range(
                        max(1, min(strlen($word), strlen($search) - $distanceLimit)),
                        min(strlen($word), strlen($search) + $distanceLimit)
                    );
                    $distances = array_map(
                        fn (int $length): int => levenshtein($search, substr($word, 0, $length)),
                        $wordLengths
                    );
                    $distances[] = levenshtein($search, $word);

                    return min($distances);
                }, preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [$name]));

                return [
                    'record' => $record,
                    'distance' => $distance,
                    'matches' => $exact || (
                        preg_match('/^[a-z]+$/', $search)
                        && strlen($search) >= 3
                        && $distance <= $distanceLimit
                    ),
                ];
            })
            ->filter(fn (array $candidate): bool => $candidate['matches'])
            ->sort(function (array $left, array $right): int {
                $distance = $left['distance'] <=> $right['distance'];
                if ($distance !== 0) {
                    return $distance;
                }
                $inactive = (int) (mb_strtoupper(trim((string) $right['record']->status)) === 'INACTIVE')
                    <=> (int) (mb_strtoupper(trim((string) $left['record']->status)) === 'INACTIVE');

                return $inactive ?: strcasecmp((string) $left['record']->full_name, (string) $right['record']->full_name);
            })
            ->take(10)
            ->pluck('record')
            ->values();
        $puroks = Purok::query()
            ->whereIn('purok_number', $records->pluck('purok')->filter())
            ->orderBy('grupo_start')
            ->get(['id', 'purok_number', 'purok_name', 'grupo_start', 'grupo_end', 'full_purok'])
            ->groupBy(fn (Purok $purok): string => (string) $purok->purok_number);

        return response()->json([
            'records' => $records->map(function (Record $record) use ($puroks): array {
                $grupo = (int) $record->grupo;
                $purok = $puroks->get(trim((string) $record->purok), collect())
                    ->first(fn (Purok $candidate): bool => $candidate->full_purok
                        || ($grupo >= (int) $candidate->grupo_start && $grupo <= (int) $candidate->grupo_end));

                return [
                    'id' => $record->id,
                    'full_name' => $record->full_name,
                    'registry' => $record->registry,
                    'gender' => match (mb_strtoupper(trim((string) $record->gender))) {
                        'L', 'MALE' => 'Male',
                        'B', 'FEMALE' => 'Female',
                        default => $record->gender,
                    },
                    'purok_id' => $purok?->id,
                    'purok' => trim((string) $record->purok),
                    'grupo' => trim((string) $record->grupo),
                    'kapisanan' => match (mb_strtolower(trim((string) $record->kapisanan))) {
                        'b', 'buklod' => 'Buklod',
                        'k', 'kadiwa' => 'Kadiwa',
                        's', 'suliranin' => 'Suliranin',
                        'binhi' => 'Binhi',
                        default => $record->kapisanan,
                    },
                    'date_of_birth' => $record->date_of_birth?->format('Y-m-d'),
                    'date_of_baptism' => $record->date_of_baptism?->format('Y-m-d'),
                    'evangelist' => $record->evangelist,
                    'is_married' => (bool) $record->is_married,
                    'status' => mb_strtoupper(trim((string) $record->status)),
                    'monthly_code' => trim((string) $record->monthly_code),
                ];
            })->values(),
        ]);
    }

    public function checkRegistry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registry' => ['required', 'string', 'size:13', 'regex:/^[A-Za-z0-9]{13}$/'],
            'is_rfid' => ['required', 'boolean'],
            'exclude_record_id' => ['nullable', 'integer', 'exists:records,id'],
        ]);

        if ($validated['is_rfid']) {
            return response()->json(['duplicates' => []]);
        }

        $registry = strtoupper(trim($validated['registry']));
        $duplicates = Record::query()
            ->whereRaw('UPPER(TRIM(registry)) = ?', [$registry])
            ->whereRaw("UPPER(TRIM(registry)) <> 'RFID'")
            ->when(
                isset($validated['exclude_record_id']),
                fn (Builder $query) => $query->where('id', '<>', $validated['exclude_record_id'])
            )
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name', 'registry', 'status', 'purok', 'grupo']);

        return response()->json([
            'duplicates' => $duplicates->map(fn (Record $record): array => [
                'id' => $record->id,
                'full_name' => $record->full_name,
                'registry' => $record->registry,
                'status' => mb_strtoupper(trim((string) $record->status)),
                'purok' => trim((string) $record->purok),
                'grupo' => trim((string) $record->grupo),
            ])->values(),
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

        return DB::transaction(function () use ($validated, $request): JsonResponse {
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

            $session = $this->requireActiveMonthlySession($request);
            $previousValues = $this->movementState($record);
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
            $this->storeSessionMovement(
                $session,
                $record,
                'subtract',
                $previousValues,
                "{$status} -> INACTIVE ({$validated['code']})",
                $description,
                $request,
                $status.': '.$validated['code']
            );

            return response()->json([
                'message' => 'Record marked INACTIVE for this monthly session; it remains in the database.',
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
            'is_rfid' => ['required_without:reuse_record_id', 'nullable', 'boolean'],
            'gender' => ['required_without:reuse_record_id', 'nullable', Rule::in(['Male', 'Female'])],
            'purok_id' => ['required_without:reuse_record_id', 'nullable', 'integer', 'exists:puroks,id'],
            'grupo' => ['nullable', 'integer', 'min:1', 'max:999'],
            'kapisanan' => ['required_without:reuse_record_id', 'nullable', Rule::in(['Buklod', 'Kadiwa', 'Suliranin', 'Binhi'])],
            'date_of_baptism' => ['nullable', 'date', 'before_or_equal:today'],
            'evangelist' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(['MS', 'UWP', 'MK'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'is_married' => ['sometimes', 'required', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'reuse_record_id' => ['nullable', 'integer', 'exists:records,id'],
        ]);

        $name = mb_strtoupper(trim($validated['full_name']));
        $registry = isset($validated['reuse_record_id'])
            ? mb_strtoupper(trim((string) ($validated['registry'] ?? '')))
            : mb_strtoupper($this->normalizeRegistry($validated['registry'] ?? null, (bool) ($validated['is_rfid'] ?? false)));
        $grupo = $validated['grupo'] ?? null;
        $purok = isset($validated['purok_id']) ? Purok::query()->findOrFail($validated['purok_id']) : null;
        if ($purok && $grupo !== null && ((int) $grupo < $purok->grupo_start || (int) $grupo > $purok->grupo_end)) {
            throw ValidationException::withMessages([
                'grupo' => ["Grupo must be between {$purok->grupo_start} and {$purok->grupo_end} for {$purok->purok_name}."],
            ]);
        }

        $kapisanan = $this->deriveKapisanan(
            $validated['date_of_birth'] ?? null,
            (int) ($validated['is_married'] ?? 0)
        ) ?? ($validated['kapisanan'] ?? '');
        $isMarried = (int) ($validated['is_married'] ?? 0) === 1
            || in_array(mb_strtolower(trim($kapisanan)), ['b', 'buklod'], true);

        $description = $this->normalizeDescription($validated['description'] ?? null);
        $result = DB::transaction(function () use ($validated, $name, $registry, $purok, $kapisanan, $grupo, $description, $isMarried, $request): array {
            $duplicates = $this->findDuplicates($name, $registry, lockForUpdate: true);
            $reuseId = $validated['reuse_record_id'] ?? null;

            if ($reuseId !== null) {
                $record = Record::query()->lockForUpdate()->findOrFail($reuseId);
                if (! $duplicates->contains('id', $record->id)
                    || mb_strtoupper(trim((string) $record->status)) !== 'INACTIVE'
                    || trim((string) $record->monthly_code) !== '') {
                    throw ValidationException::withMessages([
                        'reuse_record_id' => ['Only a matching inactive record without a pending monthly code can be reactivated from Monthly Add.'],
                    ]);
                }

                $session = $this->requireActiveMonthlySession($request);
                $previousValues = $this->movementState($record);
                $record->full_name = mb_strtoupper(trim((string) $record->full_name));
                $record->registry = mb_strtoupper(trim((string) $record->registry));
                $record->status = $validated['status'];
                $record->monthly_code = $validated['status'].': B';
                $this->appendLogEntry($record, $record->monthly_code, null, 'd-m-Y');
                if ($description !== '') {
                    $this->appendLogEntry($record, $description, null, 'd-m-Y');
                }
                $record->save();
                $this->storeSessionMovement(
                    $session,
                    $record,
                    'reactivate',
                    $previousValues,
                    "INACTIVE -> {$record->status}",
                    $description,
                    $request,
                    $record->monthly_code
                );

                return [
                    'record' => $record,
                    'reused' => true,
                ];
            }

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
                    'full_name' => ['This person already has a record. Select an inactive match to reactivate it; active records cannot be added again.'],
                ]);
            }

            if (! $purok) {
                throw ValidationException::withMessages([
                    'purok_id' => ['Select a Purok before adding a new record.'],
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
            $session = $this->requireActiveMonthlySession($request);
            $previousValues = array_fill_keys(self::MOVEMENT_STATE_FIELDS, null);
            $this->appendLogEntry($record, $record->monthly_code, null, 'd-m-Y');
            if ($description !== '') {
                $this->appendLogEntry($record, $description, null, 'd-m-Y');
            }
            $record->save();
            $this->storeSessionMovement(
                $session,
                $record,
                'add',
                $previousValues,
                "Added -> {$record->status}",
                $description,
                $request,
                $record->monthly_code
            );

            return [
                'record' => $record,
                'reused' => false,
            ];
        });

        return response()->json([
            'message' => $result['reused']
                ? "Existing inactive record reactivated to {$result['record']->status}; the movement is recorded in the active session."
                : 'Deliberation record added to the active monthly session.',
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
            'monthly_code' => trim((string) $record->monthly_code),
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
    ): void {
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

    private function activeMonthlySession(Request $request, bool $create): ?MonthlyMovementSession
    {
        $session = MonthlyMovementSession::query()
            ->where('active_key', 'active')
            ->lockForUpdate()
            ->first();
        if ($session || ! $create) {
            return $session;
        }

        $now = Carbon::now('Asia/Manila');
        $periodMonth = $now->copy()->startOfMonth();
        if ($now->day >= 15) {
            $periodMonth->addMonthNoOverflow();
        }
        if ($this->monthlySessionAlreadyStartedThisMonth($now)) {
            throw ValidationException::withMessages([
                'session' => ['A monthly session has already been started this calendar month. Monthly sessions are limited to once per month.'],
            ]);
        }
        if (MonthlyMovementSession::query()->whereDate('period_month', $periodMonth)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'session' => ["A monthly session for {$periodMonth->format('F Y')} already exists. Monthly sessions are limited to once per month."],
            ]);
        }

        return MonthlyMovementSession::query()->create([
            'active_key' => 'active',
            'status' => 'active',
            'period_month' => $periodMonth->toDateString(),
            'started_by' => $this->changedBy($request),
            'started_at' => $now,
        ]);
    }

    private function sessionStartAvailability(): array
    {
        $now = Carbon::now('Asia/Manila');
        $targetMonth = $now->copy()->startOfMonth();
        if ($now->day >= 15) {
            $targetMonth->addMonthNoOverflow();
        }
        $existingPeriod = $this->monthlySessionAlreadyStartedThisMonth($now)
            || MonthlyMovementSession::query()->whereDate('period_month', $targetMonth)->exists();

        return [
            'allowed' => ! $existingPeriod,
            'target_month' => ! $existingPeriod ? $targetMonth->format('F Y') : null,
            'message' => $existingPeriod
                ? "A monthly session for {$targetMonth->format('F Y')} already exists. Sessions are available once per month."
                : "Monthly Session available right now for {$targetMonth->format('F Y')}!",
        ];
    }

    private function monthlySessionAlreadyStartedThisMonth(Carbon $date): bool
    {
        return MonthlyMovementSession::query()
            ->whereBetween('started_at', [
                $date->copy()->startOfMonth()->utc(),
                $date->copy()->endOfMonth()->utc(),
            ])
            ->exists();
    }

    private function requireActiveMonthlySession(Request $request): MonthlyMovementSession
    {
        $session = $this->activeMonthlySession($request, false);
        if (! $session) {
            throw ValidationException::withMessages([
                'session' => ['Start a monthly session before adding, subtracting, or modifying records.'],
            ]);
        }

        return $session;
    }

    private function changedBy(Request $request): ?string
    {
        return trim(implode(' ', array_filter([
            (string) $request->session()->get('login_user.first_name', ''),
            (string) $request->session()->get('login_user.last_name', ''),
        ]))) ?: $request->session()->get('login_user.gmail');
    }

    private function movementState(Record $record): array
    {
        return collect(self::MOVEMENT_STATE_FIELDS)
            ->mapWithKeys(fn (string $field): array => [
                $field => $record->{$field} instanceof \DateTimeInterface
                    ? $record->{$field}->format('Y-m-d')
                    : $record->{$field},
            ])
            ->all();
    }

    private function storeSessionMovement(
        MonthlyMovementSession $session,
        Record $record,
        string $action,
        array $previousValues,
        string $destinationDescription,
        ?string $description,
        Request $request,
        ?string $monthlyCode
    ): void {
        MonthlySessionMovement::query()->create([
            'session_id' => $session->id,
            'record_id' => $record->id,
            'action' => $action,
            'full_name' => trim((string) $record->full_name) ?: 'Unnamed record',
            'registry' => trim((string) $record->registry) ?: null,
            'gender' => trim((string) $record->gender) ?: null,
            'kapisanan' => trim((string) $record->kapisanan) ?: null,
            'date_of_baptism' => $record->date_of_baptism?->format('Y-m-d'),
            'evangelist' => trim((string) $record->evangelist) ?: null,
            'status' => $previousValues['status'] ?? null,
            'monthly_code' => $monthlyCode,
            'previous_purok' => $previousValues['purok'] ?? null,
            'previous_grupo' => $previousValues['grupo'] ?? null,
            'destination_description' => $destinationDescription,
            'previous_values' => $previousValues,
            'next_values' => $this->movementState($record),
            'description' => $this->normalizeDescription($description) ?: null,
            'changed_by' => $this->changedBy($request),
            'changed_at' => now(),
        ]);
    }

    private function sessionPayload(MonthlyMovementSession $session): array
    {
        return [
            'id' => $session->id,
            'started_by' => $session->started_by,
            'started_at' => $session->started_at?->toIso8601String(),
            'period_month' => $session->period_month?->format('F Y'),
            'movement_count' => $session->movements()->count(),
        ];
    }

    private function snapshotSession(MonthlyMovementSession $session, $movements): void
    {
        $month = $session->period_month->startOfMonth()->toDateString();
        $purokNumbers = Purok::query()->pluck('purok_number')
            ->merge(Record::query()->pluck('purok')->filter()->map(fn ($number): int => (int) preg_replace('/\D+/', '', (string) $number)))
            ->merge(collect($movements)->flatMap(fn (MonthlySessionMovement $movement): array => [
                $movement->previous_values['purok'] ?? null,
                $movement->next_values['purok'] ?? null,
            ])->filter()->map(fn ($number): int => (int) preg_replace('/\D+/', '', (string) $number)))
            ->filter(fn ($number): bool => (int) $number > 0)
            ->unique()
            ->sort()
            ->values();
        $movementCountsByPurok = [];

        $increment = function (?string $purok, ?string $status, string $code) use (&$movementCountsByPurok): void {
            $number = (int) preg_replace('/\D+/', '', (string) $purok);
            $status = mb_strtoupper(trim((string) $status));
            if ($number < 1 || $status === '') {
                return;
            }
            $movementCountsByPurok[$number][$status][$code] =
                ($movementCountsByPurok[$number][$status][$code] ?? 0) + 1;
        };

        foreach ($movements as $movement) {
            $previous = $movement->previous_values ?? [];
            $next = $movement->next_values ?? [];
            $code = strtoupper(trim(explode(':', (string) $movement->monthly_code, 2)[1] ?? ''));
            if ($movement->action === 'add') {
                $increment($next['purok'] ?? null, $next['status'] ?? null, 'B');
            } elseif ($movement->action === 'subtract') {
                $increment($previous['purok'] ?? null, $previous['status'] ?? null, $code);
            } elseif ($movement->action === 'transfer') {
                $increment($previous['purok'] ?? null, $previous['status'] ?? null, 'C-');
                $increment($next['purok'] ?? null, $previous['status'] ?? null, 'C+');
            } elseif ($movement->action === 'status') {
                $oldStatus = mb_strtoupper(trim((string) ($previous['status'] ?? '')));
                $newStatus = mb_strtoupper(trim((string) ($next['status'] ?? '')));
                $increment($previous['purok'] ?? null, $oldStatus, $newStatus === 'MK' ? 'E' : 'G');
                if ($newStatus !== 'MK') {
                    $increment($next['purok'] ?? null, $newStatus, 'B');
                }
            }
        }

        foreach ($purokNumbers as $purokNumber) {
            $members = Record::query()
                ->where('purok', (string) $purokNumber)
                ->get(['status']);
            $counts = $members->countBy(fn (Record $record): string => mb_strtoupper(trim((string) $record->status)));
            $snapshot = MonthlyRecordSnapshot::query()
                ->whereDate('month', $month)
                ->where('purok_number', $purokNumber)
                ->first();
            if ($snapshot && ! $snapshot->session_generated) {
                continue;
            }
            $movementCounts = $snapshot?->movement_counts ?? [];
            foreach ($movementCountsByPurok[$purokNumber] ?? [] as $status => $codes) {
                foreach ($codes as $code => $quantity) {
                    $movementCounts[$status][$code] = ($movementCounts[$status][$code] ?? 0) + $quantity;
                }
            }
            DB::table('monthly_record_snapshots')->updateOrInsert(
                ['month' => $month, 'purok_number' => (int) $purokNumber],
                [
                    'ms_count' => $counts->get('MS', 0),
                    'uwp_count' => $counts->get('UWP', 0),
                    'mk_count' => $counts->get('MK', 0),
                    'movement_counts' => json_encode($movementCounts, JSON_THROW_ON_ERROR),
                    'created_at' => $snapshot?->created_at ?? now(),
                    'updated_at' => now(),
                    'session_generated' => true,
                ]
            );
        }
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
