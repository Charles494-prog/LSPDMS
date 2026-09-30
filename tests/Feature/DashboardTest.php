<?php

namespace Tests\Feature;

use App\Models\GlobalVariable;
use App\Models\Purok;
use App\Models\Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metrics_and_recent_baptisees_come_from_database_records(): void
    {
        Record::query()->create([
            'full_name' => 'Active Member',
            'registry' => 'REG-1001',
            'gender' => 'B',
            'purok' => '1',
            'grupo' => '3',
            'kapisanan' => 'K',
            'date_of_baptism' => now()->subDays(20)->toDateString(),
            'evangelist' => 'Evangelist One',
            'date_of_entry' => now()->subDays(5)->toDateString(),
            'r2_10_eligibility' => 'ELIGIBLE',
            'r2_10_endorsement' => 'ENDORSED',
            'status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'Eligible Without Endorsement',
            'registry' => 'REG-1003',
            'r2_10_eligibility' => 'ELIGIBLE',
            'status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'No Check Needed',
            'registry' => 'REG-1004',
            'r2_10_eligibility' => 'NO NEED TO CHECK',
            'r2_10_endorsement' => 'ENDORSED',
            'status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'Not Eligible but Endorsed',
            'registry' => 'REG-1005',
            'r2_10_eligibility' => 'NOT ELIGIBLE',
            'r2_10_endorsement' => 'ENDORSED',
            'status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'Inactive Member',
            'registry' => 'REG-1002',
            'gender' => 'L',
            'purok' => '2',
            'r2_10_eligibility' => 'NOT APPLICABLE',
            'r2_10_endorsement' => 'ENDORSED',
            'status' => 'INACTIVE',
        ]);
        GlobalVariable::query()->create([
            'locale' => 'Test Locale',
            'lcode' => 'L-001',
            's_number' => '8',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardMetrics', function (array $metrics): bool {
                $this->assertSame(5, $metrics['recordCount']);
                $this->assertSame(4, $metrics['activeCount']);
                $this->assertSame(1, $metrics['inactiveCount']);
                $this->assertSame(['MS'], $metrics['activeStatuses']);
                $this->assertSame('1', $metrics['puroks'][0]['id']);
                $this->assertSame(1, $metrics['puroks'][0]['counts']['MS']);
                $this->assertSame(1, $metrics['gender']['female']['MS']);
                $this->assertSame(1, $metrics['groupCounts']['MS']['Kadiwa']);
                $this->assertSame(1, $metrics['monthlyCounts'][11]['MS']);
                $this->assertSame(1, $metrics['monthlyPurokCounts'][11]['1']['MS']);
                $this->assertSame(10, $metrics['registryEncoded']);
                $this->assertEqualsWithDelta(66.66666666666666, $metrics['r210Updating'], 0.000001);
                $this->assertSame(8.0, $metrics['localeSNumberTarget']);
                $this->assertSame(50.0, $metrics['localeActivePercentage']);

                return true;
            })
            ->assertViewHas('newBaptisees', fn (array $newBaptisees): bool => count($newBaptisees) === 1
                && $newBaptisees[0]['name'] === 'Active Member');
    }

    public function test_registry_monitoring_counts_only_ms_uwp_mk_and_drills_into_missing_fields(): void
    {
        foreach ([
            ['MS member missing registry', 'RFID', 'MS', null],
            ['UWP member missing registry', null, 'UWP', null],
            ['MK member complete', 'REG-2003', 'MK', now()->subYears(20)->toDateString()],
            ['Inactive missing everything', null, 'INACTIVE', null],
        ] as [$name, $registry, $status, $birthday]) {
            Record::query()->create([
                'full_name' => $name,
                'registry' => $registry,
                'gender' => 'B',
                'purok' => '1',
                'grupo' => '3',
                'kapisanan' => 'K',
                'date_of_baptism' => now()->subMonths(2)->toDateString(),
                'evangelist' => 'Evangelist One',
                'date_of_birth' => $birthday,
                'status' => $status,
            ]);
        }

        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('MS, UWP, and MK records only.')
            ->assertViewHas('dashboardMetrics', function (array $metrics): bool {
                $this->assertSame(3, $metrics['registryRecordCount']);
                $this->assertSame([['id' => '1', 'recordTotal' => 3]], $metrics['registryPuroks']);

                $labels = array_column($metrics['registryReport'], 'label');
                $this->assertContains('Birthday', $labels);
                $this->assertNotContains('Entry date', $labels);

                $registryRow = collect($metrics['registryReport'])->firstWhere('label', 'Registry');
                $this->assertSame([2], $registryRow['missing']);
                $this->assertSame(
                    ['MS member missing registry', 'UWP member missing registry'],
                    array_column($registryRow['details'][0], 'name')
                );
                $this->assertSame('registry', $registryRow['details'][0][0]['field']);
                $this->assertTrue($registryRow['details'][0][0]['can_edit_grupo']);

                $birthdayRow = collect($metrics['registryReport'])->firstWhere('label', 'Birthday');
                $this->assertSame([2], $birthdayRow['missing']);
                $this->assertEqualsWithDelta(83.33333333333334, $metrics['registryCompletion'], 0.000001);

                return true;
            })
            ->assertViewHas('newBaptisees', fn (array $newBaptisees): bool => count($newBaptisees) === 3
                && collect($newBaptisees)->pluck('status')->sort()->values()->all() === ['MK', 'MS', 'UWP']);
    }

    public function test_dashboard_charts_use_only_supported_kalagayan_and_kapisanan_values(): void
    {
        foreach ([
            ['MS Binhi', 'MS', 'Binhi'],
            ['MK Buklod', 'MK', 'Buklod'],
            ['UWP Kadiwa', 'UWP', 'K'],
            ['UWP unclassified', 'UWP', 'Other'],
            ['Inactive Binhi', 'INACTIVE', 'Binhi'],
        ] as [$name, $status, $kapisanan]) {
            Record::query()->create([
                'full_name' => $name,
                'kapisanan' => $kapisanan,
                'status' => $status,
            ]);
        }

        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardMetrics', function (array $metrics): bool {
                $this->assertSame(['MS', 'MK', 'UWP'], array_keys($metrics['kalagayanCounts']));
                $this->assertSame(['MS' => 1, 'MK' => 1, 'UWP' => 2], $metrics['kalagayanCounts']);
                $this->assertSame(['Binhi', 'Kadiwa', 'Buklod'], $metrics['groupLabels']);
                $this->assertSame([
                    'MS' => ['Binhi' => 1, 'Kadiwa' => 0, 'Buklod' => 0],
                    'MK' => ['Binhi' => 0, 'Kadiwa' => 0, 'Buklod' => 1],
                    'UWP' => ['Binhi' => 0, 'Kadiwa' => 1, 'Buklod' => 0],
                ], $metrics['groupCounts']);
                $this->assertSame(
                    [['id' => 1, 'name' => 'MS Binhi', 'status' => 'MS']],
                    $metrics['groupRecords']['MS']['Binhi']
                );
                $this->assertSame(
                    [['id' => 2, 'name' => 'MK Buklod', 'status' => 'MK']],
                    $metrics['groupRecords']['MK']['Buklod']
                );
                $this->assertSame([], $metrics['groupRecords']['UWP']['Binhi']);

                return true;
            });
    }

    public function test_registry_details_include_field_actions_and_the_current_puroks_grupo_range(): void
    {
        Purok::query()->create([
            'purok_number' => 2,
            'grupo_start' => 6,
            'grupo_end' => 12,
        ]);
        Record::query()->create([
            'full_name' => 'Missing Grupo Member',
            'status' => 'MS',
            'purok' => '2',
            'grupo' => null,
        ]);
        Record::query()->create([
            'full_name' => 'Missing Purok Member',
            'status' => 'UWP',
            'purok' => null,
            'grupo' => null,
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardMetrics', function (array $metrics): bool {
                $grupo = collect($metrics['registryReport'])->firstWhere('label', 'Grupo');
                $grupoEntry = collect($grupo['details'])->flatten(1)->firstWhere('name', 'Missing Grupo Member');
                $this->assertSame('grupo', $grupoEntry['field']);
                $this->assertTrue($grupoEntry['can_edit_grupo']);
                $this->assertSame(6, $grupoEntry['grupo_min']);
                $this->assertSame(12, $grupoEntry['grupo_max']);

                $purok = collect($metrics['registryReport'])->firstWhere('label', 'Purok');
                $purokEntry = collect($purok['details'])->flatten(1)->firstWhere('name', 'Missing Purok Member');
                $this->assertSame('purok', $purokEntry['field']);
                $this->assertFalse($purokEntry['can_edit_grupo']);

                return true;
            });
    }

    public function test_dashboard_renders_without_records_and_does_not_use_demo_members(): void
    {
        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('const DASHBOARD = {', false)
            ->assertSee('LSP / Locale')
            ->assertSee('id="cfo-group-modal"', false)
            ->assertSee('id="registry-details-modal"', false)
            ->assertSee('id="registry-edit-modal"', false)
            ->assertSee('Click any count to view the records.')
            ->assertSee('No newly baptized records were found in the past year.')
            ->assertSee('Filtered by kalagayan: MS, UWP, and MK.')
            ->assertDontSee('Maria Lourdes Santos')
            ->assertDontSee('Long-time Member (excluded)');
    }
}
