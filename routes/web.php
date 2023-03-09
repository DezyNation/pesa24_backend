<?php

use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Paysprint\AePS\AepsApiController as PaysprintAeps;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Paysprint\LICController;
use App\Http\Controllers\Paysprint\LPGController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Razorpay\PayoutController;
use App\Http\Controllers\Razorpay\ContactController;
use Illuminate\Database\Eloquent\Builder;

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
    return phpinfo();
});

Route::get('/user', function () {
    $user = User::with('roles:name')->select('id')->findOrFail(23);
    return $user['roles'][0]['pivot']['minimum_balance'];
})->middleware('minimum_balance');

Route::get('lic-api', [LICController::class, 'fetchbill']);
Route::get('lic-api1', [LICController::class, 'payLicBill']);
Route::get('lpg-api', [LPGController::class, 'operatorList']);
Route::get('contact', [ContactController::class, 'createContact']);
Route::get('payout', [PayoutController::class, 'bankPayout']);
Route::get('hlr', [RechargeController::class, 'hlrCheck']);
Route::get('location', [RechargeController::class, 'location']);
Route::get('dmt', [KycVerificationController::class, 'sendOtpAadhaar']);
Route::get('inquiry', [PaysprintAeps::class, 'bankList']);
Route::get('test-transaction', [AepsApiController::class, 'testTransaction']);



Route::get('hello', function () {
    $parent = User::with(['packages.services' => function ($query) {
        $query->where('operator_type', 'like', '%withdrawal%');
    }, 'parents:id,name', 'roles:name'])->select('id')->findOrFail(23);
    return $parent;
});




require __DIR__ . '/auth.php';
