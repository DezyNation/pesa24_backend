<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Middleware\AdminLogin;
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
use App\Http\Controllers\Razorpay\WebhookController;
use App\Http\Controllers\Admin\FundRequestController;
use App\Http\Controllers\Paysprint\BBPS\LICController;
use App\Http\Controllers\Paysprint\CallbackController;
use App\Http\Controllers\Paysprint\BBPS\BillController;
use App\Http\Controllers\Pesa24\AttachServiceController;
use App\Http\Controllers\Pesa24\GlobalServiceController;
use App\Http\Controllers\Razorpay\FundAccountController;
use App\Http\Controllers\Eko\DMT\AgentCustomerController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Paysprint\AePS\AepsApiController;
use App\Http\Controllers\Eko\AePS\AepsApiController as EkoAepsApiController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Pesa24\Dashboard\UserDashboardController;
use App\Http\Controllers\pesa24\dashboard\AdminDashboardcontroller;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;
use App\Http\Controllers\Paysprint\AxisController;
use App\Http\Controllers\Paysprint\BBPS\FastTagController;
use App\Http\Controllers\Paysprint\CMS\AirtelCMSController;
use App\Http\Controllers\Paysprint\CMS\FinoCMSController;
use App\Http\Controllers\Paysprint\PANController;
use App\Http\Controllers\Paysprint\PayoutController as PaysprintPayout;

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
    Route::post('money-transfer', [PaysprintPayout::class, 'moneyTransfer'])->middleware(['mpin', 'minimum_balance']);
    Route::get('money-transfer', [PaysprintPayout::class, 'fetchMoneyTransfer']);
    Route::get('user/bank', [ProfileController::class, 'bank']);
    Route::get('user/services', [ProfileController::class, 'userServices']);
    Route::get('user/ledger/{name?}', [UserDashboardController::class, 'transactionLedger']);
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

    Route::get('user/kyc-status', function () {
        $table = DB::table('k_y_c_verifications')->where('user_id', auth()->user()->id)->get();
        if (!$table[0]->aadhar || !$table[0]->pan) {
            return false;
        }
        return true;
    });
    Route::post('user/pay/onboard-fee', [KycVerificationController::class, 'onboardFee'])->middleware(['profile']);

    /*-----------------------Tickets-----------------------*/
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('user/tickets', [TicketController::class, 'index']);
    Route::get('user/overview', [UserDashboardController::class, 'overView']);
    /*-----------------------Tickets-----------------------*/

    /*-----------------------Password and MPIN-----------------------*/
    Route::post('user/new-mpin', [ProfileController::class, 'newMpin']);
    Route::post('user/new-password', [ProfileController::class, 'newPass']);
    /*-----------------------Fund Requests-----------------------*/

    Route::post('fund/request-fund', [FundRequestController::class, 'fundRequest']);
    Route::get('fund/fetch-parents', [FundController::class, 'parents']);
    Route::get('cms-records', function () {
        $data = DB::table('cms_records')->where('user_id', auth()->user()->id)->latest()->get();
        return $data;
    });
    Route::get('fund/fetch-fund', [FundRequestController::class, 'fetchFundUser']);

    /*-----------------------Fund Requests-----------------------*/
    Route::get('transaction/{type}', [UserDashboardController::class, 'sunTransaction']);
});

