<?php

use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
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
use App\Models\Organization;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Request;

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
// Route::get('test', [AepsApiController::class, 'testTransaction']);

Route::get('hello', function () {
    // $parent = User::with(['packages.services' => function ($query) {
    //     $query->where('operator_type', 'like', '%withdrawal%');
    // }, 'parents:id,name', 'roles:name'])->select('id')->findOrFail(55);
    // if (count($parent['parents']) === 0) {
    //     return "null";
    // }
});

// Route::get('test', [AepsApiController::class, 'moneyTransfer']);
Route::get('service', [AgentManagementController::class, 'services']);

Route::get('logic', function () {
    $organization = 'AMV45';
    $test = Organization::with(['packages.users' => function ($q) {
        $q->paginate(1);
    }])->where('code', $organization)->get();
    # http://127.0.0.1:8000/logic?page=1
    echo $test;
});

Route::get('policy', function () {
    $user = User::with('parents')->find(56)->can('view', User::with('parents')->find(55));

    if ($user) {
        return 'True';
    } else {

        return 'False';
    }
});

Route::get('admin', function () {
    $id = 1;
    $org = Organization::with(['roles' => function ($q) use ($id) {
        $q->where('role_id', $id);
    }])->select('id')->where('code', 'DEZ45')->get();
    return $org;
});


require __DIR__ . '/auth.php';
