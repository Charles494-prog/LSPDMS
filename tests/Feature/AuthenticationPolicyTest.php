<?php

namespace Tests\Feature;

use App\Models\GlobalVariable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_policy_can_bypass_code_after_valid_credentials_when_disabled(): void
    {
        GlobalVariable::query()->create(['require_otp' => false]);
        $user = $this->verifiedUser('no-otp@example.test');

        $this->post(route('login'), [
            'email' => $user->gmail,
            'password' => 'valid-password',
        ])
            ->assertRedirect('/home')
            ->assertSessionHas('login_user.id', $user->id);

        $this->assertFalse(session()->has('pending_login_user'));
    }

    public function test_otp_remains_required_by_default(): void
    {
        Mail::fake();
        $user = $this->verifiedUser('otp@example.test');

        $this->post(route('login'), [
            'email' => $user->gmail,
            'password' => 'valid-password',
        ])
            ->assertRedirect('/verify-code')
            ->assertSessionHas('pending_login_user.id', $user->id);
    }

    public function test_secretary_feature_permissions_are_enforced_on_routes_and_navigation(): void
    {
        $secretary = $this->verifiedUser('secretary@example.test', [
            'tungkulin' => 'Secretary',
            'dashboard_allow' => true,
            'form_generator_allow' => false,
            'Delistment_allow' => false,
            'Inquire_Allow' => false,
        ]);

        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertDontSee('De-Listment')
            ->assertDontSee('Inquire / Modify')
            ->assertDontSee('Tarheta Status Checker')
            ->assertDontSee('Form Generator');

        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('de-listment'))
            ->assertForbidden()
            ->assertSee('Your account does not have permission to access this feature.')
            ->assertSee('href="'.url('/home').'"', false)
            ->assertSee('Back to Home');
        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('inquire-modify'))
            ->assertForbidden();
        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('tarheta-status'))
            ->assertForbidden();
        $this->withSession(['login_user' => ['id' => $secretary->id]])
            ->get(route('form-generator'))
            ->assertForbidden();
    }

    public function test_head_secretary_has_superadmin_and_feature_access_even_without_feature_flags(): void
    {
        $headSecretary = $this->verifiedUser('head@example.test', [
            'tungkulin' => 'Head Secretary',
            'dashboard_allow' => false,
            'form_generator_allow' => false,
            'Delistment_allow' => false,
            'Inquire_Allow' => false,
        ]);

        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->get(route('superadmin-dashboard'))
            ->assertOk();
        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->get(route('dashboard'))
            ->assertOk();
        $this->withSession(['login_user' => ['id' => $headSecretary->id]])
            ->get(route('tarheta-status'))
            ->assertOk();
    }

    public function test_forgot_password_setting_hides_link_and_disables_reset_routes(): void
    {
        GlobalVariable::query()->create(['allow_forgot_password' => false]);

        $this->get(route('login.form'))
            ->assertOk()
            ->assertDontSee('Forgot password?');
        $this->get(route('password.request'))->assertNotFound();
        $this->post(route('password.email'), ['email' => 'person@example.test'])->assertNotFound();
    }

    public function test_password_reset_email_flow_updates_password_and_revokes_remember_token(): void
    {
        $user = $this->verifiedUser('reset@example.test', [
            'remember_token' => Hash::make('remember-me'),
            'remember_token_expires_at' => now()->addDay(),
        ]);
        $resetUrl = null;
        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $message, callable $callback) use (&$resetUrl): bool {
                preg_match('/https?:\/\/\S+/', $message, $matches);
                $resetUrl = $matches[0] ?? null;
                $callback(new class
                {
                    public function to(string $email): self
                    {
                        return $this;
                    }

                    public function subject(string $subject): self
                    {
                        return $this;
                    }
                });

                return str_contains($message, '/password/reset/');
            });

        $this->post(route('password.email'), ['email' => $user->gmail])
            ->assertRedirect()
            ->assertSessionHas('status');
        $this->assertNotNull($resetUrl);
        $parts = parse_url($resetUrl);
        parse_str($parts['query'] ?? '', $query);
        $token = basename($parts['path']);

        $this->get($resetUrl)
            ->assertOk()
            ->assertSee('Choose a new password');
        $this->post(route('password.reset'), [
            'email' => $user->gmail,
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertRedirect('/login')
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertNull($user->fresh()->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->gmail]);
    }

    private function verifiedUser(string $email, array $attributes = []): User
    {
        return User::query()->create($attributes + [
            'gmail' => $email,
            'password' => 'valid-password',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
        ]);
    }
}
