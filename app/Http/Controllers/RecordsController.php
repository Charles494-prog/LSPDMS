<?php

namespace App\Http\Controllers;

use App\Models\Purok;
use App\Models\Record;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordsController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:255'],
        ]);

        $query = trim($validated['q']);

        if ($query === '') {
            return response()->json(['message' => 'Enter a name or registry number.'], 422);
        }

        $nameParts = preg_split('/\s+/', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY);

        $records = Record::query()
            ->where(function ($records) use ($query, $nameParts) {
                $records->where(function ($nameQuery) use ($nameParts) {
                    foreach ($nameParts as $part) {
                        $nameQuery->whereRaw('LOWER(full_name) LIKE ?', ['%'.$part.'%']);
                    }
                })->orWhereRaw('LOWER(registry) LIKE ?', ['%'.mb_strtolower($query).'%']);
            })
            ->orderBy('full_name')
            ->orderBy('registry')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $records->map(fn (Record $record): array => $this->present($record)),
        ]);
    }

    public function smartHelper(): JsonResponse
    {
        $records = Record::query()
            ->select(['id', 'full_name', 'registry', 'status', 'kapisanan', 'purok', 'grupo'])
            ->orderBy('full_name')
            ->orderBy('registry')
            ->get();

        $findings = [];
        $registryGroups = [];
        $nameGroups = [];
        $namedRecords = [];

        foreach ($records as $record) {
            $registry = (string) ($record->registry ?? '');
            if (trim($registry) !== '' && mb_strtoupper(trim($registry)) !== 'RFID') {
                $registryGroups['registry:'.$registry][] = $record;
            }

            $name = $this->nameParts($record->full_name ?? '');
            if ($name['letters'] !== '') {
                $nameGroups['name:'.$name['letters'].':'.$name['suffix']][] = $record;
                $namedRecords[] = ['record' => $record, ...$name];
            }
        }

        foreach ($registryGroups as $registryRecords) {
            if (count($registryRecords) < 2) {
                continue;
            }

            $findings[] = [
                'type' => 'duplicate_registry',
                'field' => 'registry',
                'match_value' => $registryRecords[0]->registry,
                'records' => $this->presentHelperRecords($registryRecords),
            ];
        }

        foreach ($nameGroups as $nameRecords) {
            if (count($nameRecords) < 2) {
                continue;
            }

            $findings[] = [
                'type' => 'duplicate_name',
                'field' => 'full_name',
                'match_value' => $nameRecords[0]->full_name,
                'similarity' => 100,
                'records' => $this->presentHelperRecords($nameRecords),
            ];
        }

        $namedCount = count($namedRecords);
        for ($left = 0; $left < $namedCount; $left++) {
            for ($right = $left + 1; $right < $namedCount; $right++) {
                $leftName = $namedRecords[$left]['letters'];
                $rightName = $namedRecords[$right]['letters'];
                $leftSuffix = $namedRecords[$left]['suffix'];
                $rightSuffix = $namedRecords[$right]['suffix'];

                if ($leftSuffix !== '' && $rightSuffix !== '' && $leftSuffix !== $rightSuffix) {
                    continue;
                }

                if ($leftName === $rightName) {
                    continue;
                }

                $similarity = $this->nameSimilarity($leftName, $rightName);
                if ($similarity < 90) {
                    continue;
                }

                $findings[] = [
                    'type' => 'similar_name',
                    'field' => 'full_name',
                    'similarity' => $similarity,
                    'records' => $this->presentHelperRecords([
                        $namedRecords[$left]['record'],
                        $namedRecords[$right]['record'],
                    ]),
                ];
            }
        }

        return response()->json([
            'data' => $findings,
            'records_scanned' => $records->count(),
        ]);
    }

    public function update(Request $request, Record $record): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'registry_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('records', 'registry')->ignore($record->getKey()),
            ],
            'gender' => ['sometimes', 'required', 'string', Rule::in(['Male', 'Female'])],
            'kapisanan' => ['sometimes', 'nullable', 'string', 'max:50'],
            'grupo' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:24'],
            'purok' => ['prohibited'],
            'date_of_baptism' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'evangelist' => ['sometimes', 'nullable', 'string', 'max:150'],
        ]);

        if (isset($validated['grupo']) && $validated['grupo'] !== '') {
            preg_match('/\d+/', trim((string) $record->purok), $purokNumber);
            $purokSetting = isset($purokNumber[0])
                ? Purok::query()->where('purok_number', $purokNumber[0])->first()
                : null;
            $minimum = $purokSetting?->grupo_start ?? 1;
            $maximum = $purokSetting?->grupo_end ?? 24;
            if ((int) $validated['grupo'] < $minimum || (int) $validated['grupo'] > $maximum) {
                throw ValidationException::withMessages([
                    'grupo' => ["Grupo must stay between {$minimum} and {$maximum} for this Purok."],
                ]);
            }
        }

        $attributes = [];
        $fieldMap = [
            'name' => 'full_name',
            'registry_number' => 'registry',
            'gender' => 'gender',
            'kapisanan' => 'kapisanan',
            'grupo' => 'grupo',
            'date_of_baptism' => 'date_of_baptism',
            'date_of_birth' => 'date_of_birth',
            'evangelist' => 'evangelist',
        ];

        foreach ($fieldMap as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $attributes[$column] = match ($input) {
                    'kapisanan' => $this->storeKapisanan($validated[$input]),
                    'gender' => $this->storeGender($validated[$input]),
                    default => $validated[$input],
                };
            }
        }

        $record->fill($attributes)->save();

        return response()->json([
            'data' => $this->present($record->refresh()),
        ]);
    }

    public function destroy(Record $record): JsonResponse
    {
        if (mb_strtolower(trim((string) $record->status)) !== 'inactive') {
            return response()->json([
                'message' => 'You can only delete this record after changing its status to Inactive in Form Generator > Monthly > Deliberation.',
            ], 422);
        }

        $record->delete();

        return response()->json(['message' => 'Inactive record deleted.']);
    }

    private function present(Record $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->full_name ?? '',
            'registry_number' => $record->registry ?? '',
            'gender' => $this->displayGender($record->gender),
            'kapisanan' => $this->displayKapisanan($record->kapisanan),
            'purok' => $record->purok ?? '',
            'grupo' => $record->grupo ?? '',
            'date_of_baptism' => $record->date_of_baptism?->format('Y-m-d') ?? '',
            'date_of_birth' => $record->date_of_birth?->format('Y-m-d') ?? '',
            'evangelist' => $record->evangelist ?? '',
            'date_of_entry' => $record->date_of_entry?->format('Y-m-d') ?? '',
            'r2_10_eligibility' => $record->r2_10_eligibility ?? '',
            'status' => $record->status ?? '',
        ];
    }

    private function presentHelperRecords(array $records): array
    {
        return array_map(fn (Record $record): array => [
            'id' => $record->id,
            'name' => $record->full_name ?? '',
            'registry_number' => $record->registry ?? '',
            'status' => $record->status ?? '',
            'kapisanan' => $this->displayKapisanan($record->kapisanan),
            'purok' => $record->purok ?? '',
            'grupo' => $record->grupo ?? '',
        ], $records);
    }

    /**
     * Normalize the name for comparison while retaining Jr/Sr as distinguishing suffixes.
     *
     * @return array{letters: string, suffix: string}
     */
    private function nameParts(string $name): array
    {
        preg_match('/\s+(jr|sr)\.?\s*$/iu', trim($name), $matches);
        $suffix = mb_strtolower($matches[1] ?? '');
        $name = preg_replace('/\s+(?:jr|sr)\.?\s*$/iu', '', trim($name)) ?? $name;

        return [
            'letters' => preg_replace('/[^\p{L}]/u', '', mb_strtolower($name)) ?? '',
            'suffix' => $suffix,
        ];
    }

    private function nameSimilarity(string $left, string $right): int
    {
        $leftLetters = mb_str_split($left);
        $rightLetters = mb_str_split($right);
        $leftLength = count($leftLetters);
        $rightLength = count($rightLetters);
        $longest = max($leftLength, $rightLength);

        if ($longest === 0) {
            return 100;
        }

        $previous = range(0, $rightLength);
        for ($row = 1; $row <= $leftLength; $row++) {
            $current = [$row];
            for ($column = 1; $column <= $rightLength; $column++) {
                $cost = $leftLetters[$row - 1] === $rightLetters[$column - 1] ? 0 : 1;
                $current[$column] = min(
                    $current[$column - 1] + 1,
                    $previous[$column] + 1,
                    $previous[$column - 1] + $cost
                );
            }
            $previous = $current;
        }

        return (int) round((1 - $previous[$rightLength] / $longest) * 100);
    }

    private function displayKapisanan(?string $kapisanan): string
    {
        return match (mb_strtolower(trim($kapisanan ?? ''))) {
            'b' => 'Buklod',
            'k' => 'Kadiwa',
            'binhi' => 'binhi',
            default => $kapisanan ?? '',
        };
    }

    private function storeKapisanan(?string $kapisanan): ?string
    {
        return match (mb_strtolower(trim($kapisanan ?? ''))) {
            'b', 'buklod' => 'B',
            'k', 'kadiwa' => 'K',
            'binhi' => 'binhi',
            default => $kapisanan,
        };
    }

    private function displayGender(?string $gender): string
    {
        return match (mb_strtoupper(trim($gender ?? ''))) {
            'L', 'MALE' => 'Male',
            'B', 'FEMALE' => 'Female',
            default => $gender ?? '',
        };
    }

    private function storeGender(string $gender): string
    {
        return match (mb_strtolower(trim($gender))) {
            'male' => 'L',
            'female' => 'B',
        };
    }
}
