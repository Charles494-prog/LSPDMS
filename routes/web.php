<?php

use App\Http\Controllers\LoginController;
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
