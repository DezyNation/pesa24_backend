<?php

use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use App\Models\Package;
use App\Models\ParentUser;
use Illuminate\Support\Str;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\Eko\BBPS\BBPSController;
use App\Http\Controllers\Paysprint\LPGController;
use App\Http\Controllers\Paysprint\PANController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Razorpay\ContactController;
use App\Http\Controllers\Paysprint\OnboardController;
use App\Http\Controllers\Paysprint\BBPS\LICController;
use App\Http\Controllers\Paysprint\BBPS\BillController;
use App\Http\Controllers\Pesa24\AttachServiceController;
use App\Http\Controllers\Paysprint\BBPS\FastTagController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Paysprint\CMS\AirtelCMSController;
use App\Http\Controllers\Eko\MoneyTransfer\PayoutController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Pesa24\Dashboard\UserDashboardController;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;
use App\Http\Controllers\Paysprint\PayoutController as PaysprintPayout;
use App\Http\Controllers\Paysprint\AePS\AepsApiController as PaysprintAeps;
use App\Http\Controllers\Razorpay\PayoutController as RazorpayPayoutController;
use App\Http\Controllers\Eko\MoneyTransfer\PayoutController as MoneyTransferPayoutController;

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
    return ['Application' => 'Janpay'];
});

// Route::get('test', [AdminController::class, 'marketOverview']);

// Route::get('inquiry', [BBPSController::class, 'payBill']);
// Route::get('dmt', [TransactionController::class, 'initiateTransaction']);
// Route::get('pan', [BBPSController::class, 'payBill']);

require __DIR__ . '/auth.php';
