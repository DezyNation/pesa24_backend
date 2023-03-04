<?php

namespace App\Http\Controllers\Eko\MoneyTransfer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CustomerRecipientController extends Controller
{
    /*-----------------------Customer-----------------------*/
    
    public function customerInfo(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $phone = $request['customerId'];
        $user_code = auth()->user()->user_code;

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:$phone?initiator_id=9962981729&user_code=$user_code");

        $data = $this->recipientList($phone);

        return ['response' => $response->json(), 'recepient' => $data->json()];
    }

    public function createCustomer(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $customer_id = $request['values.customerId'];

        $data = [
            'name' => $request['values.customerName'],
            'initiator_id' => 9962981729,
            'user_code' => auth()->user()->user_code,
            'dob' => $request['values.dob'],
            'pipe' => 9,
            'residence_address' => json_encode(['street' => strval($request['values.street']), 'city' => strval($request['values.city']), 'state' => strval($request['values.state']), 'pincode' => strval($request['values.pincode'])])
        ];
        
        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
            ])->put("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:$customer_id", $data);
            
            if($response->json($key = 'response_status_id') == 0 && $response->json($key = 'status') == 0){
                Session::put('otp_ref_id', $response->json($key = 'data')['otp_ref_id']);
                return response('OTP Send', 200);
            }elseif ($response->json($key = 'response_status_id') == 1 && $response->json($key = 'status') == 1419) {
                return response('Enter valid number', 400);
            }

            return response('Customer already verified', 409);
    }

    public function resendOtp(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $customer_id = $request['customerId'];
        $data = [
            'initiator_id' => 9962981729,
            'user_code' => auth()->user()->user_code,
            'pipe' => 9
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
            ])->post("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:$customer_id/otp", $data);
            
            if($response->json($key = 'response_status_id') == 0 && $response->json($key = 'status') == 0){
                Session::put('otp_ref_id', $response->json($key = 'data')['otp_ref_id']);
                return response('OTP Send', 200);

            }elseif ($response->json($key = 'response_status_id') == -1 && $response->json($key = 'status') == 0) {
                return response('Customer already registered', 204);
            }

            return response( 'Customer does not exist', 409);

    }
        
    public function verifyCustomerIdentity(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $otp = $request['otp'];

        $data = [
            'initiator_id' => 9962981729,
            'user_code' => auth()->user()->user_code,
            'customer_id_type'=> 'mobile_number',
            'customer_id' => $request['customerId'],
            'pipe'=> 9,
            'otp_ref_id' => Session::get('otp_ref_id')
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
            ])->put("https://staging.eko.in:25004/ekoapi/v2/customers/verification/otp:$otp", $data);
            
        return $response;
    }
        
    /*-----------------------Recipient-----------------------*/

    public function recipientList(int $phone)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $user_code = auth()->user()->user_code;
        $phone = $phone;

        $response = Http::withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:$phone/recipients?initiator_id=9962981729&user_code=$user_code");

        return $response; 
    }

    public function recipientDetails(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $user_code = auth()->user()->user_code;
        $phone = $request['phoneNumber'];
        $recipient_id = $request['recipientId'];

        $response = Http::withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:$phone/recipients/recipient_id:$recipient_id?initiator_id=9962981729&user_code=$user_code");

        return $response; 
    }

    public function addRecipient(Request $request)
    {         
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $account_no = $request['accountNo'];
        $ifsc = $request['ifsc'];

        $data = [
            'initiator_id' => 9962981729,
            'bank_id' => $request['values.bankCode'],
            'recipient_name' => $request['values.beneficiaryName'],
            'recipient_mobile' => $request['values.phone'],
            'recipient_type' => 3,
            'user_code' => auth()->user()->user_code
        ];

        $customer_id = $request['customerId'];
        $acc_ifsc = $request['values.accountNumber'].'_'.$request['values.ifsc'];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->put("https://staging.eko.in:25004/ekoapi/v2/customers/mobile_number:$customer_id/recipients/acc_ifsc:$acc_ifsc");

        return $response; 
    }
}
