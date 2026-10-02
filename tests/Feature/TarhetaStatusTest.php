<?php

namespace Tests\Feature;

use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarhetaStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_checker_shows_mismatches_by_default_and_can_show_matches_and_saves_tarheta_status(): void
    {
        $user = $this->makeInquireUser();
        $mismatch = Record::query()->create([
            'full_name' => 'Member Needs Tarheta Update',
            'status' => 'UWP',
            'tarheta_status' => 'MS',
        ]);
        Record::query()->create([
            'full_name' => 'Member Already Matching',
            'status' => 'MK',
            'tarheta_status' => 'MK',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('tarheta-status'))
            ->assertOk()
            ->assertSee('Member Needs Tarheta Update')
            ->assertSee('Member Already Matching')
            ->assertSee('data-matched="1"', false)
            ->assertSee('Tarheta Status Checker')
            ->assertSee('name="csrf-token"', false)
            ->assertSee('tarheta-choice')
            ->assertSee('Show already-matched records')
            ->assertSee('tarhetaProgress')
            ->assertSee('tarhetaSearch')
            ->assertDontSee('tarheta-save')
            ->assertDontSee('>Save<', false);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->putJson(route('tarheta-status.update', $mismatch), ['tarheta_status' => 'UWP'])
            ->assertOk()
            ->assertJsonPath('matches', true)
            ->assertJsonPath('tarheta_status', 'UWP');

        $this->assertDatabaseHas('records', [
            'id' => $mismatch->id,
            'status' => 'UWP',
            'tarheta_status' => 'UWP',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('tarheta-status'))
            ->assertOk()
            ->assertSee('Member Needs Tarheta Update')
            ->assertSee('data-matched="1"', false)
            ->assertSee('100%');
    }

    public function test_checker_only_accepts_the_four_tarheta_statuses_and_requires_its_own_permission(): void
    {
        $user = $this->makeInquireUser();
        $record = Record::query()->create([
            'full_name' => 'Tarheta Validation Member',
            'status' => 'MS',
            'tarheta_status' => 'UWP',
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->putJson(route('tarheta-status.update', $record), ['tarheta_status' => 'OTHER'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tarheta_status');

        $unauthorized = User::query()->create([
            'gmail' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
            'Inquire_Allow' => true,
            'tarheta_status_allow' => false,
        ]);
        $this->withSession(['login_user' => ['id' => $unauthorized->id]])
            ->get(route('tarheta-status'))
            ->assertForbidden();
    }

    public function test_checker_treats_null_statuses_as_matching_and_progress_counts_the_full_evaluated_set(): void
    {
        $user = $this->makeInquireUser();
        Record::query()->create(['full_name' => 'Null Pair', 'status' => null, 'tarheta_status' => null]);
        Record::query()->create(['full_name' => 'Existing Match', 'status' => 'MS', 'tarheta_status' => 'MS']);
        Record::query()->create(['full_name' => 'Status Mismatch', 'status' => 'UWP', 'tarheta_status' => 'MS']);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('tarheta-status'))
            ->assertOk()
            ->assertSee('3</strong> evaluated records', false)
            ->assertSee('id="tarhetaMatched">2', false)
            ->assertSee('id="tarhetaTotal">3', false)
            ->assertSee('66.7%')
            ->assertSee('data-matched="1"', false)
            ->assertSee('data-matched="0"', false);
    }

    private function makeInquireUser(): User
    {
        return User::query()->create([
            'gmail' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
            'Inquire_Allow' => true,
            'tarheta_status_allow' => true,
        ]);
    }
}
