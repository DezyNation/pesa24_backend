<?php

use App\Models\User;
use App\Models\Package;
use App\Models\ParentUser;
use Illuminate\Support\Str;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\Paysprint\LPGController;
use App\Http\Controllers\Paysprint\PANController;
use App\Http\Controllers\Razorpay\PayoutController;
use App\Http\Controllers\Eko\AePS\AepsApiController;
use App\Http\Controllers\Razorpay\ContactController;
use App\Http\Controllers\Paysprint\OnboardController;
use App\Http\Controllers\Paysprint\BBPS\LICController;
use App\Http\Controllers\Paysprint\BBPS\BillController;
use App\Http\Controllers\Paysprint\BBPS\FastTagController;
use App\Http\Controllers\Pesa24\KycVerificationController;
use App\Http\Controllers\Paysprint\BBPS\RechargeController;
use App\Http\Controllers\Paysprint\CMS\AirtelCMSController;
use App\Http\Controllers\Eko\Agent\AgentManagementController;
use App\Http\Controllers\Eko\MoneyTransfer\TransactionController;
use App\Http\Controllers\Eko\MoneyTransfer\CustomerRecipientController;
use App\Http\Controllers\Paysprint\PayoutController as PaysprintPayout;
use App\Http\Controllers\Paysprint\AePS\AepsApiController as PaysprintAeps;
use App\Http\Controllers\Eko\MoneyTransfer\PayoutController as MoneyTransferPayoutController;

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

Route::get('/file', function () {



    // $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
    // $encodedKey = base64_encode($key);
    // $secret_key_timestamp = round(microtime(true) * 1000);
    // $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
    // $secret_key = base64_encode($signature);

    // $data = [
    //     'service_code' => $request['serviceCode'] ?? 43,
    //     'initiator_id' => '9962981729',
    //     'user_code' => auth()->user()->user_code ?? 20810200,
    //     'modelname' => $request['modelname'] ?? "ANYSQE",
    //     'devicenumber' => $request['devicenumber'] ?? 123433,
    //     'office_address' => json_encode(['line' => strval($request['line'] ?? "ABD"), 'city' => strval($request['city']?? "ABD"), 'state' => strval($request['state']?? "Delhi NCR"), 'pincode' => strval($request['pincode']?? "110033")]),
    //     'address_as_per_proof' => json_encode(['line' => strval($request['line'] ?? "ABD"), 'city' => strval($request['city']?? "ABD"), 'state' => strval($request['state']?? "Delhi NCR"), 'pincode' => strval($request['pincode']?? "110033")])
    // ];
    // $pan = storage_path('app/pan/fOawYlLn9uEmUYLfQlvQXl28GdT3ypqWTxYuLFX2.png');
    // $aadhar_front = storage_path('app/pan/fOawYlLn9uEmUYLfQlvQXl28GdT3ypqWTxYuLFX2.png');
    // $aadhar_back = storage_path('app/pan/fOawYlLn9uEmUYLfQlvQXl28GdT3ypqWTxYuLFX2.png');

    // $response = Http::asForm()->attach('pancard', file_get_contents($pan), 'pan.pdf')->attach('aadhar_front', file_get_contents($aadhar_front), 'aadhar_front.pdf')->attach('aadhar_back', file_get_contents($aadhar_back), 'aadhar_back.pdf')->withHeaders([
    //     'developer_key' => 'becbbce45f79c6f5109f848acd540567',
    //     'secret-key-timestamp' => $secret_key_timestamp,
    //     'secret-key' => $secret_key,
    // ])->put('http://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
    // return $response;
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

Route::get('inquiry', [AepsApiController::class, 'moneyTransfer']);
Route::get('dmt', [TransactionController::class, 'initiateTransaction']);
Route::get('pan', [AgentManagementController::class, 'generalService']);
// Route::get('cms', [AirtelCMSController::class, 'transactionStatus']);
// Route::prefix('commissions')->group(function () {
//     // Route::get('aeps-mini/{user_id}', [CommissionController::class, 'aepsMiniComission']);
//     // Route::get('dmt/{user_id}/{amount}', [CommissionController::class, 'dmtCommission']);
//     // Route::get('recharge/{user_id}/{operator}/{amount}', [CommissionController::class, 'rechargeCommissionPaysprint']);
//     // Route::get('bbps/{user_id}/{operator}/{amount}', [CommissionController::class, 'bbpsPaysprintCommission']);
// });

Route::post('file-test', function(Request $request){
    // return $request->all();
    if ($request->hasFile('pancard')) {
        $request->file('pancard')->store('public');
        return "hehe";
    }

    return "hoho";
});



require __DIR__ . '/auth.php';
