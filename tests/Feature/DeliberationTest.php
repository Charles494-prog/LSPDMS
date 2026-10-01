<?php

namespace Tests\Feature;

use App\Models\Purok;
use App\Models\Record;
use App\Models\User;
use App\Models\MonthlyRecordCodeHistory;
use App\Models\MonthlyRecordMovementHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliberationTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_is_protected_and_shows_registry_boxes_and_rfid_option(): void
    {
        $user = $this->makeSecretary();
        Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Central',
            'grupo_start' => 1,
            'grupo_end' => 3,
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('forms.monthly.deliberation'))
            ->assertOk()
            ->assertSee('Deliberation Form')
            ->assertSee('data-registry-character="12"', false)
            ->assertSee('This member has RFID instead of a registry number')
            ->assertSee('Select Grupo')
            ->assertSee('>Central (1-3)</option>', false)
            ->assertDontSee('1 - Central', false)
            ->assertSee('id="delib-description"', false)
            ->assertDontSee('<option value="E">E</option>', false)
            ->assertSee('new Map(result.records.map(record => [record.id, record]))', false)
            ->assertSee('activeSubtractSearch?.abort()', false);

        $unauthorized = $this->makeSecretary(['form_generator_allow' => false]);
        $this->withSession(['login_user' => ['id' => $unauthorized->id]])
            ->get(route('forms.monthly.deliberation'))
            ->assertForbidden();
    }

    public function test_monthly_modify_can_change_status_only_and_requires_mk_demotion_confirmation(): void
    {
        $user = $this->makeSecretary();
        $purok = Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Purok 1',
            'grupo_start' => 1,
            'grupo_end' => 24,
            'full_purok' => true,
        ]);
        $member = Record::query()->create([
            'full_name' => 'MK Status Member',
            'status' => 'MK',
            'monthly_code' => null,
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'status',
                'status' => 'MS',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmed_mk_demotion');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'status',
                'status' => 'MS',
                'purok_id' => $purok->id,
                'grupo' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'status',
                'status' => 'MS',
                'confirmed_mk_demotion' => true,
            ])
            ->assertOk()
            ->assertJsonPath('record.status', 'MS');

        $this->assertDatabaseHas('records', [
            'id' => $member->id,
            'status' => 'MS',
            'purok' => null,
            'grupo' => null,
            'monthly_code' => 'MS: B',
        ]);
        $this->assertDatabaseHas('monthly_record_movement_histories', [
            'record_id' => $member->id,
            'full_name' => 'MK Status Member',
            'status' => 'MK',
            'monthly_code' => 'MK: G',
            'destination_status' => 'MS',
            'previous_purok' => null,
            'previous_grupo' => null,
        ]);
    }

    public function test_monthly_modify_status_change_records_previous_code_and_sets_new_status_code(): void
    {
        $user = $this->makeSecretary();
        $member = Record::query()->create([
            'full_name' => 'Juan Dela Cruz',
            'registry' => 'JUAN123456789',
            'gender' => 'M',
            'kapisanan' => 'K',
            'date_of_baptism' => '2001-02-03',
            'evangelist' => 'Juan Evangelist',
            'purok' => '1',
            'grupo' => '1',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'status',
                'status' => 'UWP',
            ])
            ->assertOk()
            ->assertJsonPath('record.status', 'UWP')
            ->assertJsonPath('record.monthly_code', 'UWP: B')
            ->assertJsonPath('message', 'Status updated. Current monthly code: UWP: B; previous status movement is recorded in history.');

        $this->assertDatabaseHas('records', [
            'id' => $member->id,
            'full_name' => 'Juan Dela Cruz',
            'purok' => '1',
            'grupo' => '1',
            'status' => 'UWP',
            'monthly_code' => 'UWP: B',
        ]);
        $this->assertDatabaseHas('monthly_record_movement_histories', [
            'record_id' => $member->id,
            'full_name' => 'Juan Dela Cruz',
            'registry' => 'JUAN123456789',
            'gender' => 'M',
            'kapisanan' => 'K',
            'evangelist' => 'Juan Evangelist',
            'previous_purok' => '1',
            'previous_grupo' => '1',
            'status' => 'MS',
            'monthly_code' => 'MS: G',
            'destination_status' => 'UWP',
        ]);
        $history = MonthlyRecordMovementHistory::query()->where('record_id', $member->id)->firstOrFail();
        $this->assertSame('2001-02-03', $history->date_of_baptism?->format('Y-m-d'));
        $deliberatedAt = now()->timezone('Asia/Manila')->toDateString();
        $this->assertDatabaseHas('monthly_record_code_histories', [
            'record_id' => $member->id,
            'full_name' => 'Juan Dela Cruz',
            'registry' => 'JUAN123456789',
            'gender' => 'M',
            'kapisanan' => 'K',
            'evangelist' => 'Juan Evangelist',
            'purok' => '1',
            'grupo' => '1',
            'status' => 'MS',
            'monthly_code' => 'MS: G',
        ]);
        $this->assertDatabaseHas('monthly_record_code_histories', [
            'record_id' => $member->id,
            'full_name' => 'Juan Dela Cruz',
            'registry' => 'JUAN123456789',
            'gender' => 'M',
            'kapisanan' => 'K',
            'evangelist' => 'Juan Evangelist',
            'purok' => '1',
            'grupo' => '1',
            'status' => 'UWP',
            'monthly_code' => 'UWP: B',
        ]);
        $codeHistory = \App\Models\MonthlyRecordCodeHistory::query()
            ->where('record_id', $member->id)
            ->orderBy('id')
            ->get();
        $this->assertSame(['MS: G', 'UWP: B'], $codeHistory->pluck('monthly_code')->all());
        $this->assertSame(['2001-02-03', '2001-02-03'], $codeHistory->map(
            fn ($entry): ?string => $entry->date_of_baptism?->format('Y-m-d')
        )->all());
        $this->assertSame([$deliberatedAt, $deliberatedAt], $codeHistory->map(
            fn ($entry): string => $entry->deliberated_at->format('Y-m-d')
        )->all());
    }

    public function test_monthly_modify_to_mk_stores_previous_e_code_and_leaves_record_monthly_code_empty(): void
    {
        $user = $this->makeSecretary();
        $member = Record::query()->create([
            'full_name' => 'UWP to MK Member',
            'purok' => '2',
            'grupo' => '4',
            'status' => 'UWP',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'status',
                'status' => 'MK',
                'endorsement_reason' => 'Reviewed for R2-10.',
            ])
            ->assertOk()
            ->assertJsonPath('record.status', 'MK')
            ->assertJsonPath('record.monthly_code', null)
            ->assertJsonPath('message', 'Status updated to MK. Previous status movement is recorded in history.');

        $this->assertDatabaseHas('records', [
            'id' => $member->id,
            'status' => 'MK',
            'monthly_code' => null,
        ]);
        $this->assertStringNotContainsString('Reviewed for R2-10.', $member->fresh()->log_section);
        $this->assertStringContainsString(
            now()->timezone('Asia/Manila')->format('d-m-Y').': (Reviewed for R2-10.)',
            $member->fresh()->reason_of_r2_10_endorsement
        );
        $this->assertDatabaseHas('monthly_record_movement_histories', [
            'record_id' => $member->id,
            'status' => 'UWP',
            'previous_purok' => '2',
            'previous_grupo' => '4',
            'monthly_code' => 'UWP: E',
            'destination_status' => 'MK',
        ]);
        $this->assertDatabaseHas('monthly_record_code_histories', [
            'record_id' => $member->id,
            'status' => 'UWP',
            'monthly_code' => 'UWP: E',
        ]);
        $this->assertDatabaseCount('monthly_record_code_histories', 1);
    }

    public function test_monthly_modify_only_accepts_active_records_without_monthly_code_and_disallows_grupo_only_moves(): void
    {
        $user = $this->makeSecretary();
        $source = Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Purok 1',
            'grupo_start' => 1,
            'grupo_end' => 24,
            'full_purok' => true,
        ]);
        $destination = Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2',
            'grupo_start' => 1,
            'grupo_end' => 6,
        ]);
        $member = Record::query()->create([
            'full_name' => 'Current Transfer Member',
            'purok' => '1',
            'grupo' => '2',
            'status' => 'UWP',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $source->id,
                'grupo' => 3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('purok_id');

        $member->monthly_code = 'UWP: B';
        $member->save();
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $destination->id,
                'grupo' => 3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('record_id');

        $member->monthly_code = null;
        $member->status = 'INACTIVE';
        $member->save();
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->getJson(route('forms.monthly.modify.search', ['q' => 'Current Transfer']))
            ->assertOk()
            ->assertJsonPath('records.0.status', 'INACTIVE');
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'status',
                'status' => 'MS',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('record_id');
    }

    public function test_subtract_blocks_records_with_existing_monthly_code(): void
    {
        $user = $this->makeSecretary();
        $record = Record::query()->create([
            'full_name' => 'Already Moved Member',
            'status' => 'UWP',
            'monthly_code' => 'UWP: C+',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.subtract'), [
                'record_id' => $record->id,
                'code' => 'D',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This record already has a monthly movement code. Clear the previous code in Inquire / Modify before proceeding.');

        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'status' => 'UWP',
            'monthly_code' => 'UWP: C+',
        ]);
    }

    public function test_monthly_modify_page_is_protected_and_offers_configured_puroks(): void
    {
        $user = $this->makeSecretary();
        Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2a',
            'grupo_start' => 1,
            'grupo_end' => 6,
        ]);
        Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2b',
            'grupo_start' => 7,
            'grupo_end' => 11,
        ]);
        Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Purok Uno',
            'grupo_start' => 1,
            'grupo_end' => 24,
            'full_purok' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('forms.monthly.modify'))
            ->assertOk()
            ->assertSee('Monthly Modify')
            ->assertSee('Purok 2a')
            ->assertSee('Purok 2b')
            ->assertSee('Purok Uno (1-24)')
            ->assertSee('Purok 2a (1-6)')
            ->assertSee('Purok 2b (7-11)')
            ->assertSee('id="monthlyModifyEndorsementReason"', false)
            ->assertSee('id="monthlyModifyConfirmDialog"', false)
            ->assertSee('id="monthlyModifySuccessDialog"', false)
            ->assertSee('resetModifyForm = () =>', false)
            ->assertDontSee('all Grupos')
            ->assertSee(route('forms.monthly.modify.search'), false);

        $unauthorized = $this->makeSecretary(['form_generator_allow' => false]);
        $this->withSession(['login_user' => ['id' => $unauthorized->id]])
            ->get(route('forms.monthly.modify'))
            ->assertForbidden();
    }

    public function test_add_uses_the_selected_split_purok_grupo_range(): void
    {
        $user = $this->makeSecretary();
        Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2a',
            'grupo_start' => 1,
            'grupo_end' => 6,
        ]);
        $purok2b = Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2b',
            'grupo_start' => 7,
            'grupo_end' => 11,
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('forms.monthly.deliberation'))
            ->assertOk()
            ->assertSee('Purok 2a (1-6)')
            ->assertSee('Purok 2b (7-11)');

        $payload = $this->validPayload([
            'full_name' => 'Purok 2b Member',
            'registry' => 'PUROK2B123456',
            'purok_id' => $purok2b->id,
            'grupo' => 8,
        ]);
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('records', [
            'full_name' => 'Purok 2b Member',
            'purok' => '2',
            'grupo' => '8',
        ]);

        $payload['full_name'] = 'Invalid Purok 2b Member';
        $payload['registry'] = 'PUROK2B123457';
        $payload['grupo'] = 6;
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grupo');
    }

    public function test_monthly_modify_limits_complete_purok_to_grupos_one_through_twenty_four(): void
    {
        $user = $this->makeSecretary();
        Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2',
            'grupo_start' => 1,
            'grupo_end' => 24,
            'full_purok' => true,
        ]);
        $completePurok = Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Purok Uno',
            'grupo_start' => 1,
            'grupo_end' => 24,
            'full_purok' => true,
        ]);
        $member = Record::query()->create([
            'full_name' => 'Complete Purok Member',
            'purok' => '2',
            'grupo' => '1',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $completePurok->id,
                'grupo' => 25,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grupo');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $completePurok->id,
                'grupo' => 24,
            ])
            ->assertOk()
            ->assertJsonPath('record.grupo', '24');
    }

    public function test_monthly_modify_uses_each_configured_range_even_if_complete_flag_is_set(): void
    {
        $user = $this->makeSecretary();
        $source = Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Purok 1',
            'grupo_start' => 1,
            'grupo_end' => 24,
            'full_purok' => true,
        ]);
        $purok3 = Purok::query()->create([
            'purok_number' => 3,
            'purok_name' => 'Purok 3',
            'grupo_start' => 1,
            'grupo_end' => 11,
            'full_purok' => true,
        ]);
        Purok::query()->create([
            'purok_number' => 4,
            'purok_name' => 'Purok 4',
            'grupo_start' => 1,
            'grupo_end' => 9,
            'full_purok' => true,
        ]);
        $member = Record::query()->create([
            'full_name' => 'Configured Range Transfer',
            'purok' => (string) $source->purok_number,
            'grupo' => '2',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('forms.monthly.modify'))
            ->assertOk()
            ->assertSee('Purok 3 (1-11)')
            ->assertSee('Purok 4 (1-9)')
            ->assertDontSee('Purok 3 (1-24)')
            ->assertDontSee('Purok 4 (1-24)');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $purok3->id,
                'grupo' => 12,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grupo');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $purok3->id,
                'grupo' => 11,
            ])
            ->assertOk()
            ->assertJsonPath('record.grupo', '11');
    }

    public function test_monthly_modify_moves_member_within_split_destination_range_and_appends_log(): void
    {
        $user = $this->makeSecretary();
        Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Purok 1',
            'grupo_start' => 1,
            'grupo_end' => 12,
        ]);
        $destination = Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2b',
            'grupo_start' => 7,
            'grupo_end' => 11,
        ]);
        $member = Record::query()->create([
            'full_name' => 'Monthly Transfer Member',
            'registry' => 'TRANSFER12345',
            'gender' => 'F',
            'kapisanan' => 'B',
            'date_of_baptism' => '1998-07-09',
            'evangelist' => 'Maria Evangelist',
            'purok' => '1',
            'grupo' => '4',
            'status' => 'MS',
            'log_section' => '01/01/2025: Existing entry,',
        ]);
        $today = now()->timezone('Asia/Manila')->format('d/m/Y');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->getJson(route('forms.monthly.modify.search', ['q' => 'Monthly Transfer']))
            ->assertOk()
            ->assertJsonPath('records.0.name', 'Monthly Transfer Member')
            ->assertJsonPath('records.0.purok_name', 'Purok 1');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $destination->id,
                'grupo' => 6,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grupo');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $member->id,
                'action' => 'transfer',
                'purok_id' => $destination->id,
                'grupo' => 8,
                'description' => 'Approved transfer',
            ])
            ->assertOk()
            ->assertJsonPath('record.purok', '2')
            ->assertJsonPath('record.grupo', '8');

        $this->assertDatabaseHas('records', [
            'id' => $member->id,
            'purok' => '2',
            'grupo' => '8',
            'status' => 'MS',
            'monthly_code' => 'MS: C+',
            'log_section' => '01/01/2025: Existing entry, '.$today.': Monthly modify: Purok 1 Grupo 4 to Purok 2b Grupo 8 (MS: C- / MS: C+): Approved transfer,',
        ]);
        $this->assertDatabaseHas('monthly_record_movement_histories', [
            'record_id' => $member->id,
            'full_name' => 'Monthly Transfer Member',
            'registry' => 'TRANSFER12345',
            'previous_purok' => '1',
            'previous_grupo' => '4',
            'status' => 'MS',
            'monthly_code' => 'MS: C-',
            'destination_status' => 'MS',
            'gender' => 'F',
            'kapisanan' => 'B',
            'evangelist' => 'Maria Evangelist',
        ]);
        $history = MonthlyRecordMovementHistory::query()->where('record_id', $member->id)->firstOrFail();
        $this->assertSame('1998-07-09', $history->date_of_baptism?->format('Y-m-d'));
        $this->assertDatabaseHas('monthly_record_code_histories', [
            'record_id' => $member->id,
            'full_name' => 'Monthly Transfer Member',
            'registry' => 'TRANSFER12345',
            'gender' => 'F',
            'kapisanan' => 'B',
            'evangelist' => 'Maria Evangelist',
            'purok' => '1',
            'grupo' => '4',
            'status' => 'MS',
            'monthly_code' => 'MS: C-',
        ]);
        $this->assertDatabaseHas('monthly_record_code_histories', [
            'record_id' => $member->id,
            'full_name' => 'Monthly Transfer Member',
            'registry' => 'TRANSFER12345',
            'gender' => 'F',
            'kapisanan' => 'B',
            'evangelist' => 'Maria Evangelist',
            'purok' => '2',
            'grupo' => '8',
            'status' => 'MS',
            'monthly_code' => 'MS: C+',
        ]);
        $codeHistory = \App\Models\MonthlyRecordCodeHistory::query()
            ->where('record_id', $member->id)
            ->orderBy('id')
            ->get();
        $this->assertSame(['MS: C-', 'MS: C+'], $codeHistory->pluck('monthly_code')->all());
        $this->assertSame(['1998-07-09', '1998-07-09'], $codeHistory->map(
            fn ($entry): ?string => $entry->date_of_baptism?->format('Y-m-d')
        )->all());
        $this->assertSame([
            now()->timezone('Asia/Manila')->toDateString(),
            now()->timezone('Asia/Manila')->toDateString(),
        ], $codeHistory->map(fn ($entry): string => $entry->deliberated_at->format('Y-m-d'))->all());
    }

    public function test_registry_must_be_exactly_thirteen_characters_unless_rfid_is_selected(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $payload = $this->validPayload(['registry' => '12345']);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('registry');

        $payload = $this->validPayload([
            'registry' => 'RFID',
            'is_rfid' => true,
            'full_name' => 'RFID Member',
        ]);
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('record.registry', 'RFID');
    }

    public function test_duplicate_names_and_registry_values_are_reported_and_cannot_be_added_again(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        Record::query()->create([
            'full_name' => 'Existing Member',
            'registry' => 'ABCDEFGHIJKLM',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.duplicates'), [
                'full_name' => ' existing member ',
                'registry' => '',
                'is_rfid' => false,
            ])
            ->assertOk()
            ->assertJsonPath('duplicates.0.name', 'Existing Member');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'full_name' => 'Another Name',
                'registry' => 'ABCDEFGHIJKLM',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('full_name');
    }

    public function test_rfid_is_not_used_for_registry_duplicate_matching(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        Record::query()->create([
            'full_name' => 'Other RFID Member',
            'registry' => 'RFID',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'full_name' => 'New RFID Member',
                'registry' => 'RFID',
                'is_rfid' => true,
            ]))
            ->assertCreated();
    }

    public function test_add_accepts_missing_baptism_date_evangelist_and_grupo(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'full_name' => 'Optional Information Member',
                'registry' => 'ABCDEFGHIJKLM',
                'grupo' => null,
                'date_of_baptism' => null,
                'evangelist' => null,
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('records', [
            'full_name' => 'Optional Information Member',
            'purok' => '1',
            'grupo' => null,
            'date_of_baptism' => null,
            'evangelist' => null,
            'gender' => 'L',
            'kapisanan' => 'K',
            'monthly_code' => 'MS: B',
            'log_section' => now()->timezone('Asia/Manila')->format('d-m-Y').': MS: B,',
        ]);
        $this->assertSame(
            '2026-10-01',
            Record::query()->where('full_name', 'Optional Information Member')->firstOrFail()->date_of_entry->format('Y-m-d')
        );
    }

    public function test_add_sets_monthly_code_from_the_selected_status(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());

        foreach (['MS', 'UWP', 'MK'] as $status) {
            $name = "{$status} Monthly Code Member";
            $this->withSession(['login_user' => ['id' => $user->id]])
                ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                    'full_name' => $name,
                    'registry' => str_pad((string) (Record::query()->count() + 1), 13, '0', STR_PAD_LEFT),
                    'status' => $status,
                ]))
                ->assertCreated();

            $this->assertDatabaseHas('records', [
                'full_name' => $name,
                'status' => $status,
                'monthly_code' => "{$status}: B",
            ]);
            $this->assertSame(
                '2026-10-01',
                Record::query()->where('full_name', $name)->firstOrFail()->date_of_entry->format('Y-m-d')
            );
        }
    }

    public function test_add_appends_optional_description_to_log_section(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $today = now()->timezone('Asia/Manila')->format('d-m-Y');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'description' => 'Added after review',
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('records', [
            'full_name' => 'New Member',
            'log_section' => $today.': MS: B, '.$today.': Added after review,',
        ]);
    }

    public function test_add_persists_female_and_buklod_codes_and_rfid_value(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'full_name' => 'Female RFID Buklod',
                'registry' => 'RFID',
                'is_rfid' => true,
                'gender' => 'Female',
                'kapisanan' => 'Buklod',
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('records', [
            'full_name' => 'Female RFID Buklod',
            'registry' => 'RFID',
            'gender' => 'B',
            'kapisanan' => 'B',
        ]);
    }

    public function test_inactive_matching_record_can_be_found_but_not_reused_or_changed(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $existing = Record::query()->create([
            'full_name' => 'Inactive Member',
            'registry' => 'ABCDEFGHIJKLM',
            'status' => 'INACTIVE',
            'gender' => 'L',
            'purok' => '9',
            'grupo' => '7',
            'kapisanan' => 'K',
            'date_of_baptism' => '2000-01-01',
            'evangelist' => 'Original Evangelist',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'full_name' => 'Inactive Member',
                'registry' => 'ABCDEFGHIJKLM',
                'status' => 'UWP',
                'reuse_record_id' => $existing->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reuse_record_id');

        $this->assertDatabaseHas('records', [
            'id' => $existing->id,
            'status' => 'INACTIVE',
            'monthly_code' => null,
            'gender' => 'L',
            'purok' => '9',
            'grupo' => '7',
            'kapisanan' => 'K',
            'evangelist' => 'Original Evangelist',
        ]);
    }

    public function test_add_rejects_inactive_as_a_new_status_and_blocks_records_with_existing_monthly_codes(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload(['status' => 'INACTIVE']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        Record::query()->create([
            'full_name' => 'Already Deliberated Member',
            'registry' => 'ABCDEFGHIJKLM',
            'status' => 'MS',
            'monthly_code' => 'MS: B',
        ]);
        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'full_name' => 'Already Deliberated Member',
                'registry' => 'ABCDEFGHIJKLM',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('full_name');
    }

    public function test_kapisanan_is_derived_from_marriage_and_exact_age_boundary(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());

        foreach ([
            ['Young Member', '2009-10-02', 0, 'binhi'],
            ['Eighteen Member', '2008-10-01', 0, 'K'],
            ['Married Member', '2009-10-02', 1, 'B'],
        ] as [$name, $birthDate, $isMarried, $expectedKapisanan]) {
            $this->withSession(['login_user' => ['id' => $user->id]])
                ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                    'full_name' => $name,
                    'registry' => str_pad((string) (Record::query()->count() + 1), 13, '0', STR_PAD_LEFT),
                    'date_of_birth' => $birthDate,
                    'is_married' => $isMarried,
                    'kapisanan' => 'Suliranin',
                ]))
                ->assertCreated();

            $this->assertDatabaseHas('records', [
                'full_name' => $name,
                'kapisanan' => $expectedKapisanan,
                'is_married' => $isMarried,
            ]);
        }
    }

    public function test_monthly_add_defaults_unanswered_marital_status_to_not_married(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $payload = $this->validPayload([
            'full_name' => 'Default Not Married Member',
            'registry' => 'DEFAULT000001',
            'date_of_birth' => '2000-01-01',
        ]);
        unset($payload['is_married']);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('records', [
            'full_name' => 'Default Not Married Member',
            'is_married' => 0,
            'kapisanan' => 'K',
        ]);
    }

    public function test_monthly_add_accepts_only_binary_marital_values(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                'is_married' => 'yes',
            ]))
            ->assertUnprocessable()
                ->assertJsonValidationErrors('is_married');
    }

    public function test_monthly_add_marks_buklod_as_married_even_without_birthday(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();

        $this->withSession(['login_user' => ['id' => $user->id]])
                ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                    'full_name' => 'Buklod Marriage Member',
                    'date_of_birth' => null,
                    'is_married' => 0,
                    'kapisanan' => 'Buklod',
                ]))
                ->assertCreated();

        $this->assertDatabaseHas('records', [
                'full_name' => 'Buklod Marriage Member',
                'kapisanan' => 'B',
                'is_married' => 1,
        ]);
    }

    public function test_subtract_search_matches_name_and_registry_and_returns_readonly_record_details(): void
    {
        $user = $this->makeSecretary();
        Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2a',
            'grupo_start' => 1,
            'grupo_end' => 6,
        ]);
        Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2b',
            'grupo_start' => 7,
            'grupo_end' => 11,
        ]);
        Record::query()->create([
            'full_name' => 'Searchable Member',
            'registry' => 'ZXCVBNMASDFGH',
            'gender' => 'L',
            'purok' => '2',
            'grupo' => '8',
            'kapisanan' => 'K',
            'status' => 'uwp',
            'monthly_code' => 'UWP: D',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->getJson(route('forms.monthly.deliberation.subtract.search', ['q' => 'zxcv']))
            ->assertOk()
            ->assertJsonPath('records.0.full_name', 'Searchable Member')
            ->assertJsonPath('records.0.gender', 'Male')
            ->assertJsonPath('records.0.kapisanan', 'Kadiwa')
            ->assertJsonPath('records.0.status', 'UWP')
            ->assertJsonPath('records.0.monthly_code', 'UWP: D')
            ->assertJsonPath('records.0.purok_name', 'Purok 2b');
    }

    public function test_subtract_search_returns_a_record_once_when_both_name_and_registry_match(): void
    {
        $user = $this->makeSecretary();
        Record::query()->create([
            'full_name' => 'Alex Alex Member',
            'registry' => 'ALEX123456789',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->getJson(route('forms.monthly.deliberation.subtract.search', ['q' => 'alex']))
            ->assertOk()
            ->assertJsonCount(1, 'records')
            ->assertJsonPath('records.0.full_name', 'Alex Alex Member');
    }

    public function test_subtract_marks_ms_or_uwp_inactive_and_preserves_the_record_with_reason_code(): void
    {
        $user = $this->makeSecretary();
        $record = Record::query()->create([
            'full_name' => 'Member to Subtract',
            'registry' => 'ZXCVBNMASDFGH',
            'status' => 'UWP',
            'reason_of_r2_10_endorsement' => 'Earlier note',
            'log_section' => 'Imported CSV entry,',
        ]);
        $today = now()->timezone('Asia/Manila')->format('d-m-Y');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.subtract'), [
                'record_id' => $record->id,
                'code' => 'DD',
                'description' => 'Moved away',
            ])
            ->assertOk()
            ->assertJsonPath('record.status', 'INACTIVE');

        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'status' => 'INACTIVE',
            'monthly_code' => 'UWP: DD',
            'reason_of_r2_10_endorsement' => 'Earlier note'.PHP_EOL.'Deliberation subtract DD: Moved away',
            'log_section' => 'Imported CSV entry, '.$today.': UWP: DD, '.$today.': Moved away,',
        ]);
    }

    public function test_subtract_monthly_code_uses_previous_status_and_selected_removal_code(): void
    {
        $user = $this->makeSecretary();
        foreach (['MS' => 'D', 'UWP' => 'DD'] as $status => $code) {
            $record = Record::query()->create([
                'full_name' => "{$status} Member",
                'status' => $status,
            ]);

            $this->withSession(['login_user' => ['id' => $user->id]])
                ->postJson(route('forms.monthly.deliberation.subtract'), [
                    'record_id' => $record->id,
                    'code' => $code,
                ])
                ->assertOk();

            $this->assertDatabaseHas('records', [
                'id' => $record->id,
                'status' => 'INACTIVE',
                'monthly_code' => "{$status}: {$code}",
            ]);
        }
    }

    public function test_monthly_modify_to_mk_requires_and_saves_an_r2_10_reason(): void
    {
        $user = $this->makeSecretary();
        $record = Record::query()->create([
            'full_name' => 'Modify MK Reason Candidate',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $record->id,
                'action' => 'status',
                'status' => 'MK',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endorsement_reason');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.subtract'), [
                'record_id' => $record->id,
                'code' => 'E',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.modify.save'), [
                'record_id' => $record->id,
                'action' => 'status',
                'status' => 'MK',
                'endorsement_reason' => 'Reviewed for R2-10.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'status' => 'MK',
            'monthly_code' => null,
        ]);
        $this->assertStringContainsString(
            now()->timezone('Asia/Manila')->format('d-m-Y').': (Reviewed for R2-10.)',
            $record->fresh()->reason_of_r2_10_endorsement
        );
    }

    public function test_subtract_rejects_inactive_and_mk_records(): void
    {
        $user = $this->makeSecretary();
        $inactive = Record::query()->create(['full_name' => 'Already Inactive', 'status' => 'INACTIVE']);
        $mk = Record::query()->create(['full_name' => 'MK Member', 'status' => 'MK']);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.subtract'), [
                'record_id' => $inactive->id,
                'code' => 'D',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Already Inactive.');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->postJson(route('forms.monthly.deliberation.subtract'), [
                'record_id' => $mk->id,
                'code' => 'H',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'MK is not removable.');

        $this->assertDatabaseHas('records', ['id' => $inactive->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('records', ['id' => $mk->id, 'status' => 'MK']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'full_name' => 'New Member',
            'registry' => '1234567890123',
            'is_rfid' => false,
            'gender' => 'Male',
            'purok_id' => 1,
            'grupo' => 2,
            'kapisanan' => 'Kadiwa',
            'date_of_baptism' => '2020-01-01',
            'evangelist' => 'Evangelist Name',
            'status' => 'MS',
            'date_of_birth' => null,
            'is_married' => 0,
            'description' => null,
        ], $overrides);
    }

    private function makePurok(): Purok
    {
        return Purok::query()->create([
            'purok_number' => 1,
            'purok_name' => 'Central',
            'grupo_start' => 1,
            'grupo_end' => 3,
        ]);
    }

    private function makeSecretary(array $attributes = []): User
    {
        return User::query()->create($attributes + [
            'gmail' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
            'form_generator_allow' => true,
        ]);
    }
}
