<?php

use App\Models\User;
use App\Mail\SendOtp;
use Illuminate\Support\Str;
use Illuminate\Support\Benchmark;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use App\Http\Controllers\Eko\BBPS\BBPSController;
use App\Http\Controllers\KycVerificationController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Eko\MoneyTransfer\MoneyTransferController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('test', [AgentManagementController::class, 'userOnboard']);
Route::get('kyc', [KycVerificationController::class, 'panVerification']);
Route::get('customer', [MoneyTransferController::class, 'createCustomer']);
Route::get('resend-otp', [MoneyTransferController::class, 'resendOtp']);
Route::get('transaction', [TransactionController::class, 'initiateTransaction']);
Route::get('operator/{id}', [BBPSController::class, 'operatorCategoryList']);
Route::get('operators', [BBPSController::class, 'operators']);
Route::get('array-union', [BBPSController::class, 'arrayUn']);
Route::get('roles', function()
{

   $user = User::findOrFail(23)->roles;
//    dd($user);
return $user;

});

Route::get('mail', function()
{
    $otp = 5475;
    $user = User::findOrFail(23);
    Benchmark::dd([
        // fn() => Mail::to($user->email)->send(new SendOtp($otp)),
        fn() => Mail::to($user->email)->queue(new SendOtp($otp)),
      ], 3);
    
// return $user;

});
require __DIR__.'/auth.php';