Route::post('eko/aeps/money-transfer/{service_id}', [EkoAepsApiController::class, 'moneyTransfer'])->middleware(['auth:api', 'profile', 'kyc']);
Route::middleware(['auth:api', 'profile', 'minimum_balance', 'kyc'])->group(function () {
    /*-------------------------EKO ONBOARD-------------------------*/
    Route::get('eko/send-otp', [KycVerificationController::class, 'sendEkoOtp']);
    Route::get('eko-status', function() {
        $bool = is_null(auth()->user()->user_code);
        if ($bool) {
            return response(false);
        } else {
            return response(true);
        }
    });
    Route::get('eko/onbaord', [KycVerificationController::class, 'onboard']);
    Route::post('eko/verify-otp', [KycVerificationController::class, 'verifyMobile']);
    Route::get('eko/attach-service/{service_code}', [AttachServiceController::class, 'ekoActicvateService'])->middleware('eko');
    /*-------------------------EKO ONBOARD-------------------------*/

    /*------------------------EKO AEPS------------------------*/
    Route::get('eko/aeps/aeps-inquiry', [EkoAepsApiController::class, 'aepsInquiry'])->middleware('eko');
    // Route::get('fund-settlement', [EkoAepsApiController::class, 'fundSettlement']);
    Route::post('eko/aeps/mini-statement/{service_id}', [EkoAepsApiController::class, 'miniStatement'])->middleware('eko');
    Route::post('eko/aeps/balance-enquiry/{service_id}', [EkoAepsApiController::class, 'balanceEnquiry'])->middleware('eko');
    Route::get('eko/aeps/fetch-bank/{service_id}', [EkoAepsApiController::class, 'bankList'])->middleware('eko');
    Route::get('users/{id}', [ProfileController::class, 'findUser']);

    Route::post('activate-service/{service_id}', [AttachServiceController::class, 'attachService'])->middleware('subscribe');
    Route::post('eko/activate-service', [AgentManagementController::class, 'newService']);

    /*------------------------EKO BBPS------------------------*/
    Route::get('eko/bbps/operators/categories', [BBPSController::class, 'operatorCategoryList']);
    Route::get('eko/bbps/operators/{category_id?}', [BBPSController::class, 'operators']);
    Route::get('eko/bbps/operators/fields/{operator_id}', [BBPSController::class, 'operatorField']);
    Route::post('eko/bbps/fetch-bill', [BBPSController::class, 'fetchBill'])->middleware('eko');
    Route::post('eko/bbps/pay-bill/{service_code}', [BBPSController::class, 'paybill'])->middleware('mpin');

    /*------------------------EKO DMT------------------------*/

    Route::post('eko/dmt/customer-info/{service_code}', [CustomerRecipientController::class, 'customerInfo'])->middleware('eko');
    Route::post('eko/dmt/create-customer/{service_code}', [CustomerRecipientController::class, 'createCustomer'])->middleware('eko');
    Route::post('eko/dmt/resend-otp/{service_code}', [CustomerRecipientController::class, 'resendOtp'])->middleware('eko');
    Route::post('eko/dmt/verify-customer/{service_code}', [CustomerRecipientController::class, 'verifyCustomerIdentity'])->middleware('eko');

    Route::post('eko/bbps/fetch-bill', [BBPSController::class, 'fetchBill'])->middleware('eko');

    Route::get('eko/dmt/recipient-list/{service_code}', [CustomerRecipientController::class, 'recipientList'])->middleware('eko');
    Route::get('eko/dmt/recipient-details/{service_code}', [CustomerRecipientController::class, 'recipientDetails'])->middleware('eko');
    Route::post('eko/dmt/add-recipient/{service_code}', [CustomerRecipientController::class, 'addRecipient'])->middleware('eko');

    Route::get('eko/dmt/customer-info/{service_code}', [CustomerRecipientController::class, 'customerInfo'])->middleware('eko');
    // Route::post('eko/dmt/register-agent/{service_code}', [AgentCustomerController::class, 'dmtRegistration']);
    // Route::get('eko/dmt/fetch-agent/{service_code}', [AgentCustomerController::class, 'fetchAgent']);

    Route::post('eko/dmt/initiate-payment/{service_code}', [TransactionController::class, 'initiateTransaction'])->middleware('eko');
    Route::get('eko/dmt/transaction-inquiry/{transactionid}', [TransactionController::class, 'transactionInquiry'])->middleware('eko');
    // Route::post('eko/dmt/transaction-refund/{tid}', [TransactionController::class, 'refund']);
    // Route::post('eko/dmt/transaction-refund-otp/{tid}', [TransactionController::class, 'refund']);
    Route::post('paysprint/bank/bank-verify', [DMTController::class, 'penneyDrop']);
    /*-----------------------Razorpay Payout-----------------------*/
    Route::post('razorpay/payout/new-payout/{service_id}', [FundAccountController::class, 'createFundAcc']);
    Route::get('razorpay/fetch-payout/{service_id}', [PayoutController::class, 'fetchPayoutUser']);
    /*-----------------------Razorpay Payout-----------------------*/

    /*-----------------------Pysprint AePS-----------------------*/
    Route::post('paysprint/aeps/money-transfer/{service_id}', [AepsApiController::class, 'withdrwal'])->middleware('paysprint_merchant');
    Route::post('paysprint/aeps/mini-statement/{service_id}', [AepsApiController::class, 'miniStatement'])->middleware('paysprint_merchant');
    Route::post('paysprint/aeps/balance-enquiry/{service_id}', [AepsApiController::class, 'enquiry'])->middleware('paysprint_merchant');
    Route::post('paysprint/aeps/aadhaar-pay/{service_id}', [AepsApiController::class, 'aadhaarPay'])->middleware('paysprint_merchant');
    Route::get('paysprint/aeps/fetch-bank/{service_id}', [AepsApiController::class, 'bankList'])->middleware('paysprint_merchant');
    Route::post('paysprint/aeps/transaction-status/{service_id}', [AepsApiController::class, 'transactionStatus'])->middleware('paysprint_merchant');
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
    Route::get('paysprint/dmt/banks/{service_id}', [DMTController::class, 'dmtBanks']);

    Route::post('paysprint/dmt/recipient-list/{service_id}', [DMTController::class, 'fetchBeneficiary']);
    Route::post('paysprint/dmt/add-recipient/{service_id}', [DMTController::class, 'registerBeneficiary']);
    Route::post('paysprint/dmt/delete-recipient/{service_id}', [DMTController::class, 'deleteBeneficiary']);
    /*-----------------------Paysprint DMT-----------------------*/
    /*-----------------------Paysprint PAN-----------------------*/
    Route::post('paysprint/pan/start', [PANController::class, 'generateUrl']);
    /*-----------------------Paysprint PAN-----------------------*/


    /*-----------------------Paysprint BBPS-----------------------*/
    Route::get('paysprint/bbps/operators/categories/{id?}', [BillController::class, 'operatorParameter']);
    Route::post('paysprint/bbps/fetch-bill', [BillController::class, 'fetchBill']);
    Route::post('paysprint/lic/fetch-bill', [LICController::class, 'fetchBill']);
    Route::post('paysprint/bbps/pay-bill/{service_code}', [BillController::class, 'payBill'])->middleware('mpin');
    Route::post('paysprint/lic/pay-bill/{service_code?}', [LICController::class, 'payLicBill'])->middleware('mpin');
    /*-----------------------Paysprint BBPS-----------------------*/
    /*-----------------------Paysprint Recharge-----------------------*/
    Route::get('paysprint/bbps/mobile-operators/{type}', [RechargeController::class, 'operatorList']);
    Route::post('paysprint/bbps/mobile-recharge/browse', [RechargeController::class, 'browsePlans']);
    Route::post('paysprint/bbps/mobile-recharge/do-recharge', [RechargeController::class, 'doRecharge'])->middleware('mpin');
    /*-----------------------Paysprint Recharge-----------------------*/
    /*-----------------------Paysprint CMS-----------------------*/
    Route::post('paysprint/cms/fino', [FinoCMSController::class, 'generateUrl']);
    Route::post('paysprint/cms/airtel', [AirtelCMSController::class, 'generateUrl']);
    Route::post('paysprint/cms/status', [FinoCMSController::class, 'transactionStatus']);
    Route::get('cms-billers', [AdminController::class, 'getCmsBiller']);
    /*-----------------------Paysprint CMS-----------------------*/
    /*-----------------------Paysprint FastTAG-----------------------*/
    Route::get('paysprint/fastag/operators', [FastTagController::class, 'operatorList']);
    Route::post('paysprint/fastag/fetch-bill', [FastTagController::class, 'fetchConsumer']);
    Route::post('paysprint/fastag/pay-bill', [FastTagController::class, 'payAmount'])->middleware('mpin');
    Route::post('paysprint/fastag/status', [FastTagController::class, 'status']);
    /*-----------------------Paysprint FastTAG-----------------------*/
    /*-----------------------Paysprint Axis-----------------------*/
    Route::post('paysprint/axis/account', [AxisController::class, 'generateUcc']);
    /*-----------------------Paysprint Axis-----------------------*/
});

