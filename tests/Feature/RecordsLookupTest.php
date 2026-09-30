<?php

namespace Tests\Feature;

use App\Models\Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordsLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_full_name_or_registry_and_translates_kapisanan(): void
    {
        Record::query()->create([
            'full_name' => 'Sample Buklod Person',
            'registry' => 'REG-2030-0001',
            'kapisanan' => 'B',
            'gender' => 'L',
            'purok' => '1',
            'grupo' => '3',
            'date_of_baptism' => '2020-02-03',
            'date_of_entry' => '2020-02-04',
            'r2_10_eligibility' => 'ELIGIBLE',
            'status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'Sample Kadiwa Person',
            'registry' => 'REG-2030-0002',
            'kapisanan' => 'K',
        ]);
        Record::query()->create([
            'full_name' => 'Sample Binhi Person',
            'registry' => 'REG-2030-0003',
            'kapisanan' => 'binhi',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.search', ['q' => 'SAMPLE BUKLOD']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Sample Buklod Person')
            ->assertJsonPath('data.0.kapisanan', 'Buklod')
            ->assertJsonPath('data.0.gender', 'Male')
            ->assertJsonPath('data.0.purok', '1')
            ->assertJsonPath('data.0.grupo', '3')
            ->assertJsonPath('data.0.date_of_baptism', '2020-02-03')
            ->assertJsonPath('data.0.date_of_entry', '2020-02-04')
            ->assertJsonPath('data.0.r2_10_eligibility', 'ELIGIBLE')
            ->assertJsonPath('data.0.status', 'MS');

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.search', ['q' => 'REG-2030-0002']))
            ->assertOk()
            ->assertJsonPath('data.0.kapisanan', 'Kadiwa');

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.search', ['q' => 'REG-2030-0003']))
            ->assertOk()
            ->assertJsonPath('data.0.kapisanan', 'binhi');
    }

    public function test_inquire_modify_page_uses_the_database_search_routes(): void
    {
        $this->withSession(['login_user' => ['id' => 1]])
            ->get(route('inquire-modify'))
            ->assertOk()
            ->assertSee('records\\/search', false)
            ->assertSee('records\\/smart-helper', false)
            ->assertSee('Smart Helper')
            ->assertSee('id="smart-helper-toggle"', false)
            ->assertSee('id="smart-helper-close"', false)
            ->assertSee('class="smart-helper-panel"', false)
            ->assertSee('Click Scan to check for duplicate records.')
            ->assertSee('Delete inactive record')
            ->assertSee('Form Generator > Monthly > Modify', false)
            ->assertSee('Record update guide')
            ->assertSee('none of the matching records is Inactive')
            ->assertSee('id="remind-open">Guidance</button>', false)
            ->assertDontSee('Naintindihan ko')
            ->assertSee('__RECORD_ID__', false)
            ->assertSee('<select id="rm-gender" required>', false)
            ->assertDontSee('PUROK_SPLITS')
            ->assertDontSee('DEMO_MEMBERS');
    }

    public function test_live_search_suggestions_narrow_as_name_or_registry_query_grows(): void
    {
        Record::query()->create([
            'full_name' => 'Juan Dela Cruz',
            'registry' => 'RZL0831000151',
        ]);
        Record::query()->create([
            'full_name' => 'Juanata Santos',
            'registry' => 'RZL0831000152',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.search', ['q' => 'jua']))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.search', ['q' => 'juana']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Juanata Santos');

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.search', ['q' => 'rzl08']))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Juan Dela Cruz')
            ->assertJsonPath('data.0.registry_number', 'RZL0831000151');
    }

    public function test_smart_helper_finds_duplicate_registries_and_similar_names_across_statuses(): void
    {
        Record::query()->create([
            'full_name' => 'Juan Dela Cruz',
            'registry' => 'REG-1001',
            'status' => 'MS',
            'kapisanan' => 'B',
        ]);
        Record::query()->create([
            'full_name' => 'Juan Dela Croz',
            'registry' => 'REG-1002',
            'status' => 'UWP',
            'kapisanan' => 'K',
        ]);
        Record::query()->create([
            'full_name' => 'Juan Dela Cruz',
            'registry' => 'REG-1003',
            'status' => 'UWP',
            'kapisanan' => 'K',
        ]);
        Record::query()->create([
            'full_name' => 'Maria Santos',
            'registry' => 'REG-2000',
        ]);
        Record::query()->create([
            'full_name' => 'Pedro Reyes',
            'registry' => 'REG-2000',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.smart-helper'))
            ->assertOk()
            ->assertJsonPath('records_scanned', 5)
            ->assertJsonFragment([
                'type' => 'duplicate_registry',
                'field' => 'registry',
                'match_value' => 'REG-2000',
            ])
            ->assertJsonFragment([
                'type' => 'duplicate_name',
                'field' => 'full_name',
                'match_value' => 'Juan Dela Cruz',
                'similarity' => 100,
            ])
            ->assertJsonFragment([
                'type' => 'similar_name',
                'field' => 'full_name',
                'similarity' => 92,
            ])
            ->assertJsonFragment([
                'name' => 'Juan Dela Cruz',
                'registry_number' => 'REG-1001',
                'status' => 'MS',
                'kapisanan' => 'Buklod',
            ])
            ->assertJsonFragment([
                'name' => 'Juan Dela Croz',
                'registry_number' => 'REG-1002',
                'status' => 'UWP',
                'kapisanan' => 'Kadiwa',
            ]);

        $this->assertDatabaseCount('records', 5);
    }

    public function test_smart_helper_does_not_flag_jr_sr_as_same_name_or_rfid_as_registry(): void
    {
        Record::query()->create([
            'full_name' => 'Winifredo Balomino Jr.',
            'registry' => 'RFID',
            'status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'Winifredo Balomino Sr',
            'registry' => 'RFID',
            'status' => 'UWP',
        ]);
        Record::query()->create([
            'full_name' => 'Maria Santos',
            'registry' => 'RZL08310001',
        ]);
        Record::query()->create([
            'full_name' => 'Pedro Reyes',
            'registry' => 'RZL08310002',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->getJson(route('records.smart-helper'))
            ->assertOk()
            ->assertJsonPath('records_scanned', 4)
            ->assertJsonCount(0, 'data');
    }

    public function test_inactive_duplicate_record_can_be_deleted_permanently(): void
    {
        $inactive = Record::query()->create([
            'full_name' => 'Inactive Duplicate',
            'registry' => 'REG-INACTIVE-1',
            'status' => 'INACTIVE',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->deleteJson(route('records.destroy', $inactive))
            ->assertOk()
            ->assertJsonPath('message', 'Inactive record deleted.');

        $this->assertDatabaseMissing('records', ['id' => $inactive->id]);
    }

    public function test_non_inactive_record_cannot_be_deleted_from_smart_helper(): void
    {
        $active = Record::query()->create([
            'full_name' => 'Active Duplicate',
            'registry' => 'REG-ACTIVE-1',
            'status' => 'MS',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->deleteJson(route('records.destroy', $active))
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'You can only delete this record after changing its status to Inactive in Form Generator > Monthly > Modify.'
            );

        $this->assertDatabaseHas('records', ['id' => $active->id, 'status' => 'MS']);
    }

    public function test_record_edits_are_saved_to_records_and_kapisanan_labels_are_stored_as_codes(): void
    {
        $record = Record::query()->create([
            'full_name' => 'Before Name',
            'registry' => 'REG-2030-0004',
            'gender' => 'L',
            'kapisanan' => 'B',
            'grupo' => '3',
            'date_of_baptism' => '2020-02-03',
            'evangelist' => 'Before Evangelist',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->putJson(route('records.update', $record), [
                'name' => 'After Name',
                'kapisanan' => 'Kadiwa',
                'gender' => 'Female',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'After Name')
            ->assertJsonPath('data.kapisanan', 'Kadiwa')
            ->assertJsonPath('data.gender', 'Female');

        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'full_name' => 'After Name',
            'kapisanan' => 'K',
            'registry' => 'REG-2030-0004',
            'gender' => 'B',
            'grupo' => '3',
            'evangelist' => 'Before Evangelist',
        ]);

        $this->withSession(['login_user' => ['id' => 1]])
            ->putJson(route('records.update', $record), ['gender' => 'Male'])
            ->assertOk()
            ->assertJsonPath('data.gender', 'Male');

        $this->assertDatabaseHas('records', [
            'id' => $record->id,
            'gender' => 'L',
        ]);
    }
}
