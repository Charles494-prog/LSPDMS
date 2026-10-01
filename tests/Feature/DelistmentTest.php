<?php

namespace Tests\Feature;

use App\Models\GlobalVariable;
use App\Models\DelistmentHistory;
use App\Models\Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelistmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_delisted_viewer_displays_history_reason_date_and_delisted_by(): void
    {
        $record = $this->record([
            'full_name' => 'Member in Delisted Viewer',
            'registry' => 'DELISTER123456',
            'status' => 'INACTIVE',
        ]);
        DelistmentHistory::query()->create([
            'record_id' => $record->id,
            'delisted_by' => 'Test Secretary',
            'reason' => 'Moved out of the locale',
            'delisted_at' => '2026-09-30 10:30:00',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('de-listment'))
            ->assertOk()
            ->assertSee('View delistment history')
            ->assertSee('id="delisted-history-dialog"', false)
            ->assertSee('<dialog', false)
            ->assertDontSee('<section class="delist-panel table-panel" aria-labelledby="delisted-records-heading">', false)
            ->assertSee('Member in Delisted Viewer')
            ->assertSee('DELISTER123456')
            ->assertSee('Moved out of the locale')
            ->assertSee('Test Secretary')
            ->assertSee('Sep 30, 2026 06:30 PM');
    }

    public function test_delistment_page_uses_live_eligible_active_records_and_required_date_gaps(): void
    {
        GlobalVariable::query()->create([
            'dateofbaptism_requirement_boolean' => true,
            'baptism_requirement' => '2025-12-25',
            'entry_years_requirement' => 1,
        ]);
        $eligible = $this->record([
            'full_name' => 'Eligible MS Member',
            'status' => 'MS',
            'r2_10_eligibility' => 'ELIGIBLE',
            'date_of_baptism' => '2025-12-25',
            'date_of_entry' => '2024-01-15',
        ]);
        $mk = $this->record([
            'full_name' => 'Automatic MK Member',
            'status' => 'MK',
            'r2_10_eligibility' => 'NO NEED TO CHECK',
            'r2_10_endorsement' => 'ENDORSED',
            'date_of_baptism' => '2020-01-01',
            'date_of_entry' => null,
        ]);
        $this->record([
            'full_name' => 'Inactive Member',
            'status' => 'INACTIVE',
            'r2_10_eligibility' => 'NOT APPLICABLE',
        ]);
        $notEligible = $this->record([
            'full_name' => 'Not Eligible UWP Member',
            'status' => 'UWP',
            'r2_10_eligibility' => 'NOT ELIGIBLE',
        ]);

        $this->withSession(['login_user' => ['id' => 1, 'first_name' => 'Charles Andrei', 'last_name' => 'Galvan']])
            ->get(route('de-listment'))
            ->assertOk()
            ->assertDontSee('Sample Member')
            ->assertSee('chart-members-dialog')
            ->assertSee('one-year-confirmed')
            ->assertSee('const PAGE_SIZE = 20;', false)
            ->assertSee('data-entry-years="1"', false)
            ->assertSee('id="delist-dialog"', false)
            ->assertSee('baptism date must be on or before the configured cutoff')
            ->assertDontSee('Date of entry</th>', false)
            ->assertDontSee('A date of entry is not needed here.')
            ->assertViewHas('members', function (array $members) use ($eligible, $mk): bool {
                $this->assertCount(3, $members);
                $byId = collect($members)->keyBy('id');
                $this->assertTrue($byId[$eligible->id]['eligible']);
                $this->assertTrue($byId[$mk->id]['eligible']);
                $this->assertTrue($byId[$mk->id]['reported']);

                return true;
            })
            ->assertViewHas('missingRecords', function (array $missing) use ($notEligible): bool {
                $this->assertCount(1, $missing);
                $this->assertSame([$notEligible->id], array_column($missing, 'id'));

                return true;
            });
    }

    public function test_dynamic_eligibility_uses_configured_entry_years_and_baptism_cutoff_while_mk_is_exempt(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        GlobalVariable::query()->create([
            'dateofbaptism_requirement_boolean' => true,
            'baptism_requirement' => '2025-12-25',
            'entry_years_requirement' => 2,
        ]);
        $meetsRequirements = $this->record([
            'full_name' => 'Dynamically Eligible',
            'status' => 'MS',
            'r2_10_eligibility' => 'NOT ELIGIBLE',
            'date_of_entry' => '2024-10-01',
            'date_of_baptism' => '2025-12-25',
        ]);
        $afterBaptismCutoff = $this->record([
            'full_name' => 'Baptized After Cutoff',
            'status' => 'UWP',
            'r2_10_eligibility' => 'ELIGIBLE',
            'date_of_entry' => '2024-10-01',
            'date_of_baptism' => '2025-12-26',
        ]);
        $tooRecent = $this->record([
            'full_name' => 'Too Recent',
            'status' => 'MS',
            'r2_10_eligibility' => 'ELIGIBLE',
            'date_of_entry' => '2025-10-02',
            'date_of_baptism' => '2025-12-25',
        ]);
        $mkWithoutDates = $this->record([
            'full_name' => 'MK Always Eligible',
            'status' => 'MK',
            'r2_10_eligibility' => 'NOT ELIGIBLE',
            'date_of_entry' => null,
            'date_of_baptism' => null,
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('de-listment'))
            ->assertOk()
            ->assertViewHas('members', function (array $members) use ($meetsRequirements, $afterBaptismCutoff, $tooRecent, $mkWithoutDates): bool {
                $byId = collect($members)->keyBy('id');
                $this->assertTrue($byId[$meetsRequirements->id]['eligible']);
                $this->assertFalse($byId[$afterBaptismCutoff->id]['eligible']);
                $this->assertFalse($byId[$tooRecent->id]['eligible']);
                $this->assertTrue($byId[$mkWithoutDates->id]['eligible']);

                return true;
            });
    }

    public function test_endorsement_uses_dynamic_eligibility_instead_of_imported_eligibility_text(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        GlobalVariable::query()->create([
            'dateofbaptism_requirement_boolean' => true,
            'baptism_requirement' => '2025-12-25',
            'entry_years_requirement' => 2,
        ]);
        $notQualified = $this->record([
            'status' => 'MS',
            'r2_10_eligibility' => 'ELIGIBLE',
            'date_of_entry' => '2025-10-01',
            'date_of_baptism' => '2025-12-25',
        ]);
        $qualified = $this->record([
            'status' => 'UWP',
            'r2_10_eligibility' => 'NOT ELIGIBLE',
            'date_of_entry' => '2024-10-01',
            'date_of_baptism' => '2025-12-25',
        ]);
        $user = ['id' => 1, 'first_name' => 'Charles', 'last_name' => 'Galvan'];

        $this->withSession(['login_user' => $user])
            ->putJson(route('de-listment.endorse', $notQualified), [
                'note' => 'Reviewed',
                'assurance' => true,
            ])
            ->assertUnprocessable();

        $this->withSession(['login_user' => $user])
            ->putJson(route('de-listment.endorse', $qualified), [
                'note' => 'Reviewed',
                'assurance' => true,
            ])
            ->assertOk();
    }

    public function test_only_endorsed_mk_can_be_delisted_and_history_and_record_log_are_saved(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        $user = \App\Models\User::query()->create([
            'gmail' => 'delister@example.test',
            'first_name' => 'Test',
            'last_name' => 'Secretary',
            'password' => 'password',
            'is_verified' => true,
            'Delistment_allow' => true,
        ]);
        $mk = $this->record([
            'full_name' => 'Endorsed MK',
            'status' => 'MK',
            'r2_10_endorsement' => 'ENDORSED',
            'log_section' => 'Existing entry,',
        ]);
        $notEndorsed = $this->record(['status' => 'MK']);
        $notMk = $this->record(['status' => 'MS', 'r2_10_endorsement' => 'ENDORSED']);

        foreach ([$notEndorsed, $notMk] as $record) {
            $this->withSession(['login_user' => ['id' => $user->id]])
                ->putJson(route('de-listment.delist', $record), ['reason' => 'Confirmed removal'])
                ->assertUnprocessable();
        }

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->putJson(route('de-listment.delist', $mk), ['reason' => 'Member requested removal'])
            ->assertOk()
            ->assertJsonPath('data.status', 'INACTIVE');

        $this->assertDatabaseHas('records', [
            'id' => $mk->id,
            'status' => 'INACTIVE',
            'log_section' => 'Existing entry, 01/10/2026: Delisted on October 01, 2026: Member requested removal,',
        ]);
        $this->assertDatabaseHas('delistment_histories', [
            'record_id' => $mk->id,
            'delisted_by_user_id' => $user->id,
            'delisted_by' => 'Test Secretary',
            'reason' => 'Member requested removal',
            'delisted_at' => now('Asia/Manila')->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseCount('delistment_histories', 1);
    }

    public function test_eligible_record_endorsement_persists_reason_and_appends_attributed_entry(): void
    {
        $record = $this->record([
            'full_name' => 'Endorsement Candidate',
            'status' => 'MS',
            'r2_10_eligibility' => 'ELIGIBLE',
            'reason_of_r2_10_endorsement' => 'Existing reason.',
            'log_section' => 'Existing log history.',
        ]);

        $this->withSession(['login_user' => ['id' => 1, 'first_name' => 'Charles Andrei', 'last_name' => 'Galvan']])
            ->putJson(route('de-listment.endorse', ['record' => $record->id]), [
                'note' => 'Reviewed and submitted with no changes.',
                'assurance' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.r2_10_endorsement', 'ENDORSED');

        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'r2_10_endorsement' => 'ENDORSED',
        ]);
        $this->assertStringContainsString('Existing reason.', $record->fresh()->reason_of_r2_10_endorsement);
        $this->assertMatchesRegularExpression(
            '/Charles Andrei Galvan \(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\): due to Reviewed and submitted with no changes\./',
            $record->fresh()->reason_of_r2_10_endorsement
        );
        $this->assertSame('Existing log history.', $record->fresh()->log_section);
    }

    public function test_ms_and_uwp_can_use_one_year_confirmation_but_mk_requires_a_description(): void
    {
        $user = ['id' => 1, 'first_name' => 'Charles Andrei', 'last_name' => 'Galvan'];
        $ms = $this->record([
            'status' => 'MS',
            'r2_10_eligibility' => 'ELIGIBLE',
        ]);
        $this->withSession(['login_user' => $user])
            ->putJson(route('de-listment.endorse', ['record' => $ms->id]), [
                'note' => null,
                'one_year_confirmed' => true,
                'assurance' => true,
            ])
            ->assertOk();
        $this->assertMatchesRegularExpression(
            '/Charles Andrei Galvan \(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\): 1 year MS or UWP\./',
            $ms->fresh()->reason_of_r2_10_endorsement
        );

        $mk = $this->record([
            'status' => 'MK',
            'r2_10_eligibility' => 'NO NEED TO CHECK',
        ]);
        $this->withSession(['login_user' => $user])
            ->putJson(route('de-listment.endorse', ['record' => $mk->id]), [
                'note' => null,
                'one_year_confirmed' => false,
                'assurance' => true,
            ])
            ->assertUnprocessable();
        $this->assertNull($mk->fresh()->r2_10_endorsement);

        $this->withSession(['login_user' => $user])
            ->putJson(route('de-listment.endorse', ['record' => $mk->id]), [
                'note' => 'Reviewed with the member.',
                'one_year_confirmed' => true,
                'assurance' => true,
            ])
            ->assertUnprocessable();
        $this->assertNull($mk->fresh()->r2_10_endorsement);

        $this->withSession(['login_user' => $user])
            ->putJson(route('de-listment.endorse', ['record' => $mk->id]), [
                'note' => 'Reviewed with the member.',
                'assurance' => true,
            ])
            ->assertOk();
        $this->assertStringContainsString('due to Reviewed with the member.', $mk->fresh()->reason_of_r2_10_endorsement);
    }

    public function test_endorsement_rejects_ineligible_inactive_and_already_endorsed_records(): void
    {
        $notEligible = $this->record([
            'status' => 'MS',
            'r2_10_eligibility' => 'NOT ELIGIBLE',
            'date_of_entry' => now()->subDays(2)->toDateString(),
        ]);
        $inactive = $this->record(['status' => 'INACTIVE', 'r2_10_eligibility' => 'ELIGIBLE']);
        $endorsed = $this->record([
            'status' => 'UWP',
            'r2_10_eligibility' => 'ELIGIBLE',
            'r2_10_endorsement' => 'ENDORSED',
        ]);

        foreach ([$notEligible, $inactive] as $record) {
            $this->withSession(['login_user' => ['id' => 1, 'first_name' => 'Charles', 'last_name' => 'Galvan']])
                ->putJson(route('de-listment.endorse', ['record' => $record->id]), [
                    'note' => 'Reviewed',
                    'assurance' => true,
                ])
                ->assertUnprocessable();
        }

        $this->withSession(['login_user' => ['id' => 1, 'first_name' => 'Charles', 'last_name' => 'Galvan']])
            ->putJson(route('de-listment.endorse', ['record' => $endorsed->id]), [
                'note' => 'Reviewed',
                'assurance' => true,
            ])
            ->assertStatus(409);
    }

    public function test_missing_baptism_dates_are_saved_for_active_records_and_future_dates_are_rejected(): void
    {
        $record = $this->record([
            'status' => 'UWP',
            'date_of_entry' => '2021-05-06',
        ]);

        $this->withSession(['login_user' => ['id' => 1, 'first_name' => 'Charles', 'last_name' => 'Galvan']])
            ->putJson(route('de-listment.update-dates'), [
                'changes' => [[
                    'id' => $record->id,
                    'baptism' => '2020-03-04',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.baptism', '2020-03-04');

        $this->assertSame('2020-03-04', $record->fresh()->date_of_baptism->format('Y-m-d'));
        $this->assertSame('2021-05-06', $record->fresh()->date_of_entry->format('Y-m-d'));

        $this->withSession(['login_user' => ['id' => 1, 'first_name' => 'Charles', 'last_name' => 'Galvan']])
            ->putJson(route('de-listment.update-dates'), [
                'changes' => [[
                    'id' => $record->id,
                    'baptism' => now()->addDay()->toDateString(),
                ]],
            ])
            ->assertUnprocessable();
    }

    private function record(array $attributes = []): Record
    {
        return Record::query()->create($attributes + [
            'full_name' => 'Test Record',
            'status' => 'MS',
            'r2_10_eligibility' => 'NOT ELIGIBLE',
            'date_of_entry' => '2020-01-01',
        ]);
    }
}
