<?php

namespace App\Http\Controllers\Eko\Agent;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\v1\UserResource;

class AgentManagementController extends Controller
{

    public function headerArray()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => env('DEVELOPER_KEY'),
            // 'secret-key' => $secret_key,
            // 'secret-key-timestamp' => $secret_key_timestamp
        ];
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

    public function services()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $initiator_id = 9962981729;

        $response = Http::withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp,
        ])->get("https://staging.eko.in:25004/ekoapi/v1/user/services?initiator_id=$initiator_id");

        return $response;
    }

    public function userServiceInquiry()
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
        ])->get("https://staging.eko.in:25004/ekoapi/v1/user/services/user_code:$usercode?initiator_id:$initiator_id", ['initiator_id' => $initiator_id]);

        return $response;
    }
}
