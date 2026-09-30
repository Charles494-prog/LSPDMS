<?php

namespace App\Http\Controllers;

use App\Models\GlobalVariable;
use App\Models\Record;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DelistmentController extends Controller
{
    private const STATUSES = ['MS', 'UWP', 'MK'];

    public function index()
    {
        $records = Record::query()
            ->select([
                'id',
                'full_name',
                'registry',
                'status',
                'r2_10_eligibility',
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

        $members = $records->map(function (Record $record): array {
            $status = mb_strtoupper(trim((string) $record->status));
            $eligible = $status === 'MK' || in_array(
                mb_strtoupper(trim((string) $record->r2_10_eligibility)),
                ['ELIGIBLE', 'NO NEED TO CHECK'],
                true
            );
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

        $globalVariables = GlobalVariable::query()->first();
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
            'requireBaptismDate' => $requireBaptismDate,
        ]);
    }

    public function endorse(Request $request, Record $record): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'one_year_confirmed' => ['sometimes', 'boolean'],
            'assurance' => ['accepted'],
        ]);

        return DB::transaction(function () use ($record, $validated, $request): JsonResponse {
            $record = Record::query()->lockForUpdate()->findOrFail($record->id);
            $status = mb_strtoupper(trim((string) $record->status));
            $eligibility = mb_strtoupper(trim((string) $record->r2_10_eligibility));
            $eligible = $status === 'MK' || in_array($eligibility, ['ELIGIBLE', 'NO NEED TO CHECK'], true);

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
            $existingReason = trim((string) $record->reason_of_delistment);
            $record->r2_10_endorsement = 'ENDORSED';
            $record->reason_of_delistment = $existingReason === ''
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

    public function updateDates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'changes' => ['required', 'array', 'min:1', 'max:500'],
            'changes.*.id' => ['required', 'integer', 'distinct', 'exists:records,id'],
            'changes.*.baptism' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $saved = DB::transaction(function () use ($validated): array {
            $updated = [];
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
                $updated[] = [
                    'id' => $record->id,
                    'baptism' => $record->date_of_baptism?->format('Y-m-d') ?? '',
                ];
            }

            return $updated;
        });

        return response()->json([
            'message' => 'Record dates saved.',
            'data' => $saved,
        ]);
    }
}
