<?php

namespace Tests\Feature;

use App\Models\Purok;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterBnhHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_permission_is_required_to_open_the_helper(): void
    {
        $secretary = User::query()->create([
            'gmail' => 'helper-secretary@example.test',
            'password' => 'password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
            'filter_bnh_helper_allow' => false,
        ]);

        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('filter-bnh-helper'))
            ->assertForbidden();

        $headSecretary = User::query()->create([
            'gmail' => 'helper-admin@example.test',
            'password' => 'password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);
        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->putJson(route('superadmin.permissions.update'), [
                'permissions' => [[
                    'id' => $secretary->id,
                    'dashboard_allow' => 0,
                    'form_generator_allow' => 0,
                    'Delistment_allow' => 0,
                    'Inquire_Allow' => 0,
                    'tarheta_status_allow' => 0,
                    'filter_bnh_helper_allow' => 1,
                ]],
            ])
            ->assertOk();

        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('filter-bnh-helper'))
            ->assertOk()
            ->assertSee('Filter &amp; BNH Helper', false)
            ->assertSee('Purok Group Filter')
            ->assertSee('BNH Form Helper')
            ->assertSee('BNH Helper Form Resumen')
            ->assertSee('fbnhPanelMatrix', false)
            ->assertSee('fbnhPanelList', false)
            ->assertSee('fbnhPanelResumen', false)
            ->assertSee('fbnhMembersModal', false);
        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->getJson(route('session.status'))
            ->assertOk()
            ->assertJsonPath('permissions.filter_bnh_helper_allow', true);
    }

    public function test_group_filter_drills_into_active_records_and_respects_split_purok_ranges(): void
    {
        [$purok2a] = $this->makeSplitPuroks();
        $male = $this->makeRecord([
            'full_name' => 'Male MS',
            'purok' => '2',
            'grupo' => '1',
            'gender' => 'Male',
            'status' => 'MS',
        ]);
        $female = $this->makeRecord([
            'full_name' => 'Female UWP',
            'purok' => '2',
            'grupo' => '7',
            'gender' => 'Female',
            'status' => 'UWP',
        ]);
        $inactive = $this->makeRecord([
            'full_name' => 'Inactive Male',
            'purok' => '2',
            'grupo' => '1',
            'gender' => 'Male',
            'status' => 'INACTIVE',
        ]);

        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.matrix', ['purok_id' => $purok2a->id, 'status' => 'MS']))
            ->assertOk()
            ->assertJsonPath('rows.0.label', '2a-1')
            ->assertJsonPath('rows.0.male', 1)
            ->assertJsonPath('rows.0.total', 1);
        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.matrix', ['purok_id' => $purok2a->id, 'status' => 'ALL']))
            ->assertOk()
            ->assertJsonPath('rows.2.type', 'purok_total')
            ->assertJsonPath('rows.2.label', 'Total 2a')
            ->assertJsonPath('rows.2.total', 1)
            ->assertJsonPath('rows.3.type', 'grand_total')
            ->assertJsonPath('rows.3.label', 'Overall Total')
            ->assertJsonPath('rows.3.total', 1);

        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.members', [
                'purok_id' => $purok2a->id,
                'grupo' => 1,
                'gender' => 'male',
                'status' => 'MS',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'records')
            ->assertJsonPath('records.0.name', 'Male MS');

        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.members', [
                'purok_id' => $purok2a->id,
                'gender' => 'all',
                'status' => 'ALL',
            ]))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('records.0.name', 'Male MS');

        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.members', [
                'purok_id' => 'all',
                'gender' => 'all',
                'status' => 'ALL',
            ]))
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.matrix', ['purok_id' => $this->purok2b()->id, 'status' => 'UWP']))
            ->assertOk()
            ->assertJsonPath('rows.0.label', '2b-7')
            ->assertJsonPath('rows.0.female', 1);

        $purok2b = Purok::query()->where('purok_name', 'Purok 2b')->firstOrFail();
        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.members', [
                'purok_id' => $purok2b->id,
                'grupo' => 7,
                'gender' => 'female',
                'status' => 'UWP',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'records')
            ->assertJsonPath('records.0.name', 'Female UWP');

        $this->withSession(['login_user' => ['id' => 9001]])
            ->getJson(route('filter-bnh-helper.members', [
                'purok_id' => $purok2a->id,
                'grupo' => 1,
                'gender' => 'male',
                'status' => 'ALL',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'records')
            ->assertJsonPath('records.0.name', 'Male MS');

        $this->assertNotSame($male->id, $female->id);
        $this->assertNotSame($male->id, $inactive->id);
    }

    public function test_bnh_checks_qualified_totals_and_word_exports_work(): void
    {
        [$purok2a] = $this->makeSplitPuroks();
        $this->makeRecord([
            'full_name' => 'Other Purok UWP',
            'purok' => '2',
            'grupo' => '7',
            'gender' => 'Female',
            'status' => 'UWP',
        ]);
        $record = $this->makeRecord([
            'full_name' => 'Qualified Member',
            'purok' => '2',
            'grupo' => '1',
            'gender' => 'Male',
            'status' => 'MS',
            'is_valid1' => true,
            'is_valid2' => false,
        ]);
        $unqualified = $this->makeRecord([
            'full_name' => 'Unqualified Member',
            'purok' => '2',
            'grupo' => '2',
            'gender' => 'Female',
            'status' => 'MS',
            'is_valid1' => true,
            'is_valid2' => false,
        ]);

        $this->withSession(['login_user' => ['id' => 9002]])
            ->putJson(route('filter-bnh-helper.checks.update', $record), [
                'is_valid1' => 1,
                'is_valid2' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('qualified', true);

        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'is_valid1' => 1,
            'is_valid2' => 1,
        ]);

        $this->withSession(['login_user' => ['id' => 9002]])
            ->getJson(route('filter-bnh-helper.qualified-counts', ['purok_id' => $purok2a->id, 'status' => 'MS']))
            ->assertOk()
            ->assertJsonPath('rows.0.label', '2a-1')
            ->assertJsonPath('rows.0.male', 1)
            ->assertJsonPath('rows.0.total', 1);

        $this->withSession(['login_user' => ['id' => 9002]])
            ->getJson(route('filter-bnh-helper.bnh-records', ['purok_id' => $purok2a->id, 'status' => 'MS']))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('records.0.name', 'Qualified Member');

        $this->withSession(['login_user' => ['id' => 9002]])
            ->getJson(route('filter-bnh-helper.bnh-records', [
                'purok_id' => $purok2a->id,
                'status' => 'MS',
                'q' => 'Unqualified',
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('records.0.name', 'Unqualified Member');

        $listWord = $this->withSession(['login_user' => ['id' => 9002]])
            ->get(route('filter-bnh-helper.bnh-word', [
                'purok_id' => $purok2a->id,
                'status' => 'MS',
                'q' => 'Unqualified',
            ]));
        $listWord->assertOk()->assertHeader('content-type', 'application/msword; charset=UTF-8');
        $this->assertStringContainsString('Unqualified Member', $listWord->streamedContent());
        $this->assertStringNotContainsString('Qualified Member', $listWord->streamedContent());
        $this->assertStringNotContainsString('Other Purok UWP', $listWord->streamedContent());
        $this->assertStringNotContainsString('NOT QUALIFIED', $listWord->streamedContent());
        $this->assertStringNotContainsString('CHECK IN', $listWord->streamedContent());
        $this->assertStringContainsString('<span style="font-family: Wingdings">ü</span>', $listWord->streamedContent());
        $this->assertStringContainsString('<td class="center"><span style="font-family: Wingdings"></span></td>', $listWord->streamedContent());

        $qualifiedListWord = $this->withSession(['login_user' => ['id' => 9002]])
            ->get(route('filter-bnh-helper.bnh-word', [
                'purok_id' => $purok2a->id,
                'status' => 'MS',
                'q' => 'Qualified Member',
            ]));
        $this->assertStringContainsString('<td class="center"><span style="font-family: Wingdings">ü</span></td>', $qualifiedListWord->streamedContent());

        $countsWord = $this->withSession(['login_user' => ['id' => 9002]])
            ->get(route('filter-bnh-helper.counts-word', ['purok_id' => $purok2a->id, 'status' => 'MS']));
        $countsWord->assertOk();
        $this->assertStringContainsString('2a-1', $countsWord->streamedContent());
        $this->assertStringContainsString('BNH Helper Form Resumen', $countsWord->streamedContent());
        $this->assertStringContainsString('Total 2a', $countsWord->streamedContent());
        $this->assertStringNotContainsString('2b-7', $countsWord->streamedContent());
    }

    /**
     * @return array{0: Purok, 1: Purok}
     */
    private function makeSplitPuroks(): array
    {
        $first = Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2a',
            'grupo_start' => 1,
            'grupo_end' => 2,
            'full_purok' => false,
        ]);
        $second = Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2b',
            'grupo_start' => 7,
            'grupo_end' => 8,
            'full_purok' => false,
        ]);

        return [$first, $second];
    }

    private function makeRecord(array $attributes): Record
    {
        return Record::query()->create($attributes + [
            'registry' => 'REG-'.fake()->unique()->numerify('######'),
            'kapisanan' => 'K',
            'is_married' => false,
            'is_valid1' => false,
            'is_valid2' => false,
        ]);
    }

    private function purok2b(): Purok
    {
        return Purok::query()->where('purok_name', 'Purok 2b')->firstOrFail();
    }
}
