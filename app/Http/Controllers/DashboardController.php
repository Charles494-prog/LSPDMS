<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('login_user');

        return view('dashboard', [
            'user' => $user,
        ]);
    }
}