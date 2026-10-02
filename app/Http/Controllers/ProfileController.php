<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile', [
            'user' => $request->session()->get('login_user'),
        ]);
    }

    public function update(Request $request)
    {
        $sessionUser = $request->session()->get('login_user');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'picture' => ['nullable', 'image', 'max:2048'],
        ]);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
        ];

        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('profile-pictures', 'public');
            $updateData['profile_picture'] = $path;
        }

        DB::table('login_user')->where('id', $sessionUser['id'])->update($updateData);

        $sessionUser = array_merge($sessionUser, $updateData);
        $request->session()->put('login_user', $sessionUser);

        return back()->with('status', 'Profile updated.');
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()->symbols(), 'confirmed'],
        ]);

        $userId = (int) $request->session()->get('login_user.id');
        $user = DB::table('login_user')->where('id', $userId)->first();
        abort_unless($user, 403);

        $matches = str_starts_with((string) $user->password, '$')
            ? Hash::check($validated['current_password'], $user->password)
            : hash_equals((string) $user->password, $validated['current_password']);
        if (! $matches) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        DB::table('login_user')->where('id', $userId)->update([
            'password' => Hash::make($validated['password']),
            'auth_version' => DB::raw('auth_version + 1'),
            'auth_notice' => 'password_changed',
            'remember_token' => null,
            'remember_token_expires_at' => null,
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'Change of Password Detected, Please Login Again.');
    }
}