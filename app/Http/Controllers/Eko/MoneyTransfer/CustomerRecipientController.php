<?php

namespace App\Http\Controllers\Eko\MoneyTransfer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class CustomerRecipientController extends Controller
{
    /*-----------------------Customer-----------------------*/
    
    public function createCustomer(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        
        $data = [
            'name' => $request['name'],
            'initiator_id' => 9962981729,
            'user_code' => 20810200,
            'dob' => date('Y-m-d'),
            'title'=> $request['title'],
            'residence_address' => ['state' => $request['state']]
        ];
        
        $response = Http::accept('text/plain')->asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
            ])->put("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:9971412064", $data);
            
        return $response;
    }

    public function resendOtp(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => 9962981729,
            'user_code' => 20810200,
            'pipe' => 9
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
            ])->post("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:$request->phone_number/otp", $data);
            
        return $response;
    }
        
    public function verifyCustomerIdentity(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => 9962981729,
            'user_code' => 20810200,
            'customer_id_type'=> 'mobile_number',
            'customer_id' => $request['customer_id'],
            'pipe'=> 9,
            // 'otp' => 16613,
            'otp_ref_id' => $request['otp_ref_id']
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
            ])->put('https://staging.eko.in:25004/ekoapi/v2/customers/verification/otp:069775', $data);
            
        return $response;
    }
        
    /*-----------------------Recipient-----------------------*/

    public function recipientList()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $response = Http::withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get('https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:8800990087/recipients?initiator_id=9962981729&user_code=20810200');

        return $response; 
    }

    public function recipientDetails()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $response = Http::withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get('https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:8800990087/recipients/recipient_id:10019064?initiator_id=9962981729&user_code=20810200');

        return $response; 
    }

    public function addRecipient()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => 9962981729,
            'bank_id' => 56,
            'recipient_name' => 'John Doe',
            'recipient_mobile' => 7661109875,
            'recipient_type' => 3,
            'user_code' => 20810200
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:7661109875/recipients/acc_ifsc:1711890657_KKBK0000731');

        return $response; 
    }
}
