<?php

namespace Tests\Feature;

use App\Models\Record;
use App\Models\RecordAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_edits_log_the_signed_in_email_and_before_and_after_values(): void
    {
        $record = Record::query()->create([
            'full_name' => 'Charles Andrei Galvan',
            'registry' => 'RFID',
            'status' => 'MS',
        ]);

        $this->withSession([
            'login_user' => [
                'id' => 1,
                'gmail' => 'formalcharlesandreigalvan@gmail.com',
            ],
        ])
            ->putJson(route('records.update', $record), ['name' => 'Charles Andrrei Galban'])
            ->assertOk();

        $audit = RecordAuditLog::query()->where('action', 'updated')->sole();

        $this->assertSame('formalcharlesandreigalvan@gmail.com', $audit->actor_email);
        $this->assertSame('CHARLES ANDRREI GALBAN', $audit->record_name);
        $this->assertStringContainsString('Updated NAME', $audit->summary);
        $this->assertSame('Charles Andrei Galvan', $audit->changes['full_name']['from']);
        $this->assertSame('CHARLES ANDRREI GALBAN', $audit->changes['full_name']['to']);
    }

    public function test_bnh_check_fields_are_excluded_when_other_record_fields_change(): void
    {
        $record = Record::query()->create([
            'full_name' => 'Audit Check Member',
            'status' => 'MS',
            'is_valid1' => false,
            'is_valid2' => false,
        ]);

        $this->withSession([
            'login_user' => [
                'id' => 1,
                'gmail' => 'secretary@example.test',
            ],
        ])
            ->putJson(route('records.update', $record), [
                'name' => 'Audit Check Member Updated',
            ])
            ->assertOk();

        $audit = RecordAuditLog::query()->where('action', 'updated')->sole();
        $this->assertArrayNotHasKey('is_valid1', $audit->changes);
        $this->assertArrayNotHasKey('is_valid2', $audit->changes);
        $this->assertStringNotContainsString('BNH CHECK', $audit->summary);
    }

    public function test_bnh_check_only_updates_do_not_create_record_audit_logs(): void
    {
        $record = Record::query()->create([
            'full_name' => 'Audit Check Member',
            'status' => 'MS',
            'is_valid1' => false,
            'is_valid2' => false,
        ]);
        $existingLogCount = RecordAuditLog::query()->count();

        $this->withSession([
            'login_user' => [
                'id' => 1,
                'gmail' => 'secretary@example.test',
            ],
        ])
            ->putJson(route('filter-bnh-helper.checks.update', $record), [
                'is_valid1' => true,
                'is_valid2' => true,
            ])
            ->assertOk();

        $this->assertSame($existingLogCount, RecordAuditLog::query()->count());
    }

    public function test_record_deletion_is_logged_before_the_record_is_removed(): void
    {
        $record = Record::query()->create([
            'full_name' => 'Inactive Audit Member',
            'status' => 'INACTIVE',
        ]);

        $this->withSession([
            'login_user' => [
                'id' => 1,
                'gmail' => 'secretary@example.test',
            ],
        ])
            ->deleteJson(route('records.destroy', $record))
            ->assertOk();

        $audit = RecordAuditLog::query()->where('action', 'deleted')->sole();

        $this->assertSame('secretary@example.test', $audit->actor_email);
        $this->assertSame($record->id, $audit->record_id);
        $this->assertSame('Inactive Audit Member', $audit->record_name);
        $this->assertSame('Deleted record Inactive Audit Member.', $audit->summary);
        $this->assertDatabaseMissing('records', ['id' => $record->id]);
    }
}
