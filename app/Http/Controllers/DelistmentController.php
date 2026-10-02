<?php

namespace App\Http\Controllers;

use App\Models\DelistmentHistory;
use App\Models\GlobalVariable;
use App\Models\Record;
use App\Models\User;
use App\Services\R210Eligibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DelistmentController extends Controller
{
    private const STATUSES = ['MS', 'UWP', 'MK'];

    public function index(R210Eligibility $eligibility)
    {
        $globalVariables = GlobalVariable::query()->first();
        $records = Record::query()
            ->select([
                'id',
                'full_name',
                'registry',
                'status',
                'r2_10_endorsement',
                'date_of_baptism',
                'date_of_entry',
            ])
            ->orderBy('full_name')
            ->get()
            ->filter(fn (Record $record): bool => in_array(
                mb_strtoupper(trim((string) $record->status)),
                self::STATUSES,
                true
            ))
            ->values();

        $members = $records->map(function (Record $record) use ($eligibility, $globalVariables): array {
            $status = mb_strtoupper(trim((string) $record->status));
            $eligible = $eligibility->forRecord($record, $globalVariables);
            $endorsed = mb_strtoupper(trim((string) $record->r2_10_endorsement)) === 'ENDORSED';

            return [
                'id' => $record->id,
                'name' => trim((string) $record->full_name) ?: 'Unnamed record',
                'registry' => trim((string) $record->registry),
                'kalagayan' => $status,
                'eligible' => $eligible,
                'endorsed' => $endorsed,
                'reported' => $eligible && $endorsed,
                'entryDate' => $record->date_of_entry?->format('Y-m-d'),
            ];
        })->all();

        $requireBaptismDate = (bool) $globalVariables?->dateofbaptism_requirement_boolean;
        $missingRecords = $records
            ->filter(fn (Record $record): bool => $requireBaptismDate && ! $record->date_of_baptism)
            ->map(fn (Record $record): array => [
                'id' => $record->id,
                'name' => trim((string) $record->full_name) ?: 'Unnamed record',
                'registry' => trim((string) $record->registry) ?: '—',
                'baptism' => $record->date_of_baptism?->format('Y-m-d') ?? '',
            ])
            ->values()
            ->all();

        return view('de-listment', [
            'members' => $members,
            'missingRecords' => $missingRecords,
            'delistedRecords' => DelistmentHistory::query()
                ->with('record:id,full_name,registry')
                ->orderByDesc('delisted_at')
                ->orderByDesc('id')
                ->get(),
            'entryYearsRequirement' => $globalVariables?->entry_years_requirement ?? 1,
        ]);
    }

    public function endorse(Request $request, Record $record, R210Eligibility $eligibility): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'one_year_confirmed' => ['sometimes', 'boolean'],
            'assurance' => ['accepted'],
        ]);

        return DB::transaction(function () use ($record, $validated, $request, $eligibility): JsonResponse {
            $record = Record::query()->lockForUpdate()->findOrFail($record->id);
            $status = mb_strtoupper(trim((string) $record->status));
            $eligible = $eligibility->forRecord($record, GlobalVariable::query()->first());

            if (! in_array($status, self::STATUSES, true) || ! $eligible) {
                return response()->json([
                    'message' => 'Only eligible MS, UWP, or MK records can be endorsed.',
                ], 422);
            }

            if (mb_strtoupper(trim((string) $record->r2_10_endorsement)) === 'ENDORSED') {
                return response()->json([
                    'message' => 'This record is already marked as endorsed.',
                ], 409);
            }

            $oneYearConfirmed = (bool) ($validated['one_year_confirmed'] ?? false);
            $note = preg_replace('/[\r\n]+/', ' ', trim((string) ($validated['note'] ?? ''))) ?? '';
            if ($status === 'MK' && ($note === '' || $oneYearConfirmed)) {
                return response()->json([
                    'message' => 'MK endorsements require a specific brief description.',
                ], 422);
            }
            if (in_array($status, ['MS', 'UWP'], true) && $note === '' && ! $oneYearConfirmed) {
                return response()->json([
                    'message' => 'Enter a brief description or confirm that this member has been MS/UWP for at least one year.',
                ], 422);
            }

            $user = $request->session()->get('login_user', []);
            $author = trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? ''));
            if ($author === '') {
                $author = trim((string) ($user['gmail'] ?? ''));
            }
            if ($author === '') {
                return response()->json([
                    'message' => 'The signed-in account name is unavailable. Please sign in again.',
                ], 422);
            }
            $reason = $oneYearConfirmed ? '1 year MS or UWP' : 'due to '.$note;
            $auditEntry = $author.' ('.Carbon::now()->format('Y-m-d H:i:s').'): '.$reason.'.';
            $existingReason = trim((string) $record->reason_of_r2_10_endorsement);
            $record->r2_10_endorsement = 'ENDORSED';
            $record->reason_of_r2_10_endorsement = $existingReason === ''
                ? $auditEntry
                : $existingReason.PHP_EOL.$auditEntry;
            $record->save();

            return response()->json([
                'message' => 'R2-10 endorsement saved.',
                'data' => [
                    'id' => $record->id,
                    'r2_10_endorsement' => $record->r2_10_endorsement,
                ],
            ]);
        });
    }

    public function delist(Request $request, Record $record): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($request, $record, $validated): JsonResponse {
            $record = Record::query()->lockForUpdate()->findOrFail($record->id);
            if (mb_strtoupper(trim((string) $record->status)) !== 'MK') {
                return response()->json(['message' => 'Only MK records can be delisted from this page.'], 422);
            }
            if (mb_strtoupper(trim((string) $record->r2_10_endorsement)) !== 'ENDORSED') {
                return response()->json(['message' => 'The MK record must be endorsed before it can be delisted.'], 422);
            }

            $sessionUser = $request->session()->get('login_user', []);
            $user = User::query()->find($sessionUser['id'] ?? null);
            if (! $user) {
                return response()->json(['message' => 'The signed-in account is unavailable. Please sign in again.'], 401);
            }
            $author = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            if ($author === '') {
                $author = trim((string) $user->gmail);
            }
            if ($author === '') {
                return response()->json(['message' => 'The signed-in account name is unavailable. Please sign in again.'], 422);
            }

            $now = Carbon::now('Asia/Manila');
            $reason = trim(preg_replace('/[\r\n]+/', ' ', $validated['reason']) ?? '');
            DelistmentHistory::query()->create([
                'record_id' => $record->id,
                'delisted_by_user_id' => $user->id,
                'delisted_by' => $author,
                'reason' => $reason,
                'delisted_at' => $now,
            ]);

            $record->status = 'INACTIVE';
            $this->appendDelistmentLog($record, $now, $reason);
            $record->save();

            return response()->json([
                'message' => 'MK record delisted and marked INACTIVE.',
                'data' => [
                    'id' => $record->id,
                    'status' => $record->status,
                    'delisted_at' => $now->toISOString(),
                ],
            ]);
        });
    }

    public function updateDates(Request $request, R210Eligibility $eligibility): JsonResponse
    {
        $validated = $request->validate([
            'changes' => ['required', 'array', 'min:1', 'max:500'],
            'changes.*.id' => ['required', 'integer', 'distinct', 'exists:records,id'],
            'changes.*.baptism' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $saved = DB::transaction(function () use ($validated, $eligibility): array {
            $updated = [];
            $globalVariables = GlobalVariable::query()->first();
            foreach ($validated['changes'] as $change) {
                $record = Record::query()->lockForUpdate()->findOrFail($change['id']);
                $status = mb_strtoupper(trim((string) $record->status));
                if (! in_array($status, self::STATUSES, true)) {
                    throw ValidationException::withMessages([
                        'changes' => 'Dates can only be updated for MS, UWP, or MK records.',
                    ]);
                }

                if (array_key_exists('baptism', $change)) {
                    $record->date_of_baptism = $change['baptism'] ?: null;
                }
                $record->save();
                $eligible = $eligibility->forRecord($record, $globalVariables);
                $updated[] = [
                    'id' => $record->id,
                    'baptism' => $record->date_of_baptism?->format('Y-m-d') ?? '',
                    'eligible' => $eligible,
                    'reported' => $eligible
                        && mb_strtoupper(trim((string) $record->r2_10_endorsement)) === 'ENDORSED',
                ];
            }

            return $updated;
        });

        return response()->json([
            'message' => 'Record dates saved.',
            'data' => $saved,
        ]);
    }

    private function appendDelistmentLog(Record $record, Carbon $date, string $reason): void
    {
        $existing = (string) $record->log_section;
        $entry = $date->format('d/m/Y').': Delisted on '.$date->format('F d, Y').': '.$reason.',';
        $separator = $existing === ''
            ? ''
            : (preg_match('/,\s*$/u', $existing) === 1
                ? (preg_match('/,\s+$/u', $existing) === 1 ? '' : ' ')
                : ', ');
        $record->log_section = $existing.$separator.$entry;
    }
}
