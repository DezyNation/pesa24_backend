<?php

namespace App\Http\Controllers\Eko\MoneyTransfer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class TransactionController extends Controller
{
    /*------------------------------Initiate Transaction------------------------------*/
    public function initiateTransaction()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'recipient_id' => 10019064,
            'amount' => 200,
            'timestamp' => now(),
            'currency' => 'INR',
            'customer_id' => 8800990087,
            'initiator_id' => 9962981729,
            'client_ref_id' => 'RIM10011909045679290',
            'state' => 1,
            'channel' => 2,
            'latlong' => '26.8863786%2C75.7393589',
            'user_code' => 20810200
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->post('https://staging.eko.in:25004/ekoapi/v2/transactions', $data);

        return $response;
    }

    /*------------------------------Transaction Inquiry------------------------------*/
    public function transactionInquiry($transactionid)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        
        $usercode = auth()->user()->user_code;

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v2/transactions/$transactionid?initiator_id=9962981729&user_code=$usercode");

        return $response;
    }

    /*------------------------------Transaction Inquiry------------------------------*/
    public function refundOtp($tid)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        
        $data = [
            'initiator_id' => 9962981729,
            'user_code' => 20810200,
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->post("https://staging.eko.in:25004/ekoapi/v2/transactions/$tid/refund/otp", $data);

        return $response;
    }
    /*------------------------------Transaction Inquiry------------------------------*/
    public function refund(Request $request, $tid)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => 9962981729,
            'otp' => $request['otp'],
            'state' => 1,
            'user_code' => auth()->user()->user_code
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->post("https://staging.eko.in:25004/ekoapi/v2/transactions/$tid/refund", $data);

        return $response;
    }
}
