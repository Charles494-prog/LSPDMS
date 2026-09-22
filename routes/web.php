<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return redirect('/intro');
});

Route::get('/intro', function () {
    return view('intro');
});

Route::get('/login', function () {
    return view('welcome');
});

Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('/verify-code', [LoginController::class, 'showVerificationForm'])->name('verify-code');
Route::post('/verify-code', [LoginController::class, 'verifyCode']);
Route::post('/verify-code/resend', [LoginController::class, 'resendCode'])->name('verify-code.resend');

Route::middleware('login.session')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
   Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::view('/home', 'home', ['title' => 'Home'])->name('home');
    Route::view('/form-generator', 'form-generator', ['title' => 'Form Generator (Monthly & Weekly)'])->name('form-generator');
    Route::view('/inquire-modify', 'inquire-modify', ['title' => 'Inquire / Modify'])->name('inquire-modify');
    Route::view('/de-listment', 'de-listment', ['title' => 'De-Listment'])->name('de-listment');
    Route::view('/superadmin-dashboard', 'superadmin-dashboard', ['title' => 'Superadmin Dashboard'])->name('superadmin-dashboard');
    Route::view('/superadmin-dashboard', 'superadmin-dashboard', ['title' => 'Superadmin Dashboard'])->name('superadmin-dashboard');


});