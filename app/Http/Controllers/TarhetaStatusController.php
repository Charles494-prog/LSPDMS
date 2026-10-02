<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TarhetaStatusController extends Controller
{
    private const STATUSES = ['MS', 'UWP', 'MK', 'INACTIVE'];

    public function index()
    {
        $records = Record::query()
            ->select(['id', 'full_name', 'status', 'tarheta_status'])
            ->orderBy('full_name')
            ->get()
            ->map(fn (Record $record): array => [
                'id' => $record->id,
                'name' => trim((string) $record->full_name) ?: 'Unnamed record',
                'status' => mb_strtoupper(trim((string) $record->status)),
                'tarheta_status' => mb_strtoupper(trim((string) $record->tarheta_status)),
                'matched' => mb_strtoupper(trim((string) $record->status))
                    === mb_strtoupper(trim((string) $record->tarheta_status)),
            ]);
        $total = $records->count();
        $matched = $records->where('matched', true)->count();

        return view('tarheta-status', [
            'records' => $records,
            'total' => $total,
            'matched' => $matched,
            'percentage' => $total === 0 ? 0 : round(($matched / $total) * 100, 1),
        ]);
    }

    public function update(Request $request, Record $record): JsonResponse
    {
        $validated = $request->validate([
            'tarheta_status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $record = DB::transaction(function () use ($record, $validated): Record {
            $record = Record::query()->lockForUpdate()->findOrFail($record->id);
            $record->tarheta_status = $validated['tarheta_status'];
            $record->save();

            return $record;
        });

        return response()->json([
            'message' => 'Tarheta status saved.',
            'matches' => mb_strtoupper(trim((string) $record->status))
                === mb_strtoupper(trim((string) $record->tarheta_status)),
            'tarheta_status' => mb_strtoupper(trim((string) $record->tarheta_status)),
        ]);
    }
}
