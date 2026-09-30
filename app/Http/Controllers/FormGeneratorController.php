<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormGeneratorController extends Controller
{
    public function index()
    {
        return view('form-generator');
    }

    public function notes(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $term = trim((string) ($validated['q'] ?? ''));

        $records = Record::query()
            ->whereNotNull('log_section')
            ->where('log_section', '!=', '')
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('full_name', 'like', '%'.$term.'%')
                        ->orWhere('registry', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('full_name')
            ->limit(100)
            ->get(['id', 'full_name', 'registry', 'log_section']);

        return response()->json($records->map(fn (Record $record): array => $this->recordPayload($record)));
    }

    public function appendNote(Request $request, Record $record): JsonResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);
        $note = trim($validated['note']);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => 'Enter a note before saving.']);
        }

        $record = DB::transaction(function () use ($record, $note): Record {
            $lockedRecord = Record::query()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
            $existing = trim((string) $lockedRecord->log_section);
            $newNote = now()->timezone('Asia/Manila')->format('d/m/Y').': '.$note;
            $lockedRecord->log_section = $existing === '' ? $newNote : $existing.', '.$newNote;
            $lockedRecord->save();

            return $lockedRecord;
        });

        return response()->json($this->recordPayload($record->refresh()));
    }

    public function deleteNote(Record $record, int $noteIndex): JsonResponse
    {
        $record = DB::transaction(function () use ($record, $noteIndex): Record {
            $lockedRecord = Record::query()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
            $notes = $this->parseNotes((string) $lockedRecord->log_section);

            abort_unless(array_key_exists($noteIndex, $notes), 404);
            array_splice($notes, $noteIndex, 1);
            $lockedRecord->log_section = $notes === [] ? null : implode(', ', $notes);
            $lockedRecord->save();

            return $lockedRecord;
        });

        return response()->json($this->recordPayload($record->refresh()));
    }

    private function recordPayload(Record $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->full_name ?? 'Unnamed record',
            'registry' => $record->registry ?? '',
            'notes' => $this->parseNotes((string) $record->log_section),
        ];
    }

    /**
     * New notes are comma-separated entries beginning with DD/MM/YYYY.
     * Older imported text is preserved as-is until an individual dated entry is added.
     *
     * @return list<string>
     */
    private function parseNotes(string $logSection): array
    {
        $logSection = trim($logSection);
        if ($logSection === '') {
            return [];
        }

        $notes = preg_split('/,\s*(?=\d{2}\/\d{2}\/\d{4}\s*:)/u', $logSection) ?: [];

        return array_values(array_filter(array_map('trim', $notes), fn (string $note): bool => $note !== ''));
    }
}
