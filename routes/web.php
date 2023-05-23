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
use App\Http\Controllers\Paysprint\BBPS\FastTagController;
use App\Http\Controllers\Paysprint\CMS\AirtelCMSController;
use App\Http\Controllers\Paysprint\PANController;
use Illuminate\Support\Facades\Artisan;

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
    // $arr = [
    //     9971412064,
    //     9971412098
    // ];

    // $arr = json_encode($arr);
    // $data = DB::table('organizations')->where('id', 7)->get('authorised_numbers');
    // // $data = json_decode($data, true);
    // return json_decode($data[0]->authorised_numbers);
    // User::where('id', 78)->update([
    //     'mpin' => Hash::make(1234)
    // ]);
    // $request['user_id'] = 98;
    //     $bool = DB::table('user_parent')->where(['parent_id' => 85, 'user_id' => $request['user_id']]);
    //     if ($bool->exists()) {
    //         $data = DB::table('transactions')
    //         ->join('users', 'users.id', '=', 'transactions.trigered_by')
    //         ->where('trigered_by', $request['user_id'])
    //         ->select('users.name', 'transactions.*')
    //         ->get();
    //     }
    // // } else {
    //     $data = DB::table('transactions')
    //     ->join('user_parent', 'user_parent.user_id', '=','transactions.trigered_by')
    //     ->join('users', 'users.id', '=', 'transactions.trigered_by')
    //     ->where('user_parent.parent_id', 85)
    //     ->select('users.name', 'transactions.*')
    //     ->get();
    // // }

    // return $data;
});

Route::get('licCommission/{user_id}/{amount}', [CommissionController::class, 'licCommission']);
// Route::get('cms', [AirtelCMSController::class, 'transactionStatus']);
// Route::prefix('commissions')->group(function () {
//     // Route::get('aeps-mini/{user_id}', [CommissionController::class, 'aepsMiniComission']);
//     // Route::get('dmt/{user_id}/{amount}', [CommissionController::class, 'dmtCommission']);
//     // Route::get('recharge/{user_id}/{operator}/{amount}', [CommissionController::class, 'rechargeCommissionPaysprint']);
//     // Route::get('bbps/{user_id}/{operator}/{amount}', [CommissionController::class, 'bbpsPaysprintCommission']);
// });




require __DIR__ . '/auth.php';
