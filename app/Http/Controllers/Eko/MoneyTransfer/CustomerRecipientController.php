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
        $key = "d2fe1d99-6298-4af2-8cc5-d97dcf46df30";
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

    /*-----------------------Customer-----------------------*/

    public function customerInfo(Request $request)
    {
        $phone = $request['customerId'] ?? 9654110669;
        $user_code = auth()->user()->user_code ?? 20810200;

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->get("http://staging.eko.in:8080/ekoapi/v1/customers/mobile_number:$phone?initiator_id=9910028267&user_code=$user_code");
            
        // $data = $this->recipientList($phone);
        // return json_decode($data);
        return $response;
    }

    public function createCustomer(Request $request)
    {

        $data = [
            'name' => $request['customerName'] ?? "Rupesh",
            'initiator_id' => 9999912796,
            'user_code' => auth()->user()->user_code ?? 20810200,
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
        )->put("http://staging.eko.in:8080/ekoapi/v2/customers/mobile_number:$customer_id", $data);

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

        $customer_id = $request['customerId'] ?? 9971412064;
        $data = [
            'initiator_id' => 9999912796,
            'user_code' => auth()->user()->user_code ?? 20810200,
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->post("http://staging.eko.in:8080/ekoapi/v2/customers/mobile_number:$customer_id/otp", $data);

        return $response;
    }

    public function verifyCustomerIdentity(Request $request)
    {
        $otp = $request['otp'] ?? 160613;

        $data = [
            'initiator_id' => 9962981729,
            'user_code' => auth()->user()->user_code ?? 20810200,
            'id_type' => 'mobile_number',
            'id' => $request['customerId'] ?? 9971412064,
            'otp_ref_id' => $request['otp_ref_id'] ?? 'd3e00033-ebd1-5492-a631-53f0dbf00d69',
            'pipe' => 9
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->put("http://staging.eko.in:8080/ekoapi/v2/customers/verification/otp:$otp", $data);

        return $response;
    }


    /*-----------------------Recipient-----------------------*/

    public function recipientList(Request $request)
    {
        $user_code = auth()->user()->user_code ?? 20810200;
        $phone = $request['customerId'] ?? 9899796311;
        $response = Http::withHeaders(
            $this->headerArray()
        )->get("http://staging.eko.in:8080/ekoapi/v2/customers/mobile_number:$phone/recipients?initiator_id=9999912796&user_code=$user_code");

        return $response;
    }

    public function recipientDetails(Request $request)
    {
        $phone = 9999912345;
        $recipient_id = 10011965;
        $user_code = 20810200;
        
        $response = Http::withHeaders(
            $this->headerArray()
        )->get("http://staging.eko.in:8080/ekoapi/v2/customers/mobile_number:$phone/recipients/recipient_id:$recipient_id?initiator_id=9999912796&user_code=$user_code");

        return $response;
    }

    public function addRecipient(Request $request)
    {

        $data = [
            'initiator_id' => 9999912796,
            'recipient_name' => $request['values.beneficiaryName'] ?? 'John Doe',
            'recipient_mobile' => $request['values.phone'] ?? 9899796311,
            'recipient_type' => 3,
            'user_code' => auth()->user()->user_code ?? 20810200
        ];

        $customer_id = $request['customerId'] ?? 9999912345;
        $acc_ifsc = $request['values.accountNumber'] . '_' . $request['values.ifsc'] ?? '1711650492_KKBK0000261';
        $acc_ifsc = '1711650592_KKBK0000261';

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->put("http://staging.eko.in:8080/ekoapi/v2/customers/mobile_number:$customer_id/recipients/acc_ifsc:$acc_ifsc", $data);

        return json_decode($response);
    }
}
