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

        $line = auth()->user()->line;
        $city = auth()->user()->city;
        $state = auth()->user()->state;
        $pincode = auth()->user()->pincode;

        $residence_address['line'] = strval($line);
        $residence_address['city'] = strval($city);
        $residence_address['state'] = strval($state);
        $residence_address['pincode'] = strval($pincode);
        
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
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/onboard', $data);
        
        if(collect($response->json($key = 'data'))->has('user_code'))
        {
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
