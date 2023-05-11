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
    $user = DB::table('user_parent')
        ->join('users', 'users.id', '=', 'user_parent.user_id')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where(['user_parent.parent_id' => 85, 'roles.name' => 'retailer'])
        ->select('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.alternate_phone', 'users.line', 'users.line', 'users.city', 'users.state', 'users.pincode', 'users.wallet', 'users.minimum_balance', 'users.kyc', 'roles.name', 'users.aadhar_front', 'users.aadhar_back', 'users.pan_photo')
        ->get();
    return  $user;
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
