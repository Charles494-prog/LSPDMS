<?php

namespace Tests\Feature;

use App\Models\GlobalVariable;
use App\Models\Purok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_database_users_without_sample_users_or_credentials(): void
    {
        $user = User::query()->create([
            'gmail' => 'real.user@example.test',
            'password' => 'secret-password',
            'first_name' => 'Real',
            'last_name' => 'User',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('superadmin-dashboard'))
            ->assertOk()
            ->assertSee('Real User')
            ->assertSee('real.user@example.test')
            ->assertDontSee('Maria Santos')
            ->assertDontSee('Juan Dela Cruz')
            ->assertDontSee('secret-password')
            ->assertDontSee('name="password"')
            ->assertSee('id="saveSignatoriesBtn" disabled', false)
            ->assertSee('id="signatoriesSuccessTitle">Signatories saved!', false)
            ->assertSee('id="closeSignatoriesSuccess"', false);
    }

    public function test_user_access_and_tungkulin_are_saved_to_login_user(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
        ]);
        $user = User::query()->create([
            'gmail' => 'secretary@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Secretary',
            'is_verified' => false,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.users.update', $user), [
                'is_verified' => 1,
                'tungkulin' => 'Secretary Head',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.tungkulin', 'Secretary Head');

        $this->assertDatabaseHas('login_user', [
            'id' => $user->id,
            'is_verified' => 1,
            'tungkulin' => 'Secretary Head',
        ]);
    }

    public function test_permissions_can_only_be_saved_for_verified_users(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
        ]);
        $verifiedUser = User::query()->create([
            'gmail' => 'verified@example.test',
            'password' => 'secret-password',
            'is_verified' => true,
        ]);
        $unverifiedUser = User::query()->create([
            'gmail' => 'unverified@example.test',
            'password' => 'secret-password',
            'is_verified' => false,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.permissions.update'), [
                'permissions' => [[
                    'id' => $verifiedUser->id,
                    'dashboard_allow' => 1,
                    'form_generator_allow' => 0,
                    'Delistment_allow' => 1,
                    'Inquire_Allow' => 0,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('login_user', [
            'id' => $verifiedUser->id,
            'dashboard_allow' => 1,
            'form_generator_allow' => 0,
            'Delistment_allow' => 1,
            'Inquire_Allow' => 0,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.permissions.update'), [
                'permissions' => [[
                    'id' => $unverifiedUser->id,
                    'dashboard_allow' => 1,
                    'form_generator_allow' => 1,
                    'Delistment_allow' => 1,
                    'Inquire_Allow' => 1,
                ]],
            ])
            ->assertUnprocessable();
    }

    public function test_signatories_and_global_variables_are_saved_to_their_tables(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
        ]);
        $secretary = User::query()->create([
            'gmail' => 'secretary@example.test',
            'password' => 'secret-password',
            'first_name' => 'Secretary',
        ]);
        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.signatories.update'), [
                'puroks' => [[
                    'purok_number' => 3,
                    'purok_name' => 'Purok Three',
                    'secretary' => $secretary->id,
                    'manggagawa' => 'Worker Name',
                    'ministerial_id' => 'MID-003',
                    'grupo_start' => 1,
                    'grupo_end' => 12,
                    'full_purok' => 0,
                ]],
            ])
            ->assertOk();

        $purok = Purok::query()->firstOrFail();
        $this->assertDatabaseHas('puroks', [
            'id' => $purok->id,
            'purok_number' => 3,
            'purok_name' => 'Purok Three',
            'secretary' => $secretary->id,
            'manggagawa' => 'Worker Name',
            'ministerial_id' => 'MID-003',
            'grupo_start' => 1,
            'grupo_end' => 12,
            'full_purok' => 0,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->get(route('superadmin-dashboard'))
            ->assertOk()
            ->assertSee('Purok Three')
            ->assertSee('secretary@example.test')
            ->assertSee('Worker Name')
            ->assertSee('type="text" class="signatory-input manggagawa-input"', false)
            ->assertDontSee('<select class="signatory-input manggagawa-input">', false);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.global-variables.update'), [
                'locale' => 'Sample Locale',
                'lcode' => 'L-123',
                'district' => 'Sample District',
                'dcode' => 'D-456',
                's_number' => 'S-123',
                'pastor_destinado' => 'Pastor Person',
                'ksp_pd' => 'KSP Person',
                'kalihim_ms' => 'MS Secretary',
                'kalihim_uwp' => 'UWP Secretary',
                'baptism_requirement' => '2026-01-01 00:00:00',
                'entry_years_requirement' => 2,
                'dateofbaptism_requirement_boolean' => 1,
                'dateofentryrequirement_boolean' => 0,
            ])
            ->assertOk();

        $this->assertDatabaseHas('global_variables', [
            'locale' => 'Sample Locale',
            'lcode' => 'L-123',
            'district' => 'Sample District',
            'dcode' => 'D-456',
            's_number' => 'S-123',
            'pastor_destinado' => 'Pastor Person',
            'ksp_pd' => 'KSP Person',
            'kalihim_ms' => 'MS Secretary',
            'kalihim_uwp' => 'UWP Secretary',
            'baptism_requirement' => '2026-01-01 00:00:00',
            'entry_years_requirement' => 2,
            'dateofbaptism_requirement_boolean' => 1,
            'dateofentryrequirement_boolean' => 0,
        ]);

        $this->assertInstanceOf(GlobalVariable::class, GlobalVariable::query()->first());

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->get(route('superadmin-dashboard'))
            ->assertOk()
            ->assertSee('Sample Locale')
            ->assertSee('L-123')
            ->assertSee('Sample District')
            ->assertSee('D-456')
            ->assertSee('Pastor Person');
    }
}
