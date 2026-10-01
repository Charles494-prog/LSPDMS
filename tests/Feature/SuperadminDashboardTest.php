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
            'tungkulin' => 'Head Secretary',
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
            ->assertSee('id="saToastContainer"', false)
            ->assertDontSee('id="signatoriesSuccessModal"', false);
    }

    public function test_superadmin_layout_keeps_authenticated_head_secretary_identity_after_user_loops(): void
    {
        $headSecretary = User::query()->create([
            'gmail' => 'head@example.test',
            'password' => 'secret-password',
            'first_name' => 'Dudu',
            'last_name' => 'Bear',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);
        User::query()->create([
            'gmail' => 'last.user@example.test',
            'password' => 'secret-password',
            'first_name' => 'Last',
            'last_name' => 'User',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->get(route('superadmin-dashboard'))
            ->assertOk()
            ->assertSee('Dudu Bear')
            ->assertSee('Head Secretary')
            ->assertSee('Superadmin Dashboard')
            ->assertDontSee('CHARLESS 2 Secretary');
    }

    public function test_user_access_and_tungkulin_are_saved_to_login_user(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
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
                'tungkulin' => 'Head Secretary',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.tungkulin', 'Head Secretary');

        $this->assertDatabaseHas('login_user', [
            'id' => $user->id,
            'is_verified' => 1,
            'tungkulin' => 'Head Secretary',
        ]);
    }

    public function test_permissions_can_only_be_saved_for_verified_users(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
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
                    'tarheta_status_allow' => 1,
                    'filter_bnh_helper_allow' => 1,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('login_user', [
            'id' => $verifiedUser->id,
            'dashboard_allow' => 1,
            'form_generator_allow' => 0,
            'Delistment_allow' => 1,
            'Inquire_Allow' => 0,
            'tarheta_status_allow' => 1,
            'filter_bnh_helper_allow' => 1,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->get(route('superadmin-dashboard'))
            ->assertOk()
            ->assertSee('Allow Tarheta<br>Status Checker', false)
            ->assertSee('data-permission="tarheta_status_allow" checked', false)
            ->assertSee('Allow Filter &amp;<br>BNH Helper', false)
            ->assertSee('data-permission="filter_bnh_helper_allow" checked', false);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.permissions.update'), [
                'permissions' => [[
                    'id' => $unverifiedUser->id,
                    'dashboard_allow' => 1,
                    'form_generator_allow' => 1,
                    'Delistment_allow' => 1,
                    'Inquire_Allow' => 1,
                    'tarheta_status_allow' => 1,
                    'filter_bnh_helper_allow' => 1,
                ]],
            ])
            ->assertUnprocessable();
    }

    public function test_signatories_and_global_variables_are_saved_to_their_tables(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
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
            ->assertSee('Minimum years since date of entry')
            ->assertSee('Baptism cutoff date')
            ->assertDontSee('delistEntryCheck')
            ->assertSee('<select id="gvEntryYears" required>', false)
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

    public function test_puroks_can_share_a_header_number(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.signatories.update'), [
                'puroks' => [
                    [
                        'purok_number' => 2,
                        'purok_name' => 'Purok 2a',
                        'grupo_start' => 1,
                        'grupo_end' => 6,
                        'full_purok' => 0,
                    ],
                    [
                        'purok_number' => 2,
                        'purok_name' => 'Purok 2b',
                        'grupo_start' => 7,
                        'grupo_end' => 10,
                        'full_purok' => 0,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertSame(2, Purok::query()->where('purok_number', 2)->count());
    }

    public function test_purok_ranges_sharing_a_header_cannot_overlap(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.signatories.update'), [
                'puroks' => [
                    [
                        'purok_number' => 2,
                        'purok_name' => 'Purok 2a',
                        'grupo_start' => 1,
                        'grupo_end' => 6,
                        'full_purok' => 0,
                    ],
                    [
                        'purok_number' => 2,
                        'purok_name' => 'Purok 2b',
                        'grupo_start' => 6,
                        'grupo_end' => 10,
                        'full_purok' => 0,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('puroks.1.grupo_start');

        $this->assertSame(0, Purok::query()->count());
    }

    public function test_complete_purok_cannot_overlap_a_split_purok_with_the_same_header(): void
    {
        $admin = User::query()->create([
            'gmail' => 'admin@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);
        $purok2a = Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2a',
            'grupo_start' => 1,
            'grupo_end' => 6,
            'full_purok' => false,
        ]);
        $purok2b = Purok::query()->create([
            'purok_number' => 2,
            'purok_name' => 'Purok 2b',
            'grupo_start' => 7,
            'grupo_end' => 11,
            'full_purok' => false,
        ]);

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.signatories.update'), [
                'puroks' => [
                    [
                        'id' => $purok2a->id,
                        'purok_number' => '2',
                        'purok_name' => 'Purok 2a',
                        'grupo_start' => 1,
                        'grupo_end' => 6,
                        'full_purok' => 1,
                    ],
                    [
                        'id' => $purok2b->id,
                        'purok_number' => 2,
                        'purok_name' => 'Purok 2b',
                        'grupo_start' => 7,
                        'grupo_end' => 11,
                        'full_purok' => 0,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('puroks.0.full_purok');

        $this->assertDatabaseHas('puroks', [
            'id' => $purok2a->id,
            'full_purok' => false,
            'grupo_start' => 1,
            'grupo_end' => 6,
        ]);
        $this->assertDatabaseHas('puroks', [
            'id' => $purok2b->id,
            'full_purok' => false,
            'grupo_start' => 7,
            'grupo_end' => 11,
        ]);
    }

    public function test_security_settings_are_persisted_and_only_head_secretary_can_access_them(): void
    {
        $headSecretary = User::query()->create([
            'gmail' => 'head@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);
        $secretary = User::query()->create([
            'gmail' => 'secretary@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->putJson(route('superadmin.security-settings.update'), [
                'require_otp' => 0,
                'allow_forgot_password' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('data.require_otp', false)
            ->assertJsonPath('data.allow_forgot_password', false);

        $this->assertDatabaseHas('global_variables', [
            'require_otp' => 0,
            'allow_forgot_password' => 0,
        ]);

        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->get(route('superadmin-dashboard'))
            ->assertOk()
            ->assertSee('id="requireOtpSetting"', false)
            ->assertSee('id="allowForgotPasswordSetting"', false);

        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('superadmin-dashboard'))
            ->assertForbidden();

        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->putJson(route('superadmin.security-settings.update'), [
                'require_otp' => 1,
                'allow_forgot_password' => 1,
            ])
            ->assertForbidden();
    }

    public function test_disabling_user_access_invalidates_the_account_session_and_remember_login(): void
    {
        $headSecretary = User::query()->create([
            'gmail' => 'head@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);
        $secretary = User::query()->create([
            'gmail' => 'secretary@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
            'remember_token' => 'stored-token',
            'remember_token_expires_at' => now()->addDay(),
        ]);

        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->putJson(route('superadmin.users.update', $secretary), ['is_verified' => 0])
            ->assertOk()
            ->assertJsonPath('data.is_verified', false)
            ->assertJsonPath('data.revoked_current_session', false);

        $this->assertDatabaseHas('login_user', [
            'id' => $secretary->id,
            'is_verified' => 0,
            'remember_token' => null,
            'remember_token_expires_at' => null,
        ]);
        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('home'))
            ->assertRedirect('/login');
    }

    public function test_disabling_current_users_access_tells_the_browser_to_log_out_immediately(): void
    {
        $headSecretary = User::query()->create([
            'gmail' => 'head@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->putJson(route('superadmin.users.update', $headSecretary), ['is_verified' => 0])
            ->assertOk()
            ->assertJsonPath('data.is_verified', false)
            ->assertJsonPath('data.revoked_current_session', true);
    }

    public function test_baptism_cutoff_is_required_when_enabled_and_entry_years_are_always_required(): void
    {
        $admin = User::query()->create([
            'gmail' => 'requirements-admin@example.test',
            'password' => 'secret-password',
            'tungkulin' => 'Head Secretary',
            'is_verified' => true,
        ]);
        $base = [
            'dateofbaptism_requirement_boolean' => 1,
            'entry_years_requirement' => 2,
        ];

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.global-variables.update'), $base)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('baptism_requirement');

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.global-variables.update'), [
                'dateofbaptism_requirement_boolean' => 0,
                'entry_years_requirement' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entry_years_requirement');

        $this->withSession(['login_user' => ['id' => $admin->id]])
            ->putJson(route('superadmin.global-variables.update'), [
                'dateofbaptism_requirement_boolean' => 0,
                'entry_years_requirement' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.entry_years_requirement', 3);
    }
}
