<?php

use App\Models\User;
use App\Models\Package;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Paysprint\LICController;
use App\Http\Controllers\Paysprint\LPGController;
use App\Http\Controllers\Razorpay\PayoutController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Razorpay\ContactController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Paysprint\AePS\AepsApiController as PaysprintAeps;
use App\Models\ParentUser;

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

// Route::get('paysprint-test', [PaysprintAeps::class, 'onBoard']);

// Route::get('admin', function () {
//     $result = DB::table('users')
//         ->join('package_user','users.id','=','package_user.user_id')
//         ->join('packages','package_user.package_id','=','packages.id')
//         ->join('package_service','packages.id','=','package_service.package_id')
//         ->join('services','package_service.service_id','=','services.id')
//         ->select('package_service.*')->where('users.id','=',55)->where('services.id','=',22)->get();
//     echo $result;
// });




require __DIR__ . '/auth.php';
