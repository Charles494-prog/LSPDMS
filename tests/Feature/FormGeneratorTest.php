<?php

namespace Tests\Feature;

use App\Models\Record;
use App\Models\GlobalVariable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_generator_displays_weekly_monthly_and_notes_checker_sections(): void
    {
        $user = $this->makeSecretary(['form_generator_allow' => true]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('form-generator'))
            ->assertOk()
            ->assertSee('R4-02 Generator')
            ->assertSee('R4-03 Generator')
            ->assertSee('aria-label="Monthly generator sections"', false)
            ->assertSee('id="deliberation-tab"', false)
            ->assertSee('id="monthly-forms-tab"', false)
            ->assertSee('Monthly Forms')
            ->assertDontSee('data-panel="deliberation"', false)
            ->assertSee('Deliberation')
            ->assertSee(route('forms.monthly.modify'), false)
            ->assertSee('Resumen Generator &amp; R4-01 Generator', false)
            ->assertSee('Resumen &amp; R4-01 Snapshots', false)
            ->assertDontSee('id="resumen-generator-heading"', false)
            ->assertDontSee('id="r401-generator-heading"', false)
            ->assertSee(route('forms.monthly.r4-01.snapshots'), false)
            ->assertSee(route('forms.monthly.generators'), false)
            ->assertSee('Notes Checker')
            ->assertSee('id="notesRecordPagination"', false)
            ->assertSee('id="previousRecordsPage"', false)
            ->assertSee('id="nextRecordsPage"', false)
            ->assertSee('const recordsPerPage = 10;', false);
    }

    public function test_resumen_and_r4_01_generators_render_together_in_order(): void
    {
        $user = $this->makeSecretary(['form_generator_allow' => true]);

        $response = $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('forms.monthly.generators', [
                'purok' => 'all',
                'kalagayan' => 'MS',
            ]));
        $response->assertOk()
            ->assertSeeInOrder([
                'id="resumen-generator-heading"',
                'Resumen Generator',
                'id="r401-generator-heading"',
                'R4-01 Generator',
            ], false)
            ->assertSee(route('forms.monthly.generators'), false)
            ->assertSee('id="r401Filters"', false)
            ->assertSee('<option value="all" selected', false)
            ->assertSee('<option value="MS" selected', false)
            ->assertDontSee('Report month')
            ->assertDontSee('Generate preview')
            ->assertSee('Monthly Forms</a>', false);
        $this->assertSame(1, substr_count($response->getContent(), '<aside class="sidebar"'));
        $this->assertSame(1, substr_count($response->getContent(), '<header class="topbar">'));
    }

    public function test_r4_01_preview_and_docx_filter_records_and_paginate_twenty_entries(): void
    {
        Storage::fake('local');
        $user = $this->makeSecretary(['form_generator_allow' => true]);
        GlobalVariable::query()->create([
            'locale' => 'Sample Local',
            'lcode' => 'ABC-12',
            'district' => 'Sample District',
            'dcode' => 'D-7',
            's_number' => 'S-123',
            'kalihim_ms' => 'MS Secretary',
            'ksp_pd' => 'KSP Name',
            'pastor_destinado' => 'Pastor Name',
        ]);
        DB::table('puroks')->insert([
            'purok_number' => 1,
            'purok_name' => 'Sample Purok',
            'manggagawa' => 'Sample Manggagawa',
            'ministerial_id' => 'MIN-001',
            'grupo_start' => 1,
            'grupo_end' => 50,
            'full_purok' => false,
        ]);
        DB::table('monthly_movement_sessions')->insert([
            'active_key' => 'active',
            'status' => 'active',
            'period_month' => '2026-10-01',
            'started_by' => 'Secretary Example',
            'started_at' => '2026-10-01 09:00:00',
        ]);
        DB::table('puroks')->insert([
            'purok_number' => 2,
            'purok_name' => 'Purok 2A',
            'manggagawa' => 'Purok 2A Manggagawa',
            'ministerial_id' => 'MIN-002',
            'grupo_start' => 1,
            'grupo_end' => 7,
            'full_purok' => false,
        ]);
        DB::table('puroks')->insert([
            'purok_number' => 2,
            'purok_name' => 'Purok 2B',
            'manggagawa' => 'Purok 2B Manggagawa',
            'ministerial_id' => 'MIN-003',
            'grupo_start' => 8,
            'grupo_end' => 50,
            'full_purok' => false,
        ]);

        foreach (range(1, 43) as $grupo) {
            Record::query()->create([
                'full_name' => sprintf('Member %02d', $grupo),
                'registry' => sprintf('%013d', $grupo),
                'gender' => $grupo === 1 ? 'Male' : ($grupo === 2 ? 'Female' : 'L'),
                'purok' => '1',
                'grupo' => (string) $grupo,
                'kapisanan' => $grupo === 1 ? 'Binhi' : 'Kadiwa',
                'date_of_baptism' => '2020-03-04',
                'evangelist' => 'Maria Evangelist',
                'status' => 'MS',
                'monthly_code' => $grupo === 1 ? null : ($grupo === 2 ? 'MS: C+' : 'MS: B'),
            ]);
        }
        Record::query()->create([
            'full_name' => 'Different Status Member',
            'registry' => '9234567890123',
            'purok' => '1',
            'grupo' => '44',
            'status' => 'UWP',
        ]);
        Record::query()->create([
            'full_name' => 'Different Purok Member',
            'registry' => '8234567890123',
            'purok' => '2A',
            'grupo' => '1',
            'status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'Purok 2B Member',
            'registry' => '7234567890123',
            'purok' => '2B',
            'grupo' => '8',
            'status' => 'MS',
        ]);
        foreach (range(9, 28) as $grupo) {
            Record::query()->create([
                'full_name' => sprintf('Purok 2B Member %02d', $grupo),
                'registry' => sprintf('%013d', 7000000000000 + $grupo),
                'purok' => '2B',
                'grupo' => (string) $grupo,
                'status' => 'MS',
            ]);
        }

        $session = ['login_user' => [
            'id' => $user->id,
            'first_name' => 'Secretary',
            'last_name' => 'Example',
            'tungkulin' => 'Head Secretary',
        ]];
        $this->withSession($session)
            ->get(route('forms.monthly.r4-01', [
                'purok' => 1,
                'kalagayan' => 'MS',
            ]))
            ->assertOk()
            ->assertSee('font:700 8px Tahoma,Arial,sans-serif')
            ->assertSee('Sample Local')
            ->assertSee('ABC-12')
            ->assertSee('Sample District')
            ->assertSee('Member 01')
            ->assertSee('Member 43')
            ->assertSee('Maria Evangelist')
            ->assertSee('Sample Purok')
            ->assertSee('PANGALAN: Sample Manggagawa')
            ->assertSee('Assigned No.: MIN-001')
            ->assertSee('SAMPLE MANGGAGAWA')
            ->assertSee('Member 01')
            ->assertSee('C+')
            ->assertSee('Dcode: D-7')
            ->assertSee('Lcode: ABC-12')
            ->assertSee('PRK-GRP')
            ->assertSee('TUNGKULIN: MANGGAGAWA PAMUNUAN')
            ->assertSee('Member 20')
            ->assertSee('Member 21')
            ->assertSee('Member 40')
            ->assertSee('Member 41')
            ->assertSee('OCTOBER')
            ->assertDontSee('Report month')
            ->assertDontSee('Generate preview')
            ->assertDontSee('name="locale"', false)
            ->assertDontSee('name="lcode"', false)
            ->assertDontSee('Different Status Member')
            ->assertDontSee('Different Purok Member')
            ->assertSee('forms/monthly/r4-01/download?purok=1&amp;kalagayan=MS', false);

        $outputDirectory = storage_path('app/monthly-forms');
        $existingDocs = glob($outputDirectory.'/R4-01*.docx') ?: [];
        $this->withSession($session)
            ->get(route('forms.monthly.r4-01.download', [
                'purok' => 1,
                'kalagayan' => 'MS',
            ]))
            ->assertOk()
            ->assertDownload('R4-01 (MS) Oct 2026.docx');
        foreach (array_diff(glob($outputDirectory.'/R4-01*.docx') ?: [], $existingDocs) as $generatedDoc) {
            @unlink($generatedDoc);
        }
        $docxSnapshot = \App\Models\R401Snapshot::query()->where('format', 'docx')->firstOrFail();
        Storage::disk('local')->assertExists($docxSnapshot->file_path);
        $pdfResponse = $this->withSession($session)
            ->get(route('forms.monthly.r4-01.download.pdf', ['purok' => 'all', 'kalagayan' => 'MS']))
            ->assertOk()
            ->assertDownload('R4-01 (MS) Oct 2026.pdf');
        $pdfSnapshot = \App\Models\R401Snapshot::query()->where('format', 'pdf')->firstOrFail();
        Storage::disk('local')->assertExists($pdfSnapshot->file_path);
        $this->withSession($session)
            ->get(route('forms.monthly.r4-01.snapshots'))
            ->assertOk()
            ->assertSee('R4-01 Snapshots')
            ->assertSee(route('form-generator', ['panel' => 'monthly', 'monthly_section' => 'monthly-forms']))
            ->assertSee('Open Resumen &amp; R4-01 Generators', false)
            ->assertDontSee('Open R4-01 Generator')
            ->assertDontSee('Open Resumen Generator')
            ->assertSee('All Purok')
            ->assertSee($docxSnapshot->file_name)
            ->assertSee($pdfSnapshot->file_name);
        $this->withSession($session)
            ->get(route('forms.monthly.r4-01.snapshots.download', $docxSnapshot))
            ->assertOk()
            ->assertDownload($docxSnapshot->file_name);
        $this->assertStringStartsWith(
            '%PDF-',
            Storage::disk('local')->get($pdfSnapshot->file_path),
            'The archived PDF should contain a valid PDF document.'
        );

        $allPuroksResponse = $this->withSession($session)
            ->get(route('forms.monthly.r4-01', ['purok' => 'all', 'kalagayan' => 'MS']))
            ->assertOk();
        preg_match_all('/<article class="r401-page-sheet">(.*?)<\/article>/s', $allPuroksResponse->getContent(), $allPurokPages);
        $allPurokPageText = array_map('strip_tags', $allPurokPages[1]);
        $this->assertCount(6, $allPurokPageText);
        $this->assertStringContainsString('Member 41', $allPurokPageText[2]);
        $this->assertStringContainsString('Member 43', $allPurokPageText[2]);
        $this->assertStringNotContainsString('Different Purok Member', $allPurokPageText[2]);
        $this->assertStringContainsString('Different Purok Member', $allPurokPageText[3]);
        $this->assertStringContainsString('Purok 2A Manggagawa', $allPurokPageText[3]);
        $this->assertStringContainsString('MIN-002', $allPurokPageText[3]);
        $this->assertStringContainsString('Different Purok Member', $allPurokPageText[3]);
        $this->assertStringNotContainsString('Purok 2B Member', $allPurokPageText[3]);
        $this->assertStringContainsString('2-1', $allPurokPageText[3]);
        $this->assertStringNotContainsString('2A-1', $allPurokPageText[3]);
        $this->assertStringContainsString('Purok 2B Member', $allPurokPageText[4]);
        $this->assertStringContainsString('Purok 2B Manggagawa', $allPurokPageText[4]);
        $this->assertStringContainsString('MIN-003', $allPurokPageText[4]);
        $this->assertStringContainsString('2-8', $allPurokPageText[4]);
        $this->assertStringContainsString('Purok 2B Member 28', $allPurokPageText[5]);
        $this->assertStringContainsString('2-28', $allPurokPageText[5]);
        $this->assertStringNotContainsString('2B-8', $allPurokPageText[3]);
        $this->assertStringNotContainsString('2B-8', $allPurokPageText[4]);
        $reportTableBodies = [];
        foreach ($allPurokPages[1] as $pageHtml) {
            preg_match('/<table class="r401-report-table".*?<tbody>(.*?)<\/tbody>/s', $pageHtml, $bodyMatch);
            preg_match('/<tr>\s*<td>(\d+)<\/td>/', $bodyMatch[1] ?? '', $firstRowMatch);
            $reportTableBodies[] = (int) ($firstRowMatch[1] ?? 0);
        }
        $this->assertSame([1, 21, 41, 1, 1, 21], $reportTableBodies);

        $this->withSession($session)
            ->get(route('forms.monthly.r4-01', ['purok' => 2, 'kalagayan' => 'MS']))
            ->assertOk()
            ->assertSee('Different Purok Member')
            ->assertSee('Purok 2B Member')
            ->assertSee('Purok 2A Manggagawa')
            ->assertSee('Purok 2B Manggagawa')
            ->assertSee('MIN-002')
            ->assertSee('MIN-003')
            ->assertSee('2-1')
            ->assertSee('2-28')
            ->assertDontSee('2A-1')
            ->assertDontSee('2B-8');

        $docRow = [
            'code' => 'B',
            'name' => 'Monthly Member Example',
            'registry' => '1234567890123',
            'gender' => 'L',
            'purok_grupo' => '1-4',
            'kapisanan' => 'b',
            'date_of_baptism' => '04/03/2020',
            'doctrinator' => 'Maria Evangelist',
        ];
        $path = app(\App\Services\R401DocxExporter::class)->generate([
            'district' => 'Sample District',
            'dcode' => 'D-7',
            'locale' => 'Sample Local',
            'lcode' => 'ABC-12',
            'month' => 'October',
            'year' => '2026',
            'kalagayan' => 'MS',
            'signatures' => ['MS Secretary', 'KSP Name', '', 'Pastor Name'],
        ], [
            ['maysakop_name' => 'Page One Manggagawa', 'assigned_number' => 'MIN-001', 'page_number' => 1, 'rows' => array_fill(0, 20, $docRow)],
            ['maysakop_name' => 'Page Two Manggagawa', 'assigned_number' => 'MIN-002', 'page_number' => 2, 'rows' => [$docRow]],
        ]);
        try {
            $docx = new \PharData($path);
            $documentXml = $docx['word/document.xml']->getContent();
            $this->assertStringContainsString('Monthly Member Example', $documentXml);
            $this->assertStringContainsString('Sample District', $documentXml);
            $this->assertStringContainsString('Maria Evangelist', $documentXml);
            $this->assertStringContainsString('Dcode: D-7', $documentXml);
            $this->assertStringContainsString('Lcode: ABC-12', $documentXml);
            $this->assertStringContainsString('Page One Manggagawa', $documentXml);
            $this->assertStringContainsString('MIN-001', $documentXml);
            $this->assertStringContainsString('Page Two Manggagawa', $documentXml);
            $this->assertStringContainsString('MIN-002', $documentXml);
            $document = new \DOMDocument();
            $this->assertTrue($document->loadXML($documentXml, LIBXML_NONET));
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $rowText = '';
            foreach ($xpath->query('//w:tbl[3]/w:tr[2]//w:t') as $text) {
                $rowText .= $text->textContent;
            }
            $this->assertStringContainsString('1234567890123', $rowText);
            $this->assertSame(21, $xpath->query('//w:tbl[3]/w:tr')->length);
            $this->assertSame(8, $xpath->query('//w:tbl')->length);
            $this->assertSame(21, $xpath->query('//w:tbl[7]/w:tr')->length);
            $this->assertSame(
                '21',
                trim($xpath->query('//w:tbl[7]/w:tr[2]/w:tc[1]//w:t')->item(0)->textContent)
            );
            $statusLabels = '';
            foreach ($xpath->query('//w:tbl[1]/w:tr[1]/w:tc[3]//w:t') as $text) {
                $statusLabels .= $text->textContent;
            }
            $this->assertStringContainsString('☑ MS', $statusLabels);
            $this->assertSame(
                'left',
                $xpath->query('//w:tbl[3]/w:tr[2]/w:tc[3]/w:p/w:pPr/w:jc')->item(0)
                    ->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')
            );
            $this->assertSame(
                'center',
                $xpath->query('//w:tbl[3]/w:tr[2]/w:tc[1]/w:p/w:pPr/w:jc')->item(0)
                    ->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')
            );
            $this->assertSame('landscape', $xpath->query('//w:sectPr/w:pgSz')->item(0)->getAttributeNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'orient'
            ));
        } finally {
            @unlink($path);
        }
    }

    public function test_resumen_calculates_next_month_totals_from_prior_counts_and_movements(): void
    {
        Storage::fake('local');
        $user = $this->makeSecretary(['form_generator_allow' => true]);
        GlobalVariable::query()->create([
            'locale' => 'Sample Local',
            'lcode' => 'LOC-1',
            'head_lsp' => 'Head LSP Person',
            'ksp_pd' => 'KSP Person',
            'pastor_destinado' => 'Pastor Person',
        ]);
        DB::table('puroks')->insert([
            [
                'purok_number' => 1,
                'purok_name' => 'Purok Uno',
                'manggagawa' => 'Worker One',
                'ministerial_id' => 'MIN-001',
                'grupo_start' => 1,
                'grupo_end' => 24,
                'full_purok' => false,
            ],
            [
                'purok_number' => 2,
                'purok_name' => 'Purok Dos',
                'manggagawa' => 'Worker Two',
                'ministerial_id' => 'MIN-002',
                'grupo_start' => 1,
                'grupo_end' => 24,
                'full_purok' => false,
            ],
        ]);
        foreach ([
            ['2026-06-01', 1, 56, []],
            ['2026-06-01', 2, 20, []],
            ['2026-07-01', 1, 57, ['MS' => ['B' => 1]]],
            ['2026-07-01', 2, 19, ['MS' => ['B' => 2, 'E' => 3]]],
        ] as [$month, $purok, $msCount, $movementCounts]) {
            \App\Models\MonthlyRecordSnapshot::query()->create([
                'month' => $month,
                'purok_number' => $purok,
                'ms_count' => $msCount,
                'uwp_count' => 0,
                'mk_count' => 0,
                'movement_counts' => $movementCounts,
            ]);
        }

        $session = ['login_user' => [
            'id' => $user->id,
            'first_name' => 'Secretary',
            'last_name' => 'Example',
            'tungkulin' => 'Head Secretary',
        ]];
        $this->withSession($session)
            ->get(route('forms.monthly.resumen'))
            ->assertOk()
            ->assertSee('Resumen Generator')
            ->assertSee('Head LSP Person')
            ->assertSee('KSP Person')
            ->assertSee('Pastor Person')
            ->assertSee('Worker One')
            ->assertSee('Worker Two')
            ->assertViewHas('report', function (array $report): bool {
                $this->assertSame(56, $report['rows'][0]['ms']['A']);
                $this->assertSame(1, $report['rows'][0]['ms']['B']);
                $this->assertSame(57, $report['rows'][0]['ms_total']);
                $this->assertSame(20, $report['rows'][1]['ms']['A']);
                $this->assertSame(2, $report['rows'][1]['ms']['B']);
                $this->assertSame(3, $report['rows'][1]['ms']['E']);
                $this->assertSame(19, $report['rows'][1]['ms_total']);
                $this->assertSame(76, $report['totals']['ms']['A']);
                $this->assertSame(3, $report['totals']['ms']['B']);
                $this->assertSame(3, $report['totals']['ms']['E']);
                $this->assertSame(76, $report['totals']['ms_total']);

                return true;
            });

        $report = app(\App\Services\MonthlyResumenReport::class)
            ->generate(\Illuminate\Support\Carbon::createFromFormat('Y-m', '2026-07'));
        $docxPath = app(\App\Services\ResumenDocxExporter::class)
            ->generate($report, 'Sample Local', 'LOC-1', [
                'head_lsp' => 'Head LSP Person',
                'ksp_pd' => 'KSP Person',
                'pastor_destinado' => 'Pastor Person',
            ]);
        try {
            $docx = new \PharData($docxPath);
            $document = new \DOMDocument();
            $this->assertTrue($document->loadXML($docx['word/document.xml']->getContent(), LIBXML_NONET));
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $cellText = fn (string $query): string => trim($xpath->query($query)->item(0)?->textContent ?? '');
            $this->assertSame('56', $cellText('//w:tbl[2]/w:tr[5]/w:tc[3]//w:t'));
            $this->assertSame('1', $cellText('//w:tbl[2]/w:tr[5]/w:tc[4]//w:t'));
            $this->assertSame('57', $cellText('//w:tbl[2]/w:tr[5]/w:tc[12]//w:t'));
            $this->assertSame('20', $cellText('//w:tbl[2]/w:tr[6]/w:tc[3]//w:t'));
            $this->assertSame('3', $cellText('//w:tbl[2]/w:tr[6]/w:tc[9]//w:t'));
            $this->assertSame('19', $cellText('//w:tbl[2]/w:tr[6]/w:tc[12]//w:t'));
            $this->assertStringContainsString('HEAD LSP PERSON', $cellText('//w:tbl[3]/w:tr[1]/w:tc[1]'));
            $this->assertStringContainsString('KSP PERSON', $cellText('//w:tbl[3]/w:tr[1]/w:tc[2]'));
            $this->assertStringContainsString('PASTOR PERSON', $cellText('//w:tbl[3]/w:tr[1]/w:tc[3]'));
        } finally {
            unset($docx);
            @unlink($docxPath);
        }

        $download = $this->withSession($session)
            ->get(route('forms.monthly.resumen.download'))
            ->assertOk()
            ->assertDownload('Resumen Jul 2026.docx');
        $snapshot = \App\Models\R401Snapshot::query()->where('report_type', 'Resumen')->firstOrFail();
        Storage::disk('local')->assertExists($snapshot->file_path);
        $this->withSession($session)
            ->get(route('forms.monthly.r4-01.snapshots', ['report_type' => 'Resumen']))
            ->assertOk()
            ->assertSee('Resumen & R4-01 Snapshots')
            ->assertSee('Resumen · Resumen Jul 2026.docx');
    }

    public function test_resumen_assigns_snapshot_counts_to_separate_split_purok_rows(): void
    {
        $user = $this->makeSecretary(['form_generator_allow' => true]);
        DB::table('puroks')->insert([
            [
                'purok_number' => 2,
                'purok_name' => 'Purok 2A',
                'manggagawa' => 'Worker 2A',
                'ministerial_id' => 'MIN-002A',
                'grupo_start' => 1,
                'grupo_end' => 6,
                'full_purok' => false,
            ],
            [
                'purok_number' => 2,
                'purok_name' => 'Purok 2B',
                'manggagawa' => 'Worker 2B',
                'ministerial_id' => 'MIN-002B',
                'grupo_start' => 7,
                'grupo_end' => 24,
                'full_purok' => false,
            ],
        ]);
        Record::query()->create(['full_name' => 'Purok 2A Member', 'purok' => '2', 'grupo' => '2', 'status' => 'MS']);
        Record::query()->create(['full_name' => 'Purok 2B Member', 'purok' => '2', 'grupo' => '8', 'status' => 'MS']);
        foreach ([
            ['2026-06-01', 20, []],
            ['2026-07-01', 19, ['MS' => ['B' => 2, 'E' => 3]]],
        ] as [$month, $count, $movementCounts]) {
            \App\Models\MonthlyRecordSnapshot::query()->create([
                'month' => $month,
                'purok_number' => 2,
                'ms_count' => $count,
                'uwp_count' => 0,
                'mk_count' => 0,
                'movement_counts' => $movementCounts,
            ]);
        }

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('forms.monthly.resumen'))
            ->assertOk()
            ->assertSee('Purok 2A')
            ->assertSee('Purok 2B')
            ->assertViewHas('report', function (array $report): bool {
                $rows = $report['rows']->all();
                $this->assertCount(2, $rows);
                $this->assertSame(['Purok 2A', 'Purok 2B'], array_column($rows, 'purok_label'));
                $this->assertSame(20, array_sum(array_column(array_column($rows, 'ms'), 'A')));
                $this->assertSame(2, array_sum(array_column(array_column($rows, 'ms'), 'B')));
                $this->assertSame(3, array_sum(array_column(array_column($rows, 'ms'), 'E')));
                $this->assertSame(19, array_sum(array_column($rows, 'ms_total')));
                $this->assertSame(19, $report['totals']['ms_total']);

                return true;
            });
    }

    public function test_secretary_can_search_append_and_delete_dated_record_notes(): void
    {
        $user = $this->makeSecretary(['form_generator_allow' => true]);
        $record = Record::query()->create([
            'full_name' => 'Member With Notes',
            'registry' => 'REG-NOTE-1',
            'log_section' => '09/06/2025: Existing note, includes commas',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->getJson(route('forms.notes').'?q=REG-NOTE-1')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $record->id)
            ->assertJsonPath('0.notes.0', '09/06/2025: Existing note, includes commas');

        $response = $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.notes.append', $record), [
                'note' => 'Should be on deliberation, discuss with head secretary',
            ])
            ->assertOk()
            ->assertJsonCount(2, 'notes');

        $today = now()->timezone('Asia/Manila')->format('d/m/Y');
        $this->assertSame(
            $today.': Should be on deliberation, discuss with head secretary',
            $response->json('notes.1'),
        );
        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'log_section' => '09/06/2025: Existing note, includes commas, '.$today.': Should be on deliberation, discuss with head secretary',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->deleteJson(route('forms.notes.delete', ['record' => $record, 'noteIndex' => 0]))
            ->assertOk()
            ->assertJsonCount(1, 'notes')
            ->assertJsonPath('notes.0', $today.': Should be on deliberation, discuss with head secretary');
    }

    public function test_secretary_without_form_generator_permission_cannot_access_notes(): void
    {
        $user = $this->makeSecretary(['form_generator_allow' => false]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('form-generator'))
            ->assertForbidden();
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->getJson(route('forms.notes'))
            ->assertForbidden();
    }

    private function makeSecretary(array $attributes = []): User
    {
        return User::query()->create($attributes + [
            'gmail' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
        ]);
    }
}
