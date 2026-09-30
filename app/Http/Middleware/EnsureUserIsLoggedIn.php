<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('login_user')) {
            $sessionUser = $request->session()->get('login_user');
            $user = DB::table('login_user')->where('id', $sessionUser['id'] ?? null)->first();
            if (! $user || (int) $user->is_verified !== 1) {
                $request->session()->forget('login_user');
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                Cookie::queue(Cookie::forget('remember_token'));

                return redirect('/login')->withErrors([
                    'login' => 'Your account access has been disabled. Please contact the head secretary.',
                ]);
            }

            $freshUser = [
                'id' => $user->id,
                'gmail' => $user->gmail,
                'tungkulin' => $user->tungkulin,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'gender' => $user->gender,
                'profile_picture' => $user->profile_picture,
                'dark_mode' => (bool) $user->dark_mode,
                'dashboard_allow' => (bool) $user->dashboard_allow,
                'form_generator_allow' => (bool) $user->form_generator_allow,
                'Delistment_allow' => (bool) $user->Delistment_allow,
                'Inquire_Allow' => (bool) $user->Inquire_Allow,
                'is_verified' => (bool) $user->is_verified,
            ];
            $request->session()->put('login_user', $freshUser);
            view()->share('authenticatedUser', $freshUser);

            return $next($request);
        }

        if ($this->attemptRememberLogin($request)) {
            view()->share('authenticatedUser', $request->session()->get('login_user'));

            return $next($request);
        }

        return redirect('/login')->withErrors(['login' => 'Please log in to continue.']);
    }

    private function attemptRememberLogin(Request $request): bool
    {
        $cookie = $request->cookie('remember_token');

        if (! $cookie || ! str_contains($cookie, '|')) {
            return false;
        }

        [$userId, $token] = explode('|', $cookie, 2);

        $user = DB::table('login_user')->where('id', $userId)->first();

        if (! $user || (int) $user->is_verified !== 1 || ! $user->remember_token || ! $user->remember_token_expires_at) {
            return false;
        }

        if (now()->timestamp > strtotime($user->remember_token_expires_at)) {
            return false;
        }

        if (! Hash::check($token, $user->remember_token)) {
            return false;
        }

        $request->session()->regenerate();
        $request->session()->put('login_user', [
            'id' => $user->id,
            'gmail' => $user->gmail,
            'tungkulin' => $user->tungkulin,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'gender' => $user->gender,
            'profile_picture' => $user->profile_picture,
            'dark_mode' => (bool) $user->dark_mode,
            'dashboard_allow' => (bool) $user->dashboard_allow,
            'form_generator_allow' => (bool) $user->form_generator_allow,
            'Delistment_allow' => (bool) $user->Delistment_allow,
            'Inquire_Allow' => (bool) $user->Inquire_Allow,
            'is_verified' => (bool) $user->is_verified,
        ]);

        return true;
    }
}
