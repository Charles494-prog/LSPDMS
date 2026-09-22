<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('login_user')) {
            return $next($request);
        }

        if ($this->attemptRememberLogin($request)) {
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

        if (! $user || ! $user->remember_token || ! $user->remember_token_expires_at) {
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
        ]);

        return true;
    }
}