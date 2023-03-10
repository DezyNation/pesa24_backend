<?php

namespace App\Http\Controllers\Eko\Agent;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\v1\UserResource;

class AgentManagementController extends Controller
{
    public function userOnboard()
    {

        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $residence_address['line'] = strval(auth()->user()->line);
        $residence_address['city'] = strval(auth()->user()->city);
        $residence_address['state'] = strval(auth()->user()->state);
        $residence_address['pincode'] = strval(auth()->user()->pincode);

        $data = [
            'initiator_id' => 9962981729,
            'pan_number' => auth()->user()->pan_number,
            'mobile' => auth()->user()->phone_number,
            'first_name' => auth()->user()->first_name,
            'middle_name' => auth()->user()->middle_name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'residence_address' => json_encode($residence_address),
            'dob' => auth()->user()->dob,
            'shop_name' => auth()->user()->company_name
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/onboard', $data);

        if (collect($response->json($key = 'data'))->has('user_code')) {
            DB::table('users')->where('id', auth()->user()->id)->update([
                'user_code' => $response->json($key = 'data')['user_code']
            ]);
            return response(new UserResource(User::findOrFail(Auth::id())), 200);
        }
        return response($response, 400);
    }

    public function activateService(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        if ($request['serviceCode'] == 43 || $request['serviceCode'] == 52) {
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
        } else {
            $data = [
                'service_code' => $request['service_code'],
                'initiator_id' => '9962981729',
                'user_code' => auth()->user()->user_code,
            ];
            $response = Http::asForm()->withHeaders([
                'developer_key' => 'becbbce45f79c6f5109f848acd540567',
                'secret-key-timestamp' => $secret_key_timestamp,
                'secret-key' => $secret_key,
            ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
        }

        return $response;
    }

    public function userServiceInquiry(Request $request)
    {
        $usercode = auth()->user()->user_code;
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
        ])->get("https://staging.eko.in:25004/ekoapi/v1/user/services/user_code:$usercode?initiator_id:$initiator_id", ['initiator_id' => 9962981729]);

        return $response;
    }
}