Route::get('admin/packages', [AdminController::class, 'packages'])->middleware(['auth:api', 'role:distributor|super_distributor|admin']);
Route::get('admin/all-users-list/{role}/{id?}', [UserController::class, 'userInfoPackage'])->middleware(['auth:api', 'role:distributor|super_distributor|admin']);
Route::post('admin/create/user', [UserController::class, 'store'])->middleware(['auth:api', 'role:distributor|super_distributor|admin']);
Route::get('parent/users-list/{role}/{id?}', [UserController::class, 'childUser'])->middleware(['auth:api', 'role:distributor|super_distributor']);
Route::get('parent/users-transactions/{id?}', [UserController::class, 'userReport'])->middleware(['auth:api', 'role:distributor|super_distributor']);
Route::group(['middleware' => ['auth:api', 'role:admin'], 'prefix' => 'admin'], function () {
    Route::get('razorpay/fetch-payout/{service_id}', [PayoutController::class, 'fetchPayoutUserAll']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('role-count/{role}', [AdminController::class, 'roleCount']);
    Route::get('sum-amounts', [AdminController::class, 'sumAmounts']);
    Route::get('overview', [AdminController::class, 'sumCategory']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::get('tickets', [TicketController::class, 'adminTicket']);
    Route::post('tickets', [TicketController::class, 'adminUpdateTicket']);
    Route::get('packages/{id}', [PackageController::class, 'parentPackage']);
    Route::get('get-users/{role_id}/{parent?}', [UserController::class, 'getUsers']);
    Route::get('users-list/{role}/{id?}', [UserController::class, 'userInfo']);
    // Route::get('all-users-list/{role}/{id?}', [UserController::class, 'userInfoPackage']);
    Route::get('user/status/{id}/{bool}', [UserController::class, 'active']);
    Route::post('link-package', [AdminDashboardcontroller::class, 'packageService']);

    Route::get('payouts', [PayoutController::class, 'fetchPayoutAdmin']);
    Route::get('fetch-fund-requests/{id}', [FundRequestController::class, 'fetchFundId']);

    Route::post('razorpay/fetch-payout', [PayoutController::class, 'fetchPayoutAdmin']);
    Route::post('user/info/{id}', [ProfileController::class, 'adminUser']);
    Route::get('fetch-fund-requests', [FundController::class, 'fetchFund']);
    Route::get('users-list/{role}', [AdminController::class, 'roleUser']);
    Route::get('logins/{count?}', [AdminController::class, 'logins']);

    Route::post('paysprint/payout/upload-documents', [PaysprintPayout::class, 'uploadDocuments']);
    Route::get('fetch-fund-requests/{id}', [FundController::class, 'fetchFundId']);
    Route::get('fetch-admin-funds', [FundController::class, 'reversalAndTransferFunds']);
    Route::post('update-fund-requests', [FundController::class, 'updateFund'])->middleware(['permission:fund-request-edit', 'minimum_balance']);
    Route::post('new-fund', [FundController::class, 'newFund'])->middleware(['permission:fund-transfer-create', 'minimum_balance', 'mpin']);
    Route::post('delete-fund', [FundController::class, 'deleteFund'])->middleware('permission:fund-transfer-create');


    Route::post('file', function (Request $address) {
        return Storage::download($address['address']);
    });
    Route::get('transactions-type/{data}', [AdminTransactionController::class, 'categoryIndex']);
    Route::get('transactions/{id?}', [AdminTransactionController::class, 'view']);
    Route::get('transactions-user/{id}', [AdminTransactionController::class, 'userTransction']);
    Route::post('transactions-period', [AdminTransactionController::class, 'dailySales']);
    Route::post('transactions-statistics', [AdminController::class, 'sumCategory']);
    Route::post('user/update', [ProfileController::class, 'adminUpdateProfile']);

    Route::post('paysprint/payout/add-account', [PaysprintPayout::class, 'addAccount']);
    Route::get('user/status/{id}/{bool}', [AdminController::class, 'active'])->middleware('permission:user-edit');
    Route::post('user/remarks', [AdminController::class, 'userRemarks'])->middleware('permission:user-edit');
    Route::get('settlement-accounts', [AdminController::class, 'settlementAccount']);
    // Route::post('parent-user', [AdminController::class, 'parentUser']);
    Route::post('change-role-parent', [AdminController::class, 'parentUser']);
    Route::get('change-role-parent', [AdminController::class, 'getRoleParent']);
    Route::post('remove-parent', [AdminController::class, 'removeParent']);
    Route::post('settlement-accounts', [AdminController::class, 'updateSettlementAccount']);
    Route::get('all-admins', [AdminController::class, 'admins'])->middleware('permission:assign-permission');
    Route::get('credential-remarks', function () {
        $data = auth()->user()->credential_remarks;
        return $data;
    });
    Route::post('new-admin', [AdminController::class, 'newAdmin'])->middleware('permission:assign-permission');
    Route::post('add-cms-billers', [AdminController::class, 'cmsBiller']);
    Route::delete('cms-biller/{id}', [AdminController::class, 'deleteCmsBiller']);
    Route::get('cms-billers', [AdminController::class, 'getCmsBiller']);
    Route::get('all-permissions', [AdminController::class, 'permissions'])->middleware('permission:assign-permission');
    Route::post('assign-permission', [AdminController::class, 'assignPermission'])->middleware('permission:assign-permission');
    Route::post('assign-package', [AdminController::class, 'assignPackage'])->middleware('permission:user-edit');
    Route::get('package-count/{id}', [AdminController::class, 'packageCount']);
    Route::get('user-permissions/{id}', [AdminController::class, 'userPermission']);

    Route::post('add-admin-funds', [AdminController::class, 'addAdminFunds'])->middleware('mpin');
    Route::get('add-admin-funds', [AdminController::class, 'adminFundsRecords']);
    Route::get('commissions', [AdminController::class, 'commissions']);
    Route::post('packages/delete/{id}', [AdminController::class, 'packagesId'])->middleware('mpin');
    Route::get('commissions/{name}/{id}', [AdminController::class, 'commissionsPackage']);
    Route::post('commissions/{name}', [AdminController::class, 'updateCommission']);
    Route::post('commissions/delete/{name}/{id}', [AdminController::class, 'deleteCommission']);
    Route::post('create-package', [AdminController::class, 'packageCreate']);
    Route::post('update-package-defaults', [AdminController::class, 'defaultPackage']);
});

Route::any('dmt-callback-paysprint', [CallbackController::class, 'dmtCallback']);
Route::any('payout-callback-paysprint', [WebhookController::class, 'confirmPayout']);
Route::any('onboard-callback-paysprint', [CallbackController::class, 'onboardCallback']);

Route::group(['middleware' => ['auth:api', 'role:admin'], 'prefix' => 'admin'], function () {
    Route::post('service-status', [GlobalServiceController::class, 'manageService']);
    Route::get('services', [GlobalServiceController::class, 'getServices']);
    Route::post('services', [GlobalServiceController::class, 'createService']);
    Route::post('categories', [GlobalServiceController::class, 'createCategories']);
    Route::get('categories', [GlobalServiceController::class, 'getCategories']);
    Route::post('delete-category', [GlobalServiceController::class, 'deleteCategory']);
    Route::post('operators', [GlobalServiceController::class, 'registerOperators']);
    Route::get('operators', [GlobalServiceController::class, 'getOperators']);
    Route::post('delete-operator', [GlobalServiceController::class, 'deleteOperator']);
    Route::post('create-organization', [GlobalServiceController::class, 'newOrganization']);
});

Route::get('transactions-period', [AdminTransactionController::class, 'dailySales']);
