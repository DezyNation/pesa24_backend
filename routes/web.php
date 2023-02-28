<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Paysprint\LICController;
use App\Http\Controllers\Paysprint\LPGController;
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

Route::get('lic-api', [LICController::class, 'fetchbill']);
Route::get('lic-api1', [LICController::class, 'payLicBill']);
Route::get('lpg-api', [LPGController::class, 'operatorList']);
Route::get('contact', [ContactController::class, 'createContact']);
Route::get('payout', [PayoutController::class, 'bankPayout']);

Route::get('logic', function () {
    // $money = 1000;
    // $trxnamt = 200;
    // $user = User::with(['package.commissions' => function ($query) {
    //     $query->where(['operator_name' => 'jio', 'operator_type' => 'mobile postpaid']);
    // }])->select('name', 'package_id')->findOrFail(23);

    // $commission = $user['package']['commissions'][0]['pivot']['commission'];
    // $surcharge = $user['package']['commissions'][0]['pivot']['surcharge'];

    // return $user;
    $response = Http::post('http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=9971412064&sender=PESATE&message=$Message', [
    ]);

    return $response;
});



require __DIR__ . '/auth.php';
