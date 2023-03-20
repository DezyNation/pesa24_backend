<?php

use App\Models\User;
use App\Models\Package;
use App\Models\ParentUser;
use Illuminate\Support\Str;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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

Route::get('admin', function () {
    // $result = DB::table('users')
    // ->join('service_user','users.id','=','service_user.user_id')
    // ->join('services','service_user.service_id','=','services.id')
    // ->join('package_service','packages.id','=','package_service.package_id')
    // ->join('services','package_service.service_id','=','services.id')
    // ->select('services.*')->where('users.id','=',55)
    // ->where('services.id','=',22)
    // ->get();
    // echo $result;

    $service_id = 22;
    $amount = 10000;
    $result = DB::table('users')
    ->join('package_user', 'users.id', '=', 'package_user.user_id')
    ->join('packages', 'package_user.package_id', '=', 'packages.id')
    ->join('package_service', 'packages.id', '=', 'package_service.package_id')
    ->join('service_user', 'users.id',  '=', 'service_user.user_id')
    ->join('services', 'package_service.service_id', '=', 'services.id')
    ->select('package_service.*', 'services.service_name')
    ->where(['service_user.user_id'=> 55, 'service_user.service_id' => $service_id, 'package_service.service_id' => $service_id, 'package_user.user_id' => 55])
    ->where('from', '<', $amount)
    ->where('to', '>=', $amount)
    ->get();
    $array = $result->toArray();
    $user = User::findOrFail(55);
    // $test = json_decode(json_encode(response(["test" => true], 200), true), true);
    // $a = json_encode($test, true);
    // $b = json_decode($a, true);
    return $array;
});

Route::get('file', function(){
    $file = Storage::disk('local')->get('pan\sa3Pf61R2AOEdCqT60ohrf3TPx1Tm0qvPD4wYVQ6.jpg');
    return $file;
});

Route::get('test-aeps', function(){
    $usercode = 99099211;
    $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
    $encodedKey = base64_encode($key);
    $secret_key_timestamp = round(microtime(true) * 1000);
    $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
    $secret_key = base64_encode($signature);

    $initiator_id = 9962981729;

    $response = Http::accept('*/*')->withHeaders([
        'Accept-Encoding' => 'gzip, deflate',
        'Connection' => 'keep-alive',
        'Host' => 'staging.eko.in:25004',
        'Cache-Control' => 'no-cache',
        'developer_key' => 'becbbce45f79c6f5109f848acd540567',
        'secret-key-timestamp' => $secret_key_timestamp,
        'secret-key' => $secret_key,
    ])->get("https://staging.eko.in:25004/ekoapi/v1/user/services/user_code:$usercode?initiator_id:$initiator_id", ['initiator_id' => $initiator_id]);

    return $response;
});




require __DIR__ . '/auth.php';
