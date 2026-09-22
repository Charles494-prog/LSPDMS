<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}