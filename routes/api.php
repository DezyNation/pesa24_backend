<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Pesa24\FundController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Eko\BBPS\BBPSController;
use App\Http\Controllers\Pesa24\TicketController;
use App\Http\Controllers\Razorpay\PayoutController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Razorpay\ContactController;
use App\Http\Controllers\Admin\FundRequestController;
use App\Http\Controllers\Razorpay\FundAccountController;
use App\Http\Controllers\Eko\DMT\AgentCustomerController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;
use App\Http\Controllers\Pesa24\AttachServiceController;
use App\Http\Controllers\pesa24\Dashboard\UserDashboardController;
use App\Http\Controllers\Pesa24\GlobalServiceController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

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

Route::middleware(['auth:api'])->get('/user', function (Request $request) {
    return $request->user();
});

// Route::resource('users', UserController::class);
Route::post('users/otp', [UserController::class, 'otp']);
Route::post('users/verify-otp', [UserController::class, 'verifyOtp']);
Route::get('services', [Controller::class, 'index']);

Route::middleware(['auth:api'])->group(function () {
    /*------------------------USER------------------------*/
    Route::post('user/update', [ProfileController::class, 'update']);
    Route::post('user/info', [ProfileController::class, 'info']);
    Route::get('user/services', [ProfileController::class, 'userServices']);
    Route::post('user/wallet', [ProfileController::class, 'wallet']);
    Route::post('user/verify/aadhaar/send-otp', [KycVerificationController::class, 'sendOtpAadhaar']);
    Route::post('user/verify/aadhaar/verify-otp', [KycVerificationController::class, 'verifyOtpAadhaar']);
    Route::post('user/verify/pan/verify-pan', [KycVerificationController::class, 'panVerification']);
    Route::get('user/check/onboard-fee', function () {
        $data = json_decode(DB::table('users')->where('id', auth()->user()->id)->get('onboard_fee'), true);
        $user = User::findOrFail(auth()->user()->id)->makeVisible(['organization_id', 'wallet']);
        $role = $user->getRoleNames();
        $role_details = json_decode(DB::table('roles')->where('name', $role[0])->get(['fee']), true);
        $arr = array_merge($data, $role_details);
        return $arr;
    });
    Route::get('user/pay/onboard-fee', [KycVerificationController::class, 'onboardFee']);

    /*-----------------------Tickets-----------------------*/
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets', [TicketController::class, 'index']);
    Route::get('tickets/user/{id}', [TicketController::class, 'userTicket']);
    Route::get('tickets/{id}', [TicketController::class, 'ticket']);
    Route::post('tickets/{id}', [TicketController::class, 'update']);
    /*-----------------------Tickets-----------------------*/

    /*-----------------------Password and MPIN-----------------------*/
    Route::post('user/new-mpin', [ProfileController::class, 'newMpin']);
    Route::post('user/new-password', [ProfileController::class, 'newPass']);
    /*-----------------------Fund Requests-----------------------*/
    
    Route::post('fund/request-fund', [FundRequestController::class, 'fundRequest']);
    Route::get('fund/fetch-parents', [FundController::class, 'parents']);
    Route::get('fund/fetch-fund', [FundRequestController::class, 'fetchFundUser']);
    
    /*-----------------------Fund Requests-----------------------*/
    Route::get('transaction/{type}', [UserDashboardController::class, 'sunTransaction']);
});

Route::middleware(['auth:api', 'onboard', 'minimum_balance'])->group(function () {
    /*------------------------EKO AEPS------------------------*/
    Route::post('eko/aeps/aeps-inquiry/{service_id}', [AepsApiController::class, 'aepsInquiry']);
    Route::post('fund-settlement/{service_id}', [AepsApiController::class, 'fundSettlement']);
    Route::post('eko/aeps/money-transfer/{service_id}', [AepsApiController::class, 'moneyTransfer']);

    Route::post('activate-service/{service_id}', [AttachServiceController::class, 'attachService'])->middleware('subscribe');

    /*------------------------EKO BBPS------------------------*/
    Route::get('eko/bbps/operators/categories/{service_id}', [BBPSController::class, 'operatorCategoryList']);
    Route::get('eko/bbps/operators/{category_id?}/{service_id}', [BBPSController::class, 'operators']);
    Route::get('eko/bbps/operators/fields/{operator_id}/{service_id}', [BBPSController::class, 'operatorField']);
    Route::post('eko/bbps/fetch-bill/{service_id}', [BBPSController::class, 'fetchBill']);

    /*------------------------EKO DMT------------------------*/

    Route::post('eko/dmt/customer-info/{service_id}', [CustomerRecipientController::class, 'customerInfo']);
    Route::post('eko/dmt/create-customer/{service_id}', [CustomerRecipientController::class, 'createCustomer']);
    Route::post('eko/dmt/resend-otp/{service_id}', [CustomerRecipientController::class, 'resendOtp']);
    Route::post('eko/dmt/verify-customer/{service_id}', [CustomerRecipientController::class, 'verifyCustomerIdentity']);

    Route::post('eko/bbps/fetch-bill/{service_id}', [BBPSController::class, 'fetchBill']);

    Route::get('eko/dmt/recipient-list/{service_id}', [CustomerRecipientController::class, 'recipientList']);
    Route::get('eko/dmt/recipient-details/{service_id}', [CustomerRecipientController::class, 'recipientDetails']);
    Route::post('eko/dmt/add-recipient/{service_id}', [CustomerRecipientController::class, 'addRecipient']);

    Route::get('eko/dmt/customer-info/{service_id}', [CustomerRecipientController::class, 'customerInfo']);
    Route::post('eko/dmt/register-agent/{service_id}', [AgentCustomerController::class, 'dmtRegistration']);
    Route::get('eko/dmt/fetch-agent/{service_id}', [AgentCustomerController::class, 'fetchAgent']);

    Route::post('eko/dmt/initiate-payment/{service_id}', [TransactionController::class, 'initiatePayment']);
    Route::get('eko/dmt/transaction-inquiry/{transactionid}/{service_id}', [TransactionController::class, 'transactionInquiry']);
    Route::post('eko/dmt/transaction-refund/{tid}/{service_id}', [TransactionController::class, 'refund']);
    Route::post('eko/dmt/transaction-refund-otp/{tid}/{service_id}', [TransactionController::class, 'refund']);

    /*-----------------------Razorpay Payout-----------------------*/
    Route::post('razorpay/payout/new-payout/{service_id}', [FundAccountController::class, 'createFundAcc']);
    Route::post('razorpay/contacts/create-contact/{service_id}', [PayoutController::class, 'fetchPayoutAdmin']);
    
    /*-----------------------Paysprint Recharge-----------------------*/
    Route::get('paysprint/bbps/mobile-operators/{type}', [RechargeController::class, 'operatorList']);
    Route::get('paysprint/bbps/mobile-operators/parameter/{id}', [RechargeController::class, 'operatorParameter']);
    Route::post('paysprint/bbps/mobile-recharge/browse', [RechargeController::class, 'browsePlans']);
    Route::post('paysprint/bbps/mobile-recharge/do-recharge', [RechargeController::class, 'doRecharge']);
});
Route::get('razorpay/fetch-payout/{service_id}', [PayoutController::class, 'fetchPayoutUserAll']);

Route::group(['middleware' => ['auth:api', 'role:admin'], 'prefix' => 'admin'], function () {
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
    

});

Route::group(['middleware' => ['auth:api', 'role:super_admin'], 'prefix' => 'super-admin'], function(){
    Route::get('service-chage/{service_id}/{active}', [GlobalServiceController::class, 'manageService']);
});
