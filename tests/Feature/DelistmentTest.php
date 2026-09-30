<?php

namespace Tests\Feature;

use App\Models\GlobalVariable;
use App\Models\Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelistmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_delistment_page_uses_live_eligible_active_records_and_required_date_gaps(): void
    {
        GlobalVariable::query()->create([
            'dateofbaptism_requirement_boolean' => true,
            'dateofentryrequirement_boolean' => true,
        ]);
        $eligible = $this->record([
            'full_name' => 'Eligible MS Member',
            'status' => 'MS',
            'r2_10_eligibility' => 'ELIGIBLE',
            'date_of_baptism' => null,
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
            ->assertSee('A baptism date may be required')
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
            ->assertViewHas('missingRecords', function (array $missing) use ($eligible, $notEligible): bool {
                $this->assertCount(2, $missing);
                $this->assertEqualsCanonicalizing(
                    [$eligible->id, $notEligible->id],
                    array_column($missing, 'id')
                );

                return true;
            });
    }

    public function test_eligible_record_endorsement_persists_reason_and_appends_attributed_entry(): void
    {
        $record = $this->record([
            'full_name' => 'Endorsement Candidate',
            'status' => 'MS',
            'r2_10_eligibility' => 'ELIGIBLE',
            'reason_of_delistment' => 'Existing reason.',
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
        $this->assertStringContainsString('Existing reason.', $record->fresh()->reason_of_delistment);
        $this->assertMatchesRegularExpression(
            '/Charles Andrei Galvan \(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\): due to Reviewed and submitted with no changes\./',
            $record->fresh()->reason_of_delistment
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
            $ms->fresh()->reason_of_delistment
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
        $this->assertStringContainsString('due to Reviewed with the member.', $mk->fresh()->reason_of_delistment);
    }

    public function test_endorsement_rejects_ineligible_inactive_and_already_endorsed_records(): void
    {
        $notEligible = $this->record(['status' => 'MS', 'r2_10_eligibility' => 'NOT ELIGIBLE']);
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
        ]);
    }
}
