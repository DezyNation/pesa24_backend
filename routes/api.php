<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Eko\BBPS\BBPSController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;

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

    /*------------------------EKO AEPS------------------------*/
    Route::post('aeps-inquiry', [AepsApiController::class, 'aepsInquiry']);
    Route::post('fund-settlement', [AepsApiController::class, 'fundSettlement']);
    Route::get('user-service-inquiry', [AepsApiController::class, 'userServiceInquiry']);
    Route::post('aeps-inquiry', [AepsApiController::class, 'aepsInquiry']);

    /*------------------------EKO BBPS------------------------*/
    Route::get('eko/bbps/operators/categories', [BBPSController::class, 'operatorCategoryList']);
    Route::get('eko/bbps/operators/{category_id?}', [BBPSController::class, 'operators']);
    Route::get('eko/bbps/operators/fields/{operator_id}', [BBPSController::class, 'operatorField']);
    Route::post('eko/bbps/fetch-bill', [BBPSController::class, 'fetchBill']);
    /*------------------------EKO DMT------------------------*/ 
});
Route::get('paysprint/bbps/mobile-operators/{type}', [RechargeController::class, 'operatorList']);
Route::get('paysprint/bbps/mobile-recharge/hlr', [RechargeController::class, 'hlrCheck']);
Route::post('paysprint/bbps/mobile-recharge/browse', [RechargeController::class, 'browsePlans']);
Route::get('paysprint/bbps/mobile-recharge/parameter/{id}', [RechargeController::class, 'operatorParameter']);