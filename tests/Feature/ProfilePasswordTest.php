<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_offers_password_change_and_password_change_revokes_all_sessions(): void
    {
        $user = User::query()->create([
            'gmail' => 'profile-password@example.test',
            'password' => 'current-password-123',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
            'remember_token' => Hash::make('remember-me'),
            'remember_token_expires_at' => now()->addDay(),
        ]);
        $session = ['login_user' => ['id' => $user->id, 'auth_version' => 1]];

        $this->withSession($session)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Change Password')
            ->assertSee('current_password')
            ->assertSee('At least one special character');

        $this->withSession($session)
            ->put(route('profile.password.update'), [
                'current_password' => 'current-password-123',
                'password' => 'New-profile-password-123!',
                'password_confirmation' => 'New-profile-password-123!',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Change of Password Detected, Please Login Again.');

        $user->refresh();
        $this->assertTrue(Hash::check('New-profile-password-123!', $user->password));
        $this->assertSame(2, (int) $user->auth_version);
        $this->assertSame('password_changed', $user->auth_notice);
        $this->assertNull($user->remember_token);
        $this->assertNull($user->remember_token_expires_at);

        $this->withSession($session)
            ->getJson(route('session.status'))
            ->assertUnauthorized()
            ->assertJsonPath('reason', 'password_changed');
    }

    public function test_password_change_requires_correct_current_password_and_confirmation(): void
    {
        $user = User::query()->create([
            'gmail' => 'profile-password-invalid@example.test',
            'password' => 'current-password-123',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $user->id, 'auth_version' => 1]])
            ->put(route('profile.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'New-profile-password-123!',
                'password_confirmation' => 'New-profile-password-123!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->withSession(['login_user' => ['id' => $user->id, 'auth_version' => 1]])
            ->put(route('profile.password.update'), [
                'current_password' => 'current-password-123',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('current-password-123', $user->fresh()->password));
        $this->assertSame(1, (int) $user->fresh()->auth_version);
    }
}
