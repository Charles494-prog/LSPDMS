<?php

namespace App\Http\Controllers;

use App\Models\GlobalVariable;
use App\Models\Purok;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('login_user');
        $records = Record::query()
            ->select([
                'id',
                'full_name',
                'registry',
                'gender',
                'purok',
                'grupo',
                'kapisanan',
                'date_of_baptism',
                'evangelist',
                'date_of_entry',
                'date_of_birth',
                'r2_10_eligibility',
                'r2_10_endorsement',
                'status',
            ])
            ->get();

        $statusLabels = $records
            ->map(fn (Record $record): string => mb_strtoupper(trim((string) $record->status)))
            ->filter()
            ->unique()
            ->sortBy(fn (string $status): array => [
                array_search($status, ['MS', 'UWP', 'MK', 'INACTIVE'], true) === false
                    ? 4
                    : array_search($status, ['MS', 'UWP', 'MK', 'INACTIVE'], true),
                $status,
            ])
            ->values()
            ->all();

        $statusCounts = array_fill_keys($statusLabels, 0);
        $hasUnspecifiedStatus = false;
        foreach ($records as $record) {
            $status = mb_strtoupper(trim((string) $record->status));
            if ($status !== '' && array_key_exists($status, $statusCounts)) {
                $statusCounts[$status]++;
            } elseif ($status === '') {
                $hasUnspecifiedStatus = true;
            }
        }
        $statusChart = array_map(fn (string $status): array => [
            'code' => $status,
            'label' => $status,
            'count' => $statusCounts[$status],
        ], $statusLabels);
        if ($hasUnspecifiedStatus) {
            $statusChart[] = [
                'code' => 'UNSPECIFIED',
                'label' => 'Unspecified',
                'count' => $records->filter(fn (Record $record): bool => trim((string) $record->status) === '')->count(),
            ];
        }

        $activeStatuses = array_values(array_filter($statusLabels, fn (string $status): bool => $status !== 'INACTIVE'));
        $activeRecords = $records->filter(fn (Record $record): bool => trim((string) $record->status) !== ''
            && mb_strtoupper(trim((string) $record->status)) !== 'INACTIVE');
        $activeTotal = $activeRecords->count();
        $inactiveTotal = $statusCounts['INACTIVE'] ?? 0;

        $purokKeys = $records
            ->map(fn (Record $record): string => trim((string) $record->purok) ?: 'Unassigned')
            ->unique()
            ->sort(fn (string $left, string $right): int => $left === 'Unassigned'
                ? 1
                : ($right === 'Unassigned' ? -1 : strnatcasecmp($left, $right)))
            ->values();
        $puroks = $purokKeys->map(function (string $purok) use ($activeRecords, $activeStatuses, $records): array {
            $members = $activeRecords->filter(fn (Record $record): bool => (trim((string) $record->purok) ?: 'Unassigned') === $purok);
            $counts = [];
            foreach ($activeStatuses as $status) {
                $counts[$status] = $members->filter(fn (Record $record): bool => mb_strtoupper(trim((string) $record->status)) === $status)->count();
            }

            return [
                'id' => $purok,
                'counts' => $counts,
                'total' => array_sum($counts),
                'recordTotal' => $records->filter(fn (Record $record): bool => (trim((string) $record->purok) ?: 'Unassigned') === $purok)->count(),
            ];
        })->all();

        $genderCounts = [];
        foreach (['female' => 'B', 'male' => 'L'] as $gender => $code) {
            $genderCounts[$gender] = array_fill_keys($activeStatuses, 0);
            foreach ($activeRecords as $record) {
                if (mb_strtoupper(trim((string) $record->gender)) === $code) {
                    $status = mb_strtoupper(trim((string) $record->status));
                    $genderCounts[$gender][$status]++;
                }
            }
        }

        $groupLabels = ['Binhi', 'Kadiwa', 'Buklod'];
        $groupStatuses = ['MS', 'MK', 'UWP'];
        $groupCounts = array_fill_keys($groupStatuses, array_fill_keys($groupLabels, 0));
        $groupRecords = array_fill_keys($groupStatuses, array_fill_keys($groupLabels, []));
        foreach ($activeRecords as $record) {
            $status = mb_strtoupper(trim((string) $record->status));
            if (! array_key_exists($status, $groupCounts)) {
                continue;
            }

            $group = match (mb_strtolower(trim((string) $record->kapisanan))) {
                'binhi' => 'Binhi',
                'k', 'kadiwa' => 'Kadiwa',
                'b', 'buklod' => 'Buklod',
                default => null,
            };
            if ($group !== null) {
                $groupCounts[$status][$group]++;
                $groupRecords[$status][$group][] = [
                    'id' => $record->id,
                    'name' => trim((string) $record->full_name) ?: 'Unnamed record',
                    'status' => $status,
                ];
            }
        }

        $monthStart = Carbon::now()->startOfMonth()->subMonths(11);
        $monthLabels = [];
        $monthlyCounts = [];
        $monthlyPurokCounts = [];
        for ($offset = 0; $offset < 12; $offset++) {
            $month = $monthStart->copy()->addMonths($offset);
            $monthKey = $month->format('Y-m');
            $monthLabels[] = $month->format('M Y');
            $monthlyCounts[$monthKey] = array_fill_keys($activeStatuses, 0);
            $monthlyPurokCounts[$monthKey] = [];
        }
        foreach ($activeRecords as $record) {
            if (! $record->date_of_entry) {
                continue;
            }
            $monthKey = $record->date_of_entry->format('Y-m');
            if (isset($monthlyCounts[$monthKey])) {
                $status = mb_strtoupper(trim((string) $record->status));
                $purok = trim((string) $record->purok) ?: 'Unassigned';
                $monthlyCounts[$monthKey][$status]++;
                $monthlyPurokCounts[$monthKey][$purok] ??= array_fill_keys($activeStatuses, 0);
                $monthlyPurokCounts[$monthKey][$purok][$status]++;
            }
        }

        $registryStatuses = ['MS', 'UWP', 'MK'];
        $registryRecords = $records->filter(fn (Record $record): bool => in_array(
            mb_strtoupper(trim((string) $record->status)),
            $registryStatuses,
            true
        ));
        $registryPurokKeys = $registryRecords
            ->map(fn (Record $record): string => trim((string) $record->purok) ?: 'Unassigned')
            ->unique()
            ->sort(fn (string $left, string $right): int => $left === 'Unassigned'
                ? 1
                : ($right === 'Unassigned' ? -1 : strnatcasecmp($left, $right)))
            ->values();
        $registryPuroks = $registryPurokKeys->map(fn (string $purok): array => [
            'id' => $purok,
            'recordTotal' => $registryRecords->filter(
                fn (Record $record): bool => (trim((string) $record->purok) ?: 'Unassigned') === $purok
            )->count(),
        ])->all();
        $registryFields = [
            'Registry' => 'registry',
            'Gender' => 'gender',
            'Purok' => 'purok',
            'Grupo' => 'grupo',
            'Kapisanan' => 'kapisanan',
            'Baptism date' => 'date_of_baptism',
            'Evangelist' => 'evangelist',
            'Birthday' => 'date_of_birth',
        ];
        $purokSettings = Purok::query()
            ->get(['purok_number', 'grupo_start', 'grupo_end'])
            ->keyBy(fn (Purok $purok): string => (string) $purok->purok_number);
        $registryReport = [];
        foreach ($registryFields as $label => $column) {
            $isMissing = fn (Record $record): bool => $column === 'registry'
                ? in_array(mb_strtoupper(trim((string) $record->{$column})), ['', 'RFID'], true)
                : trim((string) $record->{$column}) === '';
            $fieldDetails = $registryPurokKeys->map(fn (string $purok): array => $registryRecords
                ->filter(fn (Record $record): bool => (trim((string) $record->purok) ?: 'Unassigned') === $purok
                    && $isMissing($record))
                ->map(function (Record $record) use ($column, $purokSettings): array {
                    $recordPurok = trim((string) $record->purok);
                    preg_match('/\d+/', $recordPurok, $purokNumber);
                    $purokSettingsForRecord = isset($purokNumber[0]) ? $purokSettings->get($purokNumber[0]) : null;

                    return [
                        'id' => $record->id,
                        'name' => trim((string) $record->full_name) ?: 'Unnamed record',
                        'status' => mb_strtoupper(trim((string) $record->status)),
                        'field' => $column,
                        'purok_id' => $recordPurok ?: 'Unassigned',
                        'purok' => $recordPurok ?: 'Unassigned',
                        'can_edit_grupo' => $recordPurok !== '' && $recordPurok !== 'Unassigned',
                        'grupo_min' => $purokSettingsForRecord?->grupo_start ?? 1,
                        'grupo_max' => $purokSettingsForRecord?->grupo_end ?? 24,
                    ];
                })
                ->values()
                ->all());
            $registryReport[] = [
                'label' => $label,
                'missing' => $registryPurokKeys->map(fn (string $purok): int => $registryRecords
                    ->filter(fn (Record $record): bool => (trim((string) $record->purok) ?: 'Unassigned') === $purok
                        && $isMissing($record))
                    ->count())->all(),
                'details' => $fieldDetails->all(),
            ];
        }
        $reportFieldsCount = count($registryFields);
        $missingFieldsCount = array_sum(array_map(fn (array $field): int => array_sum($field['missing']), $registryReport));
        $registryRecordCount = $registryRecords->count();
        $registryCompletion = $registryRecordCount === 0 || $reportFieldsCount === 0
            ? 0
            : (($registryRecordCount * $reportFieldsCount - $missingFieldsCount) / ($registryRecordCount * $reportFieldsCount)) * 100;

        $r210EligibleRecords = $activeRecords->filter(fn (Record $record): bool => in_array(
            mb_strtoupper(trim((string) $record->r2_10_eligibility)),
            ['ELIGIBLE', 'NO NEED TO CHECK'],
            true
        ));
        $eligibleR210Count = $r210EligibleRecords->count();
        $endorsedR210Count = $r210EligibleRecords
            ->filter(fn (Record $record): bool => mb_strtoupper(trim((string) $record->r2_10_endorsement)) === 'ENDORSED')
            ->count();
        $r210Updating = $eligibleR210Count === 0 ? 0 : $endorsedR210Count / $eligibleR210Count * 100;
        $globalVariables = GlobalVariable::query()->first();
        $localeSNumber = trim((string) ($globalVariables?->s_number ?? ''));
        $localeSNumberTarget = is_numeric($localeSNumber) ? (float) $localeSNumber : 0.0;
        $localeActivePercentage = $localeSNumberTarget > 0
            ? $activeTotal / $localeSNumberTarget * 100
            : 0.0;

        $baptismCutoff = Carbon::today()->subYear();
        $newBaptisees = $registryRecords
            ->filter(fn (Record $record): bool => $record->date_of_baptism !== null
                && $record->date_of_baptism->greaterThanOrEqualTo($baptismCutoff)
                && $record->date_of_baptism->lessThanOrEqualTo(Carbon::today()))
            ->sortByDesc(fn (Record $record): string => $record->date_of_baptism->format('Y-m-d'))
            ->map(fn (Record $record): array => [
                'name' => $record->full_name ?? '',
                'purok' => $record->purok ?? '',
                'grupo' => $record->grupo ?? '',
                'status' => mb_strtoupper(trim((string) $record->status)),
                'evangelist' => $record->evangelist ?? '',
                'baptized_at' => $record->date_of_baptism->format('Y-m-d'),
            ])
            ->values()
            ->all();

        return view('dashboard', [
            'user' => $user,
            'dashboardMetrics' => [
                'recordCount' => $records->count(),
                'activeCount' => $activeTotal,
                'inactiveCount' => $inactiveTotal,
                'statuses' => $statusChart,
                'kalagayanCounts' => array_combine(
                    ['MS', 'MK', 'UWP'],
                    array_map(fn (string $status): int => $statusCounts[$status] ?? 0, ['MS', 'MK', 'UWP'])
                ),
                'activeStatuses' => $activeStatuses,
                'puroks' => $puroks,
                'gender' => $genderCounts,
                'groupLabels' => $groupLabels,
                'groupCounts' => $groupCounts,
                'groupRecords' => $groupRecords,
                'monthLabels' => $monthLabels,
                'monthlyCounts' => array_values($monthlyCounts),
                'monthlyPurokCounts' => array_values($monthlyPurokCounts),
                'registryPuroks' => $registryPuroks,
                'registryReport' => $registryReport,
                'registryCompletion' => $registryCompletion,
                'registryRecordCount' => $registryRecordCount,
                'registryEncoded' => ($registryRecordCount * $reportFieldsCount) - $missingFieldsCount,
                'r210Updating' => $r210Updating,
                'localeSNumberTarget' => $localeSNumberTarget,
                'localeActivePercentage' => $localeActivePercentage,
            ],
            'newBaptisees' => $newBaptisees,
            'globalVariables' => $globalVariables,
        ]);
    }
}
