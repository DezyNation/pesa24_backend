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
use App\Http\Controllers\Paysprint\LICController;
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


// Route::get('logic', function () {
//     $organization = 'AMV45';
//     $test = Organization::with(['packages.users' => function ($q) {
//         $q->paginate(1);
//     }])->where('code', $organization)->get();
//     # http://127.0.0.1:8000/logic?page=1
//     echo $test;
// });

// Route::get('policy', function () {
//     $user = User::with('parents')->find(56)->can('view', User::with('parents')->find(55));

//     if ($user) {
//         return 'True';
//     } else {

//         return 'False';
//     }
// });

Route::get('paysprint-test', [PaysprintAeps::class, 'aadhaarPay']);
Route::get('bill-test/{id?}', [BillController::class, 'operatorParameter']);
Route::get('paysprint-test1/{user_id}/{operator}/{amount}', [CommissionController::class, 'rechargeCommissionPaysprint']);

Route::get('admin', function () {

    // User::where('email', 'oyeahtrip@gmail.com')->update([
    //     'mpin' => Hash::make(1234),
    //         'password' => Hash::make('password')
    // ]);

    // $user = User::with('roles')->where('email', 'rk3141508@gmail.com')->first();

    // $data = DB::table('d_m_t_s')
    // ->join('packages', 'packages.id', '=', 'd_m_t_s.package_id')
    // ->select('d_m_t_s.*')
    // ->get();
    // $data = Hash::make('password');
    $transaction_id = strtoupper(uniqid() . Str::random(8));
    return $transaction_id;
    // return $data;
});




require __DIR__ . '/auth.php';
