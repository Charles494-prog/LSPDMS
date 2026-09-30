<?php

namespace App\Http\Controllers;

use App\Models\GlobalVariable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        $this->ensureEnabled();

        return view('password-request');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $this->ensureEnabled();
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = mb_strtolower(trim($validated['email']));
        $user = DB::table('login_user')
            ->whereRaw('LOWER(TRIM(gmail)) = ?', [$email])
            ->where('is_verified', 1)
            ->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $url = URL::temporarySignedRoute(
                'password.reset.form',
                now()->addMinutes((int) config('auth.passwords.users.expire', 60)),
                ['token' => $token, 'email' => $email]
            );
            try {
                Mail::raw(
                    "Use the following link to reset your LSPDMS password. This link expires in one hour:\n\n{$url}\n\nIf you did not request this, you can ignore this email.",
                    fn ($message) => $message->to($user->gmail)->subject('Reset your LSPDMS password')
                );
            } catch (\Throwable $exception) {
                Log::error('LSPDMS password reset email failed.', [
                    'mailer' => config('mail.default'),
                    'message' => $exception->getMessage(),
                ]);
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Unable to send a password reset email right now. Please try again later.']);
            }
        }

        return back()->with('status', 'If that email belongs to an active account, a password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        $this->ensureEnabled();
        $email = mb_strtolower(trim((string) $request->query('email')));
        abort_unless($this->validToken($email, $token), 403, 'This password reset link is invalid or expired.');

        return view('password-reset', compact('email', 'token'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->ensureEnabled();
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $email = mb_strtolower(trim($validated['email']));
        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();
        abort_unless($reset && Hash::check($validated['token'], $reset->token)
            && $this->isUnexpired($reset->created_at), 403, 'This password reset link is invalid or expired.');

        DB::transaction(function () use ($email, $validated): void {
            $user = DB::table('login_user')
                ->whereRaw('LOWER(TRIM(gmail)) = ?', [$email])
                ->where('is_verified', 1)
                ->lockForUpdate()
                ->first();
            abort_unless($user, 403, 'This password reset link is invalid or expired.');

            DB::table('login_user')->where('id', $user->id)->update([
                'password' => Hash::make($validated['password']),
                'remember_token' => null,
                'remember_token_expires_at' => null,
            ]);
            DB::table('password_reset_tokens')->where('email', $email)->delete();
        });

        return redirect('/login')->with('status', 'Your password has been reset. You can now log in.');
    }

    private function validToken(string $email, string $token): bool
    {
        if ($email === '') {
            return false;
        }

        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();

        return $reset !== null
            && Hash::check($token, $reset->token)
            && $this->isUnexpired($reset->created_at);
    }

    private function isUnexpired(?string $createdAt): bool
    {
        return $createdAt !== null
            && now()->subMinutes((int) config('auth.passwords.users.expire', 60))->lt($createdAt);
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) (GlobalVariable::query()->first()?->allow_forgot_password ?? true), 404);
    }
}
