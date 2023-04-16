<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Pesa24\FundController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Eko\BBPS\BBPSController;
use App\Http\Controllers\Paysprint\DMTController;
use App\Http\Controllers\Pesa24\TicketController;
use App\Http\Controllers\Razorpay\PayoutController;
use App\Http\Controllers\Razorpay\ContactController;
use App\Http\Controllers\Admin\FundRequestController;
use App\Http\Controllers\Pesa24\AttachServiceController;
use App\Http\Controllers\Pesa24\GlobalServiceController;
use App\Http\Controllers\Razorpay\FundAccountController;
use App\Http\Controllers\Eko\DMT\AgentCustomerController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Paysprint\AePS\AepsApiController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\pesa24\Dashboard\UserDashboardController;
use App\Http\Controllers\pesa24\dashboard\AdminDashboardcontroller;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;
use App\Http\Controllers\Paysprint\CallbackController;
use App\Http\Controllers\Paysprint\PayoutController as PaysprintPayout;
use App\Http\Middleware\AdminLogin;

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
    Route::post('user/add-bank', [ProfileController::class, 'addBank']);
    Route::post('user/info', [ProfileController::class, 'info']);
    Route::post('money-transfer', [PaysprintPayout::class, 'moneyTransfer'])->middleware('mpin');
    Route::get('user/bank', [ProfileController::class, 'bank']);
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
    Route::post('user/pay/onboard-fee', [KycVerificationController::class, 'onboardFee'])->middleware(['profile', 'minimum_balance']);

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

Route::middleware(['auth:api', 'profile', 'minimum_balance', 'onboard'])->group(function () {
    /*------------------------EKO AEPS------------------------*/
    Route::post('eko/aeps/aeps-inquiry/{service_id}', [AepsApiController::class, 'aepsInquiry']);
    Route::post('fund-settlement/{service_id}', [AepsApiController::class, 'fundSettlement']);
    Route::post('eko/aeps/money-transfer/{service_id}', [AepsApiController::class, 'moneyTransfer']);
    Route::get('users/{id}', [ProfileController::class, 'findUser']);

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
    Route::post('paysprint/bank/bank-verify', [DMTController::class, 'penneyDrop']);
    /*-----------------------Razorpay Payout-----------------------*/
    Route::post('razorpay/payout/new-payout/{service_id}', [FundAccountController::class, 'createFundAcc']);
    Route::get('razorpay/fetch-payout/{service_id}', [PayoutController::class, 'fetchPayoutUser']);
    /*-----------------------Razorpay Payout-----------------------*/

    /*-----------------------Pysprint AePS-----------------------*/
    Route::post('paysprint/aeps/money-transfer/{service_id}', [AepsApiController::class, 'withdrwal']);
    Route::get('paysprint/aeps/fetch-bank/{service_id}', [AepsApiController::class, 'bankList']);
    Route::get('paysprint/aeps/transaction-status/{service_id}', [AepsApiController::class, 'transactionStatus']);
    /*-----------------------Pysprint AePS-----------------------*/

    /*-----------------------Paysprint Payout-----------------------*/
    Route::post('paysprint/payout/account-status/{service_id}', [PaysprintPayout::class, 'accountStatus']);
    Route::post('paysprint/payout/new-payout', [PaysprintPayout::class, 'doTransaction'])->middleware('bank', 'mpin');
    Route::post('paysprint/payout/transaction-status/{service_id}', [PaysprintPayout::class, 'status']);
    /*-----------------------Paysprint Payout-----------------------*/

    /*-----------------------Paysprint DMT-----------------------*/
    Route::post('paysprint/dmt/customer-info/{service_id}', [DMTController::class, 'remiterQuery']);
    Route::post('paysprint/dmt/create-customer/{service_id}', [DMTController::class, 'registerRemiter']);

    Route::post('paysprint/dmt/initiate-payment/{service_id}', [DMTController::class, 'newTransaction'])->middleware('mpin');

    Route::post('paysprint/dmt/recipient-list/{service_id}', [DMTController::class, 'fetchBeneficiary']);
    Route::post('paysprint/dmt/add-recipient/{service_id}', [DMTController::class, 'registerBeneficiary']);
    Route::post('paysprint/dmt/delete-recipient/{service_id}', [DMTController::class, 'deleteBeneficiary']);
    /*-----------------------Paysprint DMT-----------------------*/


    /*-----------------------Paysprint Recharge-----------------------*/
    Route::get('paysprint/bbps/mobile-operators/{type}', [RechargeController::class, 'operatorList']);
    Route::get('paysprint/bbps/mobile-operators/parameter/{id}', [RechargeController::class, 'operatorParameter']);
    Route::post('paysprint/bbps/mobile-recharge/browse', [RechargeController::class, 'browsePlans']);
    Route::post('paysprint/bbps/mobile-recharge/do-recharge', [RechargeController::class, 'doRecharge']);
    /*-----------------------Paysprint Recharge-----------------------*/
});

