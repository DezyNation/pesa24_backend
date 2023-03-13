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

Route::get('paysprint-test', [PaysprintAeps::class, 'onBoard']);

Route::get('admin', function () {
    // $id = 3;
    // $org = 'DEZ45';
    // $org_id = DB::table('organizations')->where('code', $org)->pluck('id');
    // $user = User::with(['roles:name'])->select('id', 'name', 'organization_id')->where('organization_id', $org_id)->get();
    // $test = Role::with(['users' => function($q) use ($org_id) {
    //     $q->select('id', 'name', 'organization_id')->where('organization_id', $org_id);
    // }])->where('id', 1)->get();

    // $test2 = User::with(['roles', 'parents' => function($query) {
    //     $query->where('user_id', 56);
    // }])->where('organization_id', 2)->get();

    // $test3 = ParentUser::has('users')->with(['users' => function($query){
    //     $query->where('parent_id', 57);
    // }, 'users.roles' => function($q){
    //     $q->select('model_id', 'role_id', 'name')->where('role_id', 3);
    // }])->select('id', 'organization_id', 'name')->where('organization_id', $org_id)->get();

    $id = 57;
    // $test3 = Role::with(['users' => function ($q) use ($org_id) {
    //     $q->where('organization_id', $org_id);
    // }, 'users.parents' => function($query){
    //     $query->where('parent_id', 57);
    // }])->where('id', $id)->get();

    // $test3 = User::with(['children' => function($query){
    //     $query->select('user_id','parent_id', 'name')->role('super_distributor');
    // }])->where(['id' => $id, 'organization_id' => $org_id])->get();

    $service = 'money transfer';
    // $user = User::with(['packages.services' => function ($query) use ($service) {
    //     $query->where('operator_type', 'like', "%$service%");
    // }])->select('id', 'name')->findOrFail(55);

    $user = User::with(['services'])->where('id', 55)->get();
    echo $user;
});




require __DIR__ . '/auth.php';
