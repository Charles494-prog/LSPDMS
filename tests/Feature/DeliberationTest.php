<?php

namespace Tests\Feature;

use App\Models\Purok;
use App\Models\Record;
use App\Models\User;
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
            ->assertSee('Select Grupo');

        $unauthorized = $this->makeSecretary(['form_generator_allow' => false]);
        $this->withSession(['login_user' => ['id' => $unauthorized->id]])
            ->get(route('forms.monthly.deliberation'))
            ->assertForbidden();
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
            'log_section' => now()->timezone('Asia/Manila')->format('d/m/Y').': Added,',
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

    public function test_matching_inactive_record_can_only_be_reused_by_changing_its_status(): void
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
            ->assertOk()
            ->assertJsonPath('reused', true);

        $this->assertDatabaseHas('records', [
            'id' => $existing->id,
            'status' => 'UWP',
            'gender' => 'L',
            'purok' => '9',
            'grupo' => '7',
            'kapisanan' => 'K',
            'evangelist' => 'Original Evangelist',
        ]);
    }

    public function test_kapisanan_is_derived_from_marriage_and_exact_age_boundary(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());

        foreach ([
            ['Young Member', '2009-10-02', 'no', 'binhi'],
            ['Eighteen Member', '2008-10-01', 'no', 'K'],
            ['Married Member', '2009-10-02', 'yes', 'B'],
        ] as [$name, $birthDate, $married, $expectedKapisanan]) {
            $this->withSession(['login_user' => ['id' => $user->id]])
                ->postJson(route('forms.monthly.deliberation.store'), $this->validPayload([
                    'full_name' => $name,
                    'registry' => str_pad((string) (Record::query()->count() + 1), 13, '0', STR_PAD_LEFT),
                    'date_of_birth' => $birthDate,
                    'married' => $married,
                    'kapisanan' => 'Suliranin',
                ]))
                ->assertCreated();

            $this->assertDatabaseHas('records', [
                'full_name' => $name,
                'kapisanan' => $expectedKapisanan,
            ]);
        }
    }

    public function test_subtract_search_matches_name_and_registry_and_returns_readonly_record_details(): void
    {
        $user = $this->makeSecretary();
        $this->makePurok();
        Record::query()->create([
            'full_name' => 'Searchable Member',
            'registry' => 'ZXCVBNMASDFGH',
            'gender' => 'L',
            'purok' => '1',
            'grupo' => '2',
            'kapisanan' => 'K',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->getJson(route('forms.monthly.deliberation.subtract.search', ['q' => 'zxcv']))
            ->assertOk()
            ->assertJsonPath('records.0.full_name', 'Searchable Member')
            ->assertJsonPath('records.0.gender', 'Male')
            ->assertJsonPath('records.0.kapisanan', 'Kadiwa');
    }

    public function test_subtract_marks_ms_or_uwp_inactive_and_preserves_the_record_with_reason_code(): void
    {
        $user = $this->makeSecretary();
        $record = Record::query()->create([
            'full_name' => 'Member to Subtract',
            'registry' => 'ZXCVBNMASDFGH',
            'status' => 'UWP',
            'reason_of_delistment' => 'Earlier note',
            'log_section' => 'Imported CSV entry,',
        ]);
        $today = now()->timezone('Asia/Manila')->format('d/m/Y');

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
            'reason_of_delistment' => 'Earlier note'.PHP_EOL.'Deliberation subtract DD: Moved away',
            'log_section' => 'Imported CSV entry, '.$today.': Subtracted,',
        ]);
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
            'purok' => 1,
            'grupo' => 2,
            'kapisanan' => 'Kadiwa',
            'date_of_baptism' => '2020-01-01',
            'evangelist' => 'Evangelist Name',
            'status' => 'MS',
            'date_of_birth' => null,
            'married' => null,
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
