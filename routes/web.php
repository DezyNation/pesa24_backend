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

Route::get('logic', function () {
    // $money = 1000;
    // $trxnamt = 200;
    // $user = User::with(['package.commissions' => function ($query) {
    //     $query->where(['operator_name' => 'jio', 'operator_type' => 'mobile postpaid']);
    // }])->select('name', 'package_id')->findOrFail(23);

    // $commission = $user['package']['commissions'][0]['pivot']['commission'];
    // $surcharge = $user['package']['commissions'][0]['pivot']['surcharge'];

    // $payout = DB::table('payouts')->where('user_id', 23)->get([
    //     'payout_id',
    //     'amount',
    //     'created_at'
    // ]);

    // User::where('id', 23)->update([
    //     'password' => Hash::make('@60Kmph00'),
    //     'mpin' => Hash::make('4742')
    // ]);

    // return 'dome';
});




require __DIR__ . '/auth.php';
