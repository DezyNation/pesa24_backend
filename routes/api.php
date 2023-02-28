<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Eko\BBPS\BBPSController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Eko\DMT\AgentCustomerController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;
use App\Http\Controllers\Razorpay\PayoutController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('users', UserController::class);
Route::post('users/otp', [UserController::class, 'otp']);
Route::post('users/verify-otp', [UserController::class, 'verifyOtp']);

Route::middleware(['auth:sanctum'])->group(function () {
    /*------------------------USER------------------------*/
    Route::post('user/update', [ProfileController::class, 'update']);
    Route::post('user/info', [ProfileController::class, 'info']);


    /*------------------------EKO AEPS------------------------*/
    Route::get('user-service-inquiry', [AepsApiController::class, 'userServiceInquiry']);
    Route::post('aeps-inquiry', [AepsApiController::class, 'aepsInquiry']);
    Route::post('fund-settlement', [AepsApiController::class, 'fundSettlement']);
    Route::post('aeps-inquiry', [AepsApiController::class, 'aepsInquiry']);

    /*------------------------EKO BBPS------------------------*/
    Route::get('eko/bbps/operators/categories', [BBPSController::class, 'operatorCategoryList']);
    Route::get('eko/bbps/operators/{category_id?}', [BBPSController::class, 'operators']);
    Route::get('eko/bbps/operators/fields/{operator_id}', [BBPSController::class, 'operatorField']);
    Route::post('eko/bbps/fetch-bill', [BBPSController::class, 'fetchBill']);

    /*------------------------EKO DMT------------------------*/

    Route::post('eko/dmt/customer-info', [CustomerRecipientController::class, 'customerInfo']);
    Route::post('eko/dmt/create-customer', [CustomerRecipientController::class, 'createCustomer']);
    Route::post('eko/dmt/resend-otp', [CustomerRecipientController::class, 'resendOtp']);
    Route::post('eko/dmt/verify-customer', [CustomerRecipientController::class, 'verifyCustomerIdentity']);

    Route::post('eko/bbps/fetch-bill', [BBPSController::class, 'fetchBill']);

    Route::get('eko/dmt/recipient-list', [CustomerRecipientController::class, 'recipientList']);
    Route::get('eko/dmt/recipient-details', [CustomerRecipientController::class, 'recipientDetails']);
    Route::post('eko/dmt/add-recipient', [CustomerRecipientController::class, 'addRecipient']);

    Route::get('eko/dmt/customer-info', [CustomerRecipientController::class, 'customerInfo']);
    Route::get('eko/dmt/register-agent', [AgentCustomerController::class, 'dmtRegistration']);
    Route::get('eko/dmt/fetch-agent', [AgentCustomerController::class, 'fetchAgent']);

    /*-----------------------Razorpay Payout-----------------------*/
    Route::post('razorpay/payout/fetch-payout', [PayoutController::class, 'fetchPayouts']);

});
    Route::get('razorpay/payout/make-payout', [PayoutController::class, 'bankPayout']);
Route::get('paysprint/bbps/mobile-operators', [RechargeController::class, 'operatorList']);
Route::get('paysprint/bbps/location', [RechargeController::class, 'location']);
Route::get('paysprint/bbps/mobile-recharge/hlr', [RechargeController::class, 'hlrCheck']);
Route::post('paysprint/bbps/mobile-recharge/browse', [RechargeController::class, 'browsePlans']);
Route::get('paysprint/bbps/mobile-recharge/parameter/{id}', [RechargeController::class, 'operatorParameter']);

Route::group(['middleware' => ['auth:sanctum', 'role:admin'], 'prefix' => 'admin'], function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
});