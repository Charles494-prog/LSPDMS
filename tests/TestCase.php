<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function withSession(array $data): static
    {
        $sessionUser = $data['login_user'] ?? null;
        if (is_array($sessionUser) && isset($sessionUser['id']) && ! User::query()->whereKey($sessionUser['id'])->exists()) {
            $user = new User;
            $user->forceFill([
                'id' => (int) $sessionUser['id'],
                'gmail' => $sessionUser['gmail'] ?? 'session-'.$sessionUser['id'].'@example.test',
                'password' => 'test-session-password',
                'tungkulin' => $sessionUser['tungkulin'] ?? 'Head Secretary',
                'is_verified' => true,
                'first_name' => $sessionUser['first_name'] ?? 'Test',
                'last_name' => $sessionUser['last_name'] ?? 'Session',
                'dashboard_allow' => true,
                'form_generator_allow' => true,
                'Delistment_allow' => true,
                'Inquire_Allow' => true,
            ])->save();
        }

        return parent::withSession($data);
    }
}
