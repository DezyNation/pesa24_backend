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
}
