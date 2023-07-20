<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'normal', 'active'])
    ->name('login');

Route::post('admin/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'admin', 'active'])
    ->name('login');

Route::post('/send-otp', [AuthenticatedSessionController::class, 'sendOtp'])
    ->middleware(['guest', 'active']);

Route::post('password/send-otp', [AuthenticatedSessionController::class, 'passOtp'])
    ->middleware('auth:api', 'active');

Route::post('admin-register', [RegisteredUserController::class, 'registerAdmin'])->middleware(['permission:user-create', 'active']);
Route::post('admin-update-user', [RegisteredUserController::class, 'adminUpdate'])->middleware(['auth:api','permission:user-edit', 'active']);
Route::post('admin-send-creds', [PasswordResetLinkController::class, 'adminSendCreds'])->middleware(['auth:api','permission:user-edit', 'active']);

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
