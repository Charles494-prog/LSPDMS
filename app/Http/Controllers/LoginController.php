<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($credentials['email']));

        $user = DB::table('login_user')
            ->whereRaw('LOWER(TRIM(gmail)) = ?', [$email])
            ->where('is_verified', 1)
            ->first();

        $passwordMatches = false;

        if ($user) {
            $storedPassword = (string) $user->password;
            $isHashedPassword = str_starts_with($storedPassword, '$2y$')
                || str_starts_with($storedPassword, '$2a$')
                || str_starts_with($storedPassword, '$2b$')
                || str_starts_with($storedPassword, '$argon2');

            if ($isHashedPassword) {
                $passwordMatches = Hash::check($credentials['password'], $user->password);
            } else {
                $passwordMatches = hash_equals($storedPassword, $credentials['password']);
            }
        }

        if (! $passwordMatches) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['login' => 'Invalid username or password.']);
        }

        try {
            $code = $this->sendVerificationCode($user->gmail);
        } catch (\Throwable $exception) {
            Log::error('LSPDMS verification email failed.', [
                'mailer' => config('mail.default'),
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['login' => 'Unable to send the verification code. Please check your internet connection and try again.']);
        }

        $request->session()->put('pending_login_user', [
            'id' => $user->id,
            'gmail' => $user->gmail,
            'tungkulin' => $user->tungkulin,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5)->timestamp,
            'sent_at' => now()->timestamp,
        ]);

        return redirect('/verify-code')->with('status', 'A verification code was sent to your Gmail.');
    }

    public function showVerificationForm(Request $request)
    {
        abort_unless($request->session()->has('pending_login_user'), 403);

        $pendingUser = $request->session()->get('pending_login_user');

        return view('verify-code', [
            'expiresAt' => $pendingUser['expires_at'],
            'resendAvailableAt' => ($pendingUser['sent_at'] ?? 0) + 60,
        ]);
    }

    public function verifyCode(Request $request)
    {
        $pendingUser = $request->session()->get('pending_login_user');

        if (! $pendingUser) {
            return redirect('/login')->withErrors(['login' => 'Please log in again.']);
        }

        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if ($pendingUser['expires_at'] < now()->timestamp) {
            $request->session()->forget('pending_login_user');

            return redirect('/login?expired=1');
        }

        if (! Hash::check($request->code, $pendingUser['code'])) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $request->session()->regenerate();
        $request->session()->put('login_user', [
            'id' => $pendingUser['id'],
            'gmail' => $pendingUser['gmail'],
            'tungkulin' => $pendingUser['tungkulin'],
        ]);
        $request->session()->forget('pending_login_user');

        return redirect('/login')->with('status', 'Login successful.');
    }

    public function resendCode(Request $request)
    {
        $pendingUser = $request->session()->get('pending_login_user');

        if (! $pendingUser) {
            return redirect('/login')->withErrors(['login' => 'Please log in again.']);
        }

        if (($pendingUser['sent_at'] ?? 0) + 60 > now()->timestamp) {
            return back()->withErrors(['code' => 'Please wait before requesting another code.']);
        }

        try {
            $code = $this->sendVerificationCode($pendingUser['gmail']);
        } catch (\Throwable $exception) {
            Log::error('LSPDMS verification email resend failed.', [
                'mailer' => config('mail.default'),
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['code' => 'Unable to send a new verification code.']);
        }

        $pendingUser['code'] = Hash::make($code);
        $pendingUser['expires_at'] = now()->addMinutes(5)->timestamp;
        $pendingUser['sent_at'] = now()->timestamp;
        $request->session()->put('pending_login_user', $pendingUser);

        return back()->with('status', 'A new verification code was sent.');
    }

    private function sendVerificationCode(string $email): string
    {
        $code = (string) random_int(100000, 999999);

        Mail::raw("Your LSPDMS verification code is: {$code}", function ($message) use ($email) {
            $message->to($email)->subject('LSPDMS verification code');
        });

        return $code;
    }
}
