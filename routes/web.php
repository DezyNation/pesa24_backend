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
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Eko\DMT\AgentCustomerController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Eko\MoneyTransfer\MoneyTransferController;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;

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
Route::get('array-union', [BBPSController::class, 'arrayUn']);
Route::get('fund', [AepsApiController::class, 'bankSettlement']);
Route::get('initiate', [AepsApiController::class, 'initiateSettlement']);
Route::get('transaction-inquiry', [TransactionController::class, 'transactionInquiry']);
Route::get('refund-otp', [TransactionController::class, 'refundOtp']);
Route::get('fetch-agent', [AgentCustomerController::class, 'fetchAgent']);
Route::get('roles', function()
{
    $user = User::findOrFail(23)->roles;
    return $user;
});

Route::get('recipient', [CustomerRecipientController::class, 'recipientList']);
Route::get('recipient-details', [CustomerRecipientController::class, 'recipientDetails']);
Route::get('add-recipient', [CustomerRecipientController::class, 'addRecipient']);
require __DIR__.'/auth.php';