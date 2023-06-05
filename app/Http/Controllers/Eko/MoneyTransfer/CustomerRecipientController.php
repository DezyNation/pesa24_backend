<?php

namespace App\Http\Controllers\Eko\MoneyTransfer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CustomerRecipientController extends Controller
{

    public function headerArray()
    {
        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => '28fbc74a742123e19bcda26d05453a18',
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    /*-----------------------Customer-----------------------*/

    public function customerInfo(Request $request)
    {
        $phone = $request['customerId'];
        $user_code = auth()->user()->user_code;

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->get("https://api.eko.in:25002/ekoicici/v2/customers/mobile_number:$phone?initiator_id=9758105858&user_code=$user_code");
            
        return $response;
    }

    public function createCustomer(Request $request)
    {

        $data = [
            'name' => $request['customerName'],
            'initiator_id' => 9758105858,
            'user_code' => auth()->user()->user_code ,
            'dob' => $request['customerDob'],
            'residence_address' => json_encode([
                'street' => $request['street'],
                'city' => $request['city'],
                'state' => $request['state'],
                'pincode' => $request['pincode']
            ])
            // 'residence_address' => json_encode(['street' => strval($request['values.street']), 'city' => strval($request['values.city']), 'state' => strval($request['values.state']), 'pincode' => strval($request['values.pincode'])])
        ];

        $customer_id = $request['customerId'];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->put("https://api.eko.in:25002/ekoicici/v2/customers/mobile_number:$customer_id", $data);

        return $response;
        /*---------------------------------Condition for OTP---------------------------------*/
        // if ($response->json($key = 'response_status_id') == 0 && $response->json($key = 'status') == 0) {
        //     Session::put('otp_ref_id', $response->json($key = 'data')['otp_ref_id']);
        //     return response('OTP Send', 200);
        // } elseif ($response->json($key = 'response_status_id') == 1 && $response->json($key = 'status') == 1419) {
        //     return response('Enter valid number', 400);
        // }
    }

    public function resendOtp(Request $request)
    {

        $customer_id = $request['customerId'];
        $data = [
            'initiator_id' => 9758105858,
            'user_code' => auth()->user()->user_code,
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->post("https://api.eko.in:25002/ekoicici/v2/customers/mobile_number:$customer_id/otp", $data);

        return $response;
    }

    public function verifyCustomerIdentity(Request $request)
    {
        $otp = $request['otp'] ?? 160613;

        $data = [
            'initiator_id' => 9758105858,
            'user_code' => auth()->user()->user_code,
            'id_type' => 'mobile_number',
            'id' => $request['customerId'],
            'otp_ref_id' => $request['otp_ref_id'],
            'pipe' => 9
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->put("https://api.eko.in:25002/ekoicici/v2/customers/verification/otp:$otp", $data);

        return $response;
    }


    /*-----------------------Recipient-----------------------*/

    public function recipientList(Request $request)
    {
        $request->validate([
            'customerId' => 'required'
        ]);
        $user_code = auth()->user()->user_code;
        $phone = $request['customerId'];
        $response = Http::withHeaders(
            $this->headerArray()
        )->get("https://api.eko.in:25002/ekoicici/v2/customers/mobile_number:$phone/recipients?initiator_id=9758105858&user_code=$user_code");

        return $response;
    }

    public function recipientDetails(Request $request)
    {
        $phone = $request['phone'];
        $recipient_id = $request['recipientId'];
        $user_code = auth()->user()->user_code;
        
        $response = Http::withHeaders(
            $this->headerArray()
        )->get("https://api.eko.in:25002/ekoicici/v2/customers/mobile_number:$phone/recipients/recipient_id:$recipient_id?initiator_id=9999912796&user_code=$user_code");

        return $response;
    }

    public function addRecipient(Request $request)
    {

        $data = [
            'initiator_id' => 9758105858,
            'recipient_name' => $request['beneficiaryName'],
            'recipient_mobile' => $request['beneficiaryPhone'],
            'recipient_type' => 3,
            'user_code' => auth()->user()->user_code
        ];

        $customer_id = $request['customerId'];
        $acc_ifsc = $request['accountNumber'] . '_' . $request['ifsc'];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->put("https://api.eko.in:25002/ekoicici/v2/customers/mobile_number:$customer_id/recipients/acc_ifsc:$acc_ifsc", $data);

        return $response;
    }
}
