<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\GlobalVariable;
use App\Models\MonthlyMovementSession;
use App\Models\MonthlyRecordSnapshot;
use App\Models\Purok;
use App\Models\R401Snapshot;
use App\Services\MonthlyResumenReport;
use App\Services\R401DocxExporter;
use App\Services\ResumenDocxExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FormGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'panel' => ['nullable', 'in:weekly,monthly,notes'],
            'monthly_section' => ['nullable', 'in:deliberation,monthly-forms'],
        ]);

        return view('form-generator', [
            'activePanel' => $validated['panel'] ?? 'weekly',
            'activeMonthlyPanel' => $validated['monthly_section'] ?? 'deliberation',
        ]);
    }

    public function r401(Request $request)
    {
        $data = $this->r401ReportData($request);

        return view('forms.monthly-r4-01', $data);
    }

    public function monthlyGenerators(Request $request, MonthlyResumenReport $resumenReport)
    {
        $resumenData = $this->resumenReportData($resumenReport);
        $r401Data = $this->r401ReportData($request);

        return view('forms.monthly-generators', array_merge($resumenData, $r401Data, [
            'embedded' => true,
        ]));
    }

    public function resumen(Request $request, MonthlyResumenReport $resumenReport)
    {
        $data = $this->resumenReportData($resumenReport);

        return view('forms.monthly-resumen', $data);
    }

    public function downloadResumen(
        Request $request,
        MonthlyResumenReport $resumenReport,
        ResumenDocxExporter $exporter
    ): BinaryFileResponse {
        $data = $this->resumenReportData($resumenReport);
        $generatedPath = $exporter->generate(
            $data['report'],
            $data['locale'],
            $data['lcode'],
            $data['signatories']
        );
        $fileName = 'Resumen '.$data['report']['month']->format('M Y').'.docx';
        $contents = file_get_contents($generatedPath);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the generated Resumen DOCX for snapshot storage.');
        }
        $relativePath = 'r4-01-snapshots/'.$data['report']['month']->format('Y-m').'/'.Str::uuid().'.docx';
        if (! Storage::disk('local')->put($relativePath, $contents)) {
            throw new \RuntimeException('Unable to store the generated Resumen snapshot.');
        }
        unlink($generatedPath);
        $snapshot = R401Snapshot::query()->create([
            'period_month' => $data['report']['month']->toDateString(),
            'report_type' => 'Resumen',
            'kalagayan' => '',
            'purok_selection' => 'All Puroks',
            'format' => 'docx',
            'file_path' => $relativePath,
            'file_name' => $fileName,
            'generated_by' => (string) ($request->session()->get('login_user', [])['gmail'] ?? ''),
        ]);

        return response()->download(
            Storage::disk('local')->path($snapshot->file_path),
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    public function downloadR401(Request $request, R401DocxExporter $exporter): BinaryFileResponse
    {
        $request->validate([
            'purok' => ['required', 'string', 'max:20'],
            'kalagayan' => ['required', 'string', 'in:MS,UWP'],
        ]);
        $data = $this->r401ReportData($request);
        abort_if($data['reportRows'] === [], 404, 'No records match the selected Purok and Kalagayan.');
        $globalVariables = $data['globalVariables'];
        $kalihim = $data['selectedStatus'] === 'UWP'
            ? ($globalVariables?->kalihim_uwp ?: $globalVariables?->kalihim_ms)
            : ($globalVariables?->kalihim_ms ?: $globalVariables?->kalihim_uwp);
        $path = $exporter->generate([
            'district' => $globalVariables?->district,
            'dcode' => $globalVariables?->dcode,
            'locale' => $data['locale'],
            'lcode' => $data['lcode'],
            'month' => $data['month']->format('F'),
            'year' => $data['month']->format('Y'),
            'kalagayan' => $data['selectedStatus'],
            'signatures' => [
                $kalihim,
                $globalVariables?->ksp_pd,
                '',
                $globalVariables?->pastor_destinado,
            ],
        ], $data['reportPages']);

        $fileName = 'R4-01 ('.$data['selectedStatus'].') '.$data['month']->format('M Y').'.docx';
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the generated R4-01 DOCX for snapshot storage.');
        }
        $snapshot = $this->storeR401Snapshot($request, $data, 'docx', $fileName, $contents);
        unlink($path);

        return response()->download(
            Storage::disk('local')->path($snapshot->file_path),
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    public function downloadR401Pdf(Request $request)
    {
        $request->validate([
            'purok' => ['required', 'string', 'max:20'],
            'kalagayan' => ['required', 'string', 'in:MS,UWP'],
        ]);
        $data = $this->r401ReportData($request);
        abort_if($data['reportRows'] === [], 404, 'No records match the selected Purok and Kalagayan.');

        $fileName = 'R4-01 ('.$data['selectedStatus'].') '.$data['month']->format('M Y').'.pdf';
        $contents = Pdf::loadView('forms.monthly-r4-01', $data)
            ->setPaper('a4', 'landscape')
            ->output();
        $snapshot = $this->storeR401Snapshot($request, $data, 'pdf', $fileName, $contents);

        return response()->download(Storage::disk('local')->path($snapshot->file_path), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function r401Snapshots(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'kalagayan' => ['nullable', 'in:MS,UWP'],
            'report_type' => ['nullable', 'in:Resumen,R4-01'],
        ]);
        $snapshots = R401Snapshot::query()
            ->when(isset($filters['month']), fn ($query) => $query->whereYear('period_month', substr($filters['month'], 0, 4))
                ->whereMonth('period_month', substr($filters['month'], 5, 2)))
            ->when(isset($filters['kalagayan']), fn ($query) => $query->where('kalagayan', $filters['kalagayan']))
            ->when(isset($filters['report_type']), fn ($query) => $query->where('report_type', $filters['report_type']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('forms.monthly-r4-01-snapshots', [
            'snapshots' => $snapshots,
            'filters' => $filters,
        ]);
    }

    public function downloadR401Snapshot(R401Snapshot $snapshot)
    {
        abort_unless(Storage::disk('local')->exists($snapshot->file_path), 404);

        return response()->download(
            Storage::disk('local')->path($snapshot->file_path),
            $snapshot->file_name,
            ['Content-Type' => $snapshot->format === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    private function storeR401Snapshot(
        Request $request,
        array $data,
        string $format,
        string $fileName,
        string $contents
    ): R401Snapshot {
        $relativePath = 'r4-01-snapshots/'.$data['month']->format('Y-m').'/'.Str::uuid().'.'.$format;
        if (! Storage::disk('local')->put($relativePath, $contents)) {
            throw new \RuntimeException('Unable to store the generated R4-01 snapshot.');
        }

        return R401Snapshot::query()->create([
            'period_month' => $data['month']->toDateString(),
            'kalagayan' => $data['selectedStatus'],
            'purok_selection' => $data['selectedPurok'] === 'all' ? 'All Purok' : 'Purok '.$data['selectedPurok'],
            'format' => $format,
            'file_path' => $relativePath,
            'file_name' => $fileName,
            'generated_by' => (string) ($request->session()->get('login_user', [])['gmail'] ?? ''),
        ]);
    }

    private function resumenReportData(MonthlyResumenReport $resumenReport): array
    {
        $defaultMonth = MonthlyMovementSession::query()
            ->whereNotNull('period_month')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->value('period_month')
            ?? MonthlyRecordSnapshot::query()->max('month')
            ?? now()->startOfMonth()->toDateString();
        $selectedMonth = Carbon::parse($defaultMonth)->format('Y-m');
        $report = $resumenReport->generate(Carbon::createFromFormat('Y-m', $selectedMonth));
        $globalVariables = GlobalVariable::query()->first();

        return [
            'report' => $report,
            'selectedMonth' => $selectedMonth,
            'locale' => trim((string) ($globalVariables?->locale ?? '')),
            'lcode' => trim((string) ($globalVariables?->lcode ?? '')),
            'signatories' => [
                'head_lsp' => trim((string) ($globalVariables?->head_lsp ?? '')),
                'ksp_pd' => trim((string) ($globalVariables?->ksp_pd ?? '')),
                'pastor_destinado' => trim((string) ($globalVariables?->pastor_destinado ?? '')),
            ],
        ];
    }

    private function r401ReportData(Request $request): array
    {
        $validated = $request->validate([
            'purok' => ['nullable', 'string', 'max:20'],
            'kalagayan' => ['nullable', 'string', 'in:MS,UWP'],
        ]);
        if (isset($validated['purok']) && $validated['purok'] !== 'all') {
            $request->validate(['purok' => ['integer', 'exists:puroks,purok_number']]);
        }
        $sessionMonth = MonthlyMovementSession::query()
            ->whereNotNull('period_month')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->value('period_month');
        $month = Carbon::parse($sessionMonth ?? now())->startOfMonth();
        $puroks = Purok::query()->orderBy('purok_number')->get(['purok_number', 'purok_name']);
        $selectedPurok = $validated['purok'] ?? null;
        $selectedStatus = $validated['kalagayan'] ?? null;
        $report = $selectedPurok !== null && $selectedStatus !== null
            ? $this->selectedMemberRows($selectedPurok, $selectedStatus)
            : ['rows' => [], 'pages' => []];
        $globalVariables = GlobalVariable::query()->first();

        return [
            'month' => $month,
            'reportRows' => $report['rows'],
            'reportPages' => $report['pages'],
            'puroks' => $puroks,
            'selectedPurok' => $selectedPurok,
            'selectedStatus' => $selectedStatus,
            'globalVariables' => $globalVariables,
            'locale' => $globalVariables?->locale ?? '',
            'lcode' => $globalVariables?->lcode ?? '',
            'dcode' => $globalVariables?->dcode ?? '',
            'district' => $globalVariables?->district ?? '',
            'kalihimName' => $selectedStatus === 'UWP'
                ? (($globalVariables?->kalihim_uwp ?: $globalVariables?->kalihim_ms) ?? '')
                : (($globalVariables?->kalihim_ms ?: $globalVariables?->kalihim_uwp) ?? ''),
        ];
    }

    private function selectedMemberRows(string $purok, string $status): array
    {
        $records = Record::query()
            ->whereRaw('UPPER(TRIM(status)) = ?', [$status])
            ->orderBy('full_name')
            ->get([
                'full_name',
                'registry',
                'gender',
                'purok',
                'grupo',
                'kapisanan',
                'date_of_baptism',
                'evangelist',
                'status',
                'monthly_code',
            ])
            ->when($purok !== 'all', fn ($records) => $records->filter(
                fn (Record $record): bool => $this->numericSortKey((string) $record->purok) === (int) $purok
            ))
            ->groupBy(fn (Record $record): string => (string) $this->numericSortKey((string) $record->purok))
            ->sortBy(fn ($group, string $key): int => $this->numericSortKey($key));
        $purokDetails = Purok::query()
            ->orderBy('purok_number')
            ->orderBy('grupo_start')
            ->get(['id', 'purok_number', 'purok_name', 'manggagawa', 'ministerial_id', 'grupo_start', 'grupo_end', 'full_purok']);
        $reportRows = [];
        $reportPages = [];

        foreach ($records as $purokNumber => $purokRecords) {
            $number = (int) $purokNumber;
            $splitPuroks = $purokDetails->where('purok_number', $number)->values();
            $assignedRecords = $splitPuroks->mapWithKeys(function (Purok $splitPurok) use ($purokRecords): array {
                $min = $splitPurok->full_purok ? 1 : (int) $splitPurok->grupo_start;
                $max = $splitPurok->full_purok ? 24 : (int) $splitPurok->grupo_end;

                return [
                    $splitPurok->id => $purokRecords->filter(
                        fn (Record $record): bool => (int) $record->grupo >= $min && (int) $record->grupo <= $max
                    ),
                ];
            });
            if ($splitPuroks->isEmpty()) {
                $assignedRecords->put(null, $purokRecords);
            }

            foreach ($assignedRecords as $splitPurokId => $splitRecords) {
                if ($splitRecords->isEmpty()) {
                    continue;
                }
                $splitPurok = $splitPuroks->firstWhere('id', $splitPurokId);
                $rows = $splitRecords
                    ->sort(function (Record $left, Record $right): int {
                        $grupoOrder = (int) $left->grupo <=> (int) $right->grupo;

                        return $grupoOrder !== 0
                            ? $grupoOrder
                            : strcasecmp((string) $left->full_name, (string) $right->full_name);
                    })
                    ->values()
                    ->map(function (Record $record): array {
                        $monthlyCode = trim((string) $record->monthly_code);
                        $codeParts = explode(':', $monthlyCode, 2);

                        return [
                            'code' => strtoupper(trim($codeParts[1] ?? '')) ?: 'A',
                            'name' => trim((string) $record->full_name),
                            'registry' => strtoupper(trim((string) $record->registry)),
                            'gender' => $this->displayGender((string) $record->gender),
                            'purok_grupo' => $this->numericSortKey((string) $record->purok).'-'.trim((string) $record->grupo, " \t\n\r\0\x0B-"),
                            'kapisanan' => $this->displayKapisanan((string) $record->kapisanan),
                            'date_of_baptism' => $record->date_of_baptism?->format('d/m/Y') ?? '',
                            'doctrinator' => trim((string) $record->evangelist),
                        ];
                    })->all();
                $reportRows = [...$reportRows, ...$rows];
                foreach (array_chunk($rows, 20) as $pageIndex => $pageRows) {
                    $reportPages[] = [
                        'purok_number' => $purokNumber,
                        'purok_name' => $splitPurok?->purok_name ?? 'Purok '.$purokNumber,
                        'maysakop_name' => trim((string) ($splitPurok?->manggagawa ?? '')),
                        'assigned_number' => trim((string) ($splitPurok?->ministerial_id ?? '')),
                        'page_number' => $pageIndex + 1,
                        'rows' => $pageRows,
                    ];
                }
            }
        }

        return ['rows' => $reportRows, 'pages' => $reportPages];
    }

    private function numericSortKey(string $value): int
    {
        return (int) preg_replace('/\D+/', '', $value);
    }

    private function displayGender(string $gender): string
    {
        return match (mb_strtolower(trim($gender))) {
            'l', 'male' => 'L',
            'b', 'female' => 'B',
            default => '',
        };
    }

    private function displayKapisanan(string $kapisanan): string
    {
        return match (mb_strtolower(trim($kapisanan))) {
            'b', 'buklod' => 'B',
            'k', 'kadiwa' => 'K',
            's', 'suliranin' => 'S',
            'binhi' => 'b',
            default => trim($kapisanan),
        };
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
