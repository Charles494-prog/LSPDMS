<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordsController;
use App\Http\Controllers\SuperadminController;
use Illuminate\Support\Facades\Route;

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
Route::get('/forms/weekly', function () {
    return '<h1>Weekly Forms Page (placeholder)</h1>';
})->name('forms.weekly');

Route::get('/forms/monthly', function () {
    return '<h1>Monthly Forms Page (placeholder)</h1>';
})->name('forms.monthly');

Route::middleware('login.session')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/records/search', [RecordsController::class, 'search'])->name('records.search');
    Route::get('/records/smart-helper', [RecordsController::class, 'smartHelper'])->name('records.smart-helper');
    Route::delete('/records/{record}', [RecordsController::class, 'destroy'])->name('records.destroy');
    Route::put('/records/{record}', [RecordsController::class, 'update'])->name('records.update');
    Route::get('/superadmin-dashboard', [SuperadminController::class, 'index'])->name('superadmin-dashboard');
    Route::put('/superadmin/users/{user}', [SuperadminController::class, 'updateUser'])->name('superadmin.users.update');
    Route::put('/superadmin/permissions', [SuperadminController::class, 'updatePermissions'])->name('superadmin.permissions.update');
    Route::put('/superadmin/signatories', [SuperadminController::class, 'updateSignatories'])->name('superadmin.signatories.update');
    Route::put('/superadmin/global-variables', [SuperadminController::class, 'updateGlobalVariables'])->name('superadmin.global-variables.update');
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::view('/home', 'home', ['title' => 'Home'])->name('home');
    Route::view('/form-generator', 'form-generator', ['title' => 'Form Generator (Monthly & Weekly)'])->name('form-generator');
    Route::view('/inquire-modify', 'inquire-modify', ['title' => 'Inquire / Modify'])->name('inquire-modify');
    Route::view('/de-listment', 'de-listment', ['title' => 'De-Listment'])->name('de-listment');
});
