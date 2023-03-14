<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Pesa24\FundController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Eko\BBPS\BBPSController;
use App\Http\Controllers\Razorpay\PayoutController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Razorpay\ContactController;
use App\Http\Controllers\Admin\FundRequestController;
use App\Http\Controllers\Razorpay\FundAccountController;
use App\Http\Controllers\Eko\DMT\AgentCustomerController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;

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
    Route::post('user/wallet', [ProfileController::class, 'wallet']);

    /*-----------------------Password and MPIN-----------------------*/
    Route::post('new-mpin', [ProfileController::class, 'newMpin']);
    Route::post('new-mpin', [ProfileController::class, 'newPass']);

    /*------------------------EKO AEPS------------------------*/
    Route::get('user-service-inquiry', [AepsApiController::class, 'userServiceInquiry']);
    Route::post('aeps-inquiry', [AepsApiController::class, 'aepsInquiry']);
    Route::post('fund-settlement', [AepsApiController::class, 'fundSettlement']);
    Route::post('eko/aeps/money-transfer', [AepsApiController::class, 'moneyTransfer']);

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

    Route::post('eko/dmt/initiate-payment', [TransactionController::class, 'initiatePayment']);
    Route::get('eko/dmt/transaction-inquiry/{transactionid}', [TransactionController::class, 'transactionInquiry']);
    Route::post('eko/dmt/transaction-refund/{tid}', [TransactionController::class, 'refund']);
    Route::post('eko/dmt/transaction-refund-otp/{tid}', [TransactionController::class, 'refund']);

    /*-----------------------Razorpay Payout-----------------------*/
    Route::post('razorpay/contacts/create-contact', [FundAccountController::class, 'createFundAcc']);
    Route::post('razorpay/contacts/create-contact', [PayoutController::class, 'fetchPayoutAdmin']);

    /*-----------------------Paysprint Recharge-----------------------*/
    Route::get('paysprint/bbps/mobile-operators/{type}', [RechargeController::class, 'operatorList']);
    Route::get('paysprint/bbps/mobile-operators/parameter/{id}', [RechargeController::class, 'operatorParameter']);
    Route::post('paysprint/bbps/mobile-recharge/browse', [RechargeController::class, 'browsePlans']);
    Route::post('paysprint/bbps/mobile-recharge/do-recharge', [RechargeController::class, 'doRecharge']);
    /*-----------------------Fund Requests-----------------------*/
    
    Route::post('fund/request-fund', [FundRequestController::class, 'fundRequest']);
    Route::get('fund/fetch-parents', [FundController::class, 'parents']);
    Route::get('fund/fetch-fund', [FundRequestController::class, 'fetchFundUser']);
});

Route::group(['middleware' => ['auth:sanctum', 'role:admin'], 'prefix' => 'admin'], function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::post('create/user', [UserController::class, 'store']);
    Route::get('packages/{id}', [PackageController::class, 'parentPackage']);
    Route::get('get-users/{role_id}/{parent?}', [UserController::class, 'getUsers']);
    Route::get('users-list/{role}/{id?}', [UserController::class, 'userInfo']);
    Route::get('user/status/{id}/{bool}', [UserController::class, 'active']);

    Route::get('payouts', [PayoutController::class, 'fetchPayoutAdmin']);

    Route::get('fetch-fund-requests', [FundRequestController::class, 'fetchFund']);
    Route::get('fetch-fund-requests/{id}', [FundRequestController::class, 'fetchFundId']);
    Route::post('update-fund-requests', [FundRequestController::class, 'updateFund']);

    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
});

//Line 78 'api/fund/request-fund' --> for fund request
//Line 93 'api/admin/fetch-fund-requests' --> get all fund requests
//Line 94 'api/admin/fetch-fund-requests/{if}' --> get specific fund request
//Line 94 'api/admin/update-fund-requests' --> update-fund-request
