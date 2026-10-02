<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticatedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('login_user')) {
            return $next($request);
        }

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

        return redirect('/home');
    }
}
