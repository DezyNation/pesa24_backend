<?php

use App\Models\User;
use App\Models\Package;
use App\Models\ParentUser;
use Illuminate\Support\Str;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\Paysprint\BBPS\LICController;
use App\Http\Controllers\Paysprint\LPGController;
use App\Http\Controllers\Razorpay\PayoutController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Razorpay\ContactController;
use App\Http\Controllers\Paysprint\OnboardController;
use App\Http\Controllers\Paysprint\BBPS\BillController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Paysprint\PayoutController as PaysprintPayout;
use App\Http\Controllers\Paysprint\AePS\AepsApiController as PaysprintAeps;
use App\Http\Controllers\Paysprint\CMS\AirtelCMSController;
use App\Http\Controllers\Paysprint\PANController;

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
    return "Pesa24";
});

Route::get('lic', [PANController::class, 'generateUrl']);
Route::get('cms', [AirtelCMSController::class, 'transactionStatus']);
Route::prefix('commissions')->group(function () {
    // Route::get('aeps-mini/{user_id}', [CommissionController::class, 'aepsMiniComission']);
    // Route::get('dmt/{user_id}/{amount}', [CommissionController::class, 'dmtCommission']);
    // Route::get('recharge/{user_id}/{operator}/{amount}', [CommissionController::class, 'rechargeCommissionPaysprint']);
    // Route::get('bbps/{user_id}/{operator}/{amount}', [CommissionController::class, 'bbpsPaysprintCommission']);
});




require __DIR__ . '/auth.php';
