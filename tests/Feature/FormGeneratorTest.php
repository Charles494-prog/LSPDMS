<?php

namespace Tests\Feature;

use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertDontSee('data-panel="deliberation"', false)
            ->assertSee('Deliberation')
            ->assertSee('R4-01 Generator')
            ->assertSee('Resumen Generator')
            ->assertSee('Notes Checker')
            ->assertSee('id="notesRecordPagination"', false)
            ->assertSee('id="previousRecordsPage"', false)
            ->assertSee('id="nextRecordsPage"', false)
            ->assertSee('const recordsPerPage = 10;', false);
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