Route::group(['middleware' => ['auth:api', 'role:admin'], 'prefix' => 'admin'], function () {
    Route::get('razorpay/fetch-payout/{service_id}', [PayoutController::class, 'fetchPayoutUserAll']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::post('create/user', [UserController::class, 'store']);
    Route::get('packages/{id}', [PackageController::class, 'parentPackage']);
    Route::get('get-users/{role_id}/{parent?}', [UserController::class, 'getUsers']);
    Route::get('users-list/{role}/{id?}', [UserController::class, 'userInfo']);
    Route::get('all-users-list/{role}/{id?}', [UserController::class, 'userInfoPackage']);
    Route::get('user/status/{id}/{bool}', [UserController::class, 'active']);
    Route::post('link-package', [AdminDashboardcontroller::class, 'packageService']);

    Route::get('payouts', [PayoutController::class, 'fetchPayoutAdmin']);
    Route::get('fetch-fund-requests/{id}', [FundRequestController::class, 'fetchFundId']);

    Route::post('razorpay/fetch-payout', [PayoutController::class, 'fetchPayoutAdmin']);
    Route::post('user/info/{id}', [ProfileController::class, 'adminUser']);
    Route::get('fetch-fund-requests', [FundController::class, 'fetchFund']);
    Route::get('users-list/{role}', [AdminController::class, 'roleUser']);
    Route::get('logins/{count?}', [AdminController::class, 'logins']);

    Route::post('paysprint/payout/upload-documents', [PaysprintPayout::class, 'documents']);
    Route::get('fetch-fund-requests/{id}', [FundController::class, 'fetchFundId']);
    Route::get('fetch-admin-funds', [FundController::class, 'reversalAndTransferFunds']);
    Route::post('update-fund-requests', [FundController::class, 'updateFund'])->middleware(['permission:fund-request-edit', 'minimum_balance']);
    Route::post('new-fund', [FundController::class, 'newFund'])->middleware(['permission:fund-transfer-create', 'minimum_balance']);
    Route::post('delete-fund', [FundController::class, 'deleteFund'])->middleware('permission:fund-transfer-create');


    Route::post('file', function (Request $address) {
        return Storage::download($address['address']);
    });
    Route::get('transactions-type/{data}', [AdminTransactionController::class, 'categoryIndex']);
    Route::get('transactions/{id}', [AdminTransactionController::class, 'view']);
    Route::get('transactions-user/{id}', [AdminTransactionController::class, 'userTransction']);
    Route::get('transactions-period', [AdminTransactionController::class, 'transactionPeriod']);

    Route::post('paysprint/payout/add-account', [PaysprintPayout::class, 'addAccount']);
    Route::get('user/status/{id}/{bool}', [AdminController::class, 'active'])->middleware('permission:user-edit');
    Route::get('settlement-accounts', [AdminController::class, 'settlementAccount']);
    Route::post('settlement-accounts', [AdminController::class, 'updateSettlementAccount']);
    Route::get('all-admins', [AdminController::class, 'admins'])->middleware('permission:assign-permission');
    Route::post('new-admin', [AdminController::class, 'newAdmin'])->middleware('permission:assign-permission');
    Route::get('all-permissions', [AdminController::class, 'permissions'])->middleware('permission:assign-permission');
    Route::post('assign-permission', [AdminController::class, 'assignPermission'])->middleware('permission:assign-permission');

    Route::post('add-admin-funds', [AdminController::class, 'addAdminFunds'])->middleware('mpin');
    Route::get('add-admin-funds', [AdminController::class, 'adminFundsRecords']);
    Route::get('commissions', [AdminController::class, 'commissions']);
    Route::get('packages', [AdminController::class, 'packages']);
    Route::post('packages/{id}', [AdminController::class, 'packagesId']);
    Route::get('commissions/{name}/{id}', [AdminController::class, 'commissionsPackage']);
    Route::post('commissions/{name}', [AdminController::class, 'updateCommission']);
    Route::post('create-package', [AdminController::class, 'packageCreate']);
    Route::post('update-package-defaults', [AdminController::class, 'packageSwitch']);
});

Route::any('dmt-callback-paysprint', [CallbackController::class, 'dmtCallback']);

Route::group(['middleware' => ['auth:api', 'role:super_admin'], 'prefix' => 'super-admin'], function () {
    Route::get('service-chage/{service_id}/{active}', [GlobalServiceController::class, 'manageService']);
});
