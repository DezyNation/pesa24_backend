<?php

namespace App\Http\Controllers\Eko\Agent;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AgentManagementController extends Controller
{
    public function userOnboard()
    {
        $key = env('AUTHENTICATOR_KEY');
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'pan_number' => 'ABCQE1234F',
            'mobile' => 9971412064,
            'first_name' => 'Rishi',
            'middle_name' => '',
            'last_name' => 'Kumar',
            'email' => 'rk3141508@gmail.com',
            // 'residence_address' => `{"line": $request->input('line'),"city":$request->input('city'),"state":$request->input('state'),"pincode":$request->input('pin')}`,
            'residence_address' => "{'line': $a,'city':$b, 'state':$c,'pincode':$d}",
            'dob' => date("d-m-Y"),
            // 'dob' => date("d-m-Y"),
            'shop_name' => 'Vishal'
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/onboard', $data);

        return $response;
    }

    public function activateService(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'service_code' => 2,
            'initiator_id' => '9962981729',
            'user_code' => auth()->user()->user_code,
            'devicenumber' => $request['devicenumber'],
            'model_name' => $request['model_name'],
            'address_as_per_proof' => "{'line': '$request->line','city':'$request->city','state':'$request->state','pincode':'$request->pincode'}",
            'office_address' => "{'line': '$request->office_line','city':'$request->office_city','state':'$request->office_state','pincode':'$request->office_pincode'}"
        ];

        $aadhar_front = $request->file('aadhar_front');
        $aadhar_back = $request->file('aadhar_back');
        $pan_card = $request->file('pan_card');

        $response = Http::attach('aadhar_front', file_get_contents($aadhar_front), 'aadhar_front.jpg')->
                          attach('aadhar_back', file_get_contents($aadhar_back), 'aadhar_back.jpg')->
                          attach('pan_card', file_get_contents($pan_card), 'pan_card.jpg')->
                          asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);

        return $response;
    }

    public function userServiceInquiry(Request $request)
    {
        $usercode = auth()->user()->user_code ;
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
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v1/user/services/user_code:$usercode?initiator_id:$initiator_id", ['initiator_id' => 9962981729]);

        return $response;
    }
}
