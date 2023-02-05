<?php

namespace App\Http\Controllers\Eko\DMT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class AgentCustomerController extends Controller
{
    /*--------------------------------Agent--------------------------------*/
    public function dmtRegistration()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => 23112120,
            'user_code' =>'Some code'
        ];

        $response = Http::asForm()->withHeader([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ]);
        
        return $response;
    }
    
    public function fetchAgent()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        
        $data = [
            'initiator_id' => 6333331126,
            'user_code' => 20810282
        ];
        
        $response = Http::asForm()->withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key'=> $secret_key,
            ])->post('https://staging.eko.in:25004/ekoapi/v2/kyc/transaction/fetchAgent', $data);
            
            return $response;
        }
        
        public function agentValidation()
        {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        
        $data = [
            'initiator_id' => 254522,
            'user_code' => 15210210,
            'customer_id' => 9971412064,
            'otp' => 45415210,
            'otp_ref_id' => 454545,
        ];
        
        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->post('https://staging.eko.in:25004/ekoapi/v2/kyc/transaction/kycOtpValidation', $data);

        return $response;
    }

    public function agentEkyc()
    {
        
    }
}
