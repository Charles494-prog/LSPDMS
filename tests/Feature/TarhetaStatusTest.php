<?php

namespace Tests\Feature;

use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarhetaStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_checker_shows_only_records_whose_statuses_differ_and_saves_tarheta_status(): void
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
            ->assertDontSee('Member Already Matching')
            ->assertSee('Tarheta Status Checker')
            ->assertSee('tarheta-choice')
            ->assertSee('tarheta-row-fading')
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
            ->assertDontSee('Member Needs Tarheta Update')
            ->assertSee('All Tarheta statuses match the current record statuses.');
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
