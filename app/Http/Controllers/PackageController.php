<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class PackageController extends Controller
{
    public function attachService(Request $request)
    {
        $user = User::findOrfail(auth()->user()->id);
        $service = Service::findOrFail($request['service_id']);

        $status = $this->activateService($service->eko_id);
        if ($status == 1) {
            $eko_activation = 1;
            $message = "Enrollment sucessfull.";
        } elseif ($status == 2) {
            $eko_activation = 0;
            $message = "Enrollment pending.";
        } else {
            $eko_activation = 0;
            return response()->json(['message' => 'Tou could not enroll to the service at the moment']);
        }

        DB::table('transactions')->insert([
            'user_id' => auth()->user()->id,
            'transaction_for' => "Service: " . "$service->operator_name " . "$service->type",
            'credit_amount' => 0,
            'debit_amount' => $service->price,
            'opening_balance' => $user->wallet,
            'balance_left' =>  $user->wallet - $service->price,
            'retailer_commission' => 0,
            'distributor_commission' => 0,
            'super_distributor_commission' => 0,
            'admin_commission' => 0,
            'transaction_id' => uniqid(),
            'is_flat' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $user->update([
            'wallet' => $user->wallet - $service->price
        ]);
        $user->services()->attach($service, ['eko_active' => $eko_activation, 'paysprint_active' => 0, 'pesa24_active' => 1]);
        return response()->json(['message' => $message]);
    }

    public function activateService($service)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);


        $data = [
            'service_code' => $service,
            'initiator_id' => '9962981729',
            'user_code' => auth()->user()->user_code,
        ];
        $response = Http::asForm()->withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
        Log::channel('response')->info($response, ['user_name' => auth()->user()->name, 'user_id' => auth()->user()->phone_number]);

        return $response['data']['service_status'];
    }

    public function parentPackage($id)
    {
        $code = Session::get('organization_code');
         $org = Organization::with(['roles' => function ($q) use ($id) {
            $q->select('role_id')->where('role_id', $id);
        }])->select('id')->where('code', $code)->get();
        return $org;
    }



    /*
                $data = [
                'service_code' => $request['serviceCode'],
                'initiator_id' => '9962981729',
                'user_code' => auth()->user()->user_code,
                'modelname' => $request['modelname'],
                'devicenumber' => $request['devicenumber'],
                'office_address' => json_encode(['line' => strval($request['line']), 'city' => strval($request['city']), 'state' => strval($request['state']), 'pincode' => strval($request['pincode'])]),
                'address_as_per_proof' => json_encode(['line' => strval($request['lineProof']), 'city' => strval($request['cityProof']), 'state' => strval($request['stateProof']), 'pincode' => strval($request['pincodeProof'])])
            ];
            $pan = $request->file('pancard');
            $aadhar_front = $request->file('aadhar_front');
            $aadhar_back = $request->file('aadhar_back');

            $response = Http::asForm()->attach('pancard', file_get_contents($pan), 'pan.pdf')->attach('aadhar_front', file_get_contents($aadhar_front), 'aadhar_front.pdf')->attach('aadhar_back', file_get_contents($aadhar_back), 'aadhar_back.pdf')->withHeaders([
                'developer_key' => 'becbbce45f79c6f5109f848acd540567',
                'secret-key-timestamp' => $secret_key_timestamp,
                'secret-key' => $secret_key,
            ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
    */
}
