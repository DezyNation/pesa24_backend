<?php

namespace App\Http\Controllers\Eko\MoneyTransfer;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class TransactionController extends CommissionController
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

    public function splitAmount($recipient_id, $amount, $customer_id)
    {
        $data = [
            'recipient_id' => $recipient_id,
            'amount' => $amount,
            'customer_id' => $customer_id,
            'initiator_id' => 9758105858,
            'channel' => 2
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->get("https://api.eko.in:25002/ekoicici/v2/transactions/split", $data);
        return $response['data']['split_tid'];
    }
    /*------------------------------Initiate Transaction------------------------------*/
    public function initiateTransaction(Request $request)
    {
        $amount = $request['amount'];
        $recipient_id = $request['beneficiaryId'];
        $customer_id = $request['customerId'];

        if ($amount > 5000) {
            $split_tid = $this->splitAmount($recipient_id, $amount, $customer_id);
            $data = [
                'recipient_id' => $recipient_id,
                'amount' => $amount,
                'timestamp' => time(),
                'currency' => 'INR',
                'customer_id' => $customer_id,
                'initiator_id' => 9758105858,
                'client_ref_id' => substr(strtoupper(uniqid() . Str::random(10)), 0, 10),
                'state' => 1,
                'channel' => 2,
                'latlong' => $request['latlong'],
                'user_code' => auth()->user()->user_code,
                'split_tid' => $split_tid
            ];
        } else {
            $data = [
                'recipient_id' => $recipient_id,
                'amount' => $amount,
                'timestamp' => time(),
                'currency' => 'INR',
                'customer_id' => $customer_id,
                'initiator_id' => 9758105858,
                'client_ref_id' => substr(strtoupper(uniqid() . Str::random(10)), 0, 10),
                'state' => 1,
                'channel' => 2,
                'latlong' => $request['latlong'],
                'user_code' => auth()->user()->user_code,
            ];
        }

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->post('https://api.eko.in:25002/ekoicici/v2/transactions', $data);
        $this->apiRecords($data['client_ref_id'], 'api', $response);
        $opening_balance = auth()->user()->wallet;
        $closing_balance = $opening_balance - $data['amount'];
        if (!array_key_exists('status', $response->json())) {
            $metadata = [
                'status' => false,
                'message' => $response['message'],
                'user_id' => auth()->user()->id,
                'user' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'amount' => $amount,
            ];
            $this->transaction($data['amount'], "DMT to {$response['data']['recipient_name']}", 'dmt', auth()->user()->id, $opening_balance, $data['client_ref_id'], $closing_balance, json_encode($metadata));
            return response(['metadata' => $metadata]);
        }

        if ($response['status'] == 0) {
            $metadata = [
                'status' => true,
                'amount' => $response['data']['amount'],
                'recipient_id' => $recipient_id,
                'customer_id' => $customer_id,
                'reference_id' => $response['data']['client_ref_id'],
                'message' => $response['message'],
                'user_id' => auth()->user()->id,
                'user' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'amount' => $amount,

            ];

            $this->transaction($data['amount'], "DMT to {$response['data']['recipient_name']}", 'dmt', auth()->user()->id, $opening_balance, $data['client_ref_id'], $closing_balance, json_encode($metadata));
            $this->dmtCommission(auth()->user()->id, $data['amount']);

        } else {
            $metadata = [
                'status' => false,
                'amount' => $request['amount'],
                'recipient_id' => $recipient_id,
                'customer_id' => $customer_id,
                'reference_id' => $data['client_ref_id'],
                'message' => $response['message'],
                'user_id' => auth()->user()->id,
                'user' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'amount' => $amount,
            ];
            $this->transaction(0, "DMT to User", 'dmt', auth()->user()->id, $opening_balance, $data['client_ref_id'], $opening_balance, json_encode($metadata));
        }

        return response(['metadata' => $metadata]);
    }

    /*------------------------------Transaction Inquiry------------------------------*/
    public function transactionInquiry($transactionid)
    {
        $usercode = auth()->user()->user_code;

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->get("https://staging.eko.in:25004/ekoapi/v2/transactions/$transactionid?initiator_id=9962981729&user_code=$usercode");

        return $response;
    }

    /*------------------------------Transaction Inquiry------------------------------*/
    public function refundOtp($tid)
    {

        $data = [
            'initiator_id' => 9962981729,
            'user_code' => 20810200,
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->post("https://staging.eko.in:25004/ekoapi/v2/transactions/$tid/refund/otp", $data);

        return $response;
    }
    /*------------------------------Transaction Inquiry------------------------------*/
    public function refund(Request $request, $tid)
    {

        $data = [
            'initiator_id' => 9962981729,
            'otp' => $request['otp'],
            'state' => 1,
            'user_code' => auth()->user()->user_code
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->post("https://staging.eko.in:25004/ekoapi/v2/transactions/$tid/refund", $data);

        return $response;
    }
}
