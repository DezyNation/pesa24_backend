<?php

namespace App\Http\Controllers\Eko\BBPS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class BBPSController extends CommissionController
{

    public function headerArray()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => env('DEVELOPER_KEY'),
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    public function headerArray2()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
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

    public function operators(Request $request, int $category_id = null)
    {
        if ($request->has('operator_id')) {
            $url = "http://staging.eko.in:8080/ekoapi/v2/billpayments/operators/{$request['operator_id']}";
        } else {
            $url = "http://staging.eko.in:8080/ekoapi/v2/billpayments/operators/?category=$category_id";
        }

        $response = Http::acceptJson()->withHeaders(
            $this->headerArray()
        )->get($url);

        return $response;
    }

    public function operatorCategoryList()
    {
        $response = Http::acceptJson()->withHeaders(
            $this->headerArray()
        )->get("http://staging.eko.in:8080/ekoapi/v2/billpayments/operators_category");

        return $response;
    }

    public function operatorField($operator_id)
    {

        $response = Http::acceptJson()->withHeaders(
            $this->headerArray()
        )->get("http://staging.eko.in:8080/ekoapi/v2/billpayments/operators/$operator_id");

        return $response;
    }

    public function fetchBill(Request $request)
    {

        $data = [
            'user_code' => auth()->user()->user_code ?? 20810200,
            'client_ref_id' => uniqid(),
            'source_ip' => $request->ip(),
            'confirmation_mobile_no' => $request['confirmation_mobile_no'],
            'utility_acc_no' => $request['utility_acc_no'],
            'sender_name' => $request['sender_name'] ?? '',
            'operator_id' => 22,
            'latlong' => $request['latlong']
        ];
        $data1 = $request->all();
        $data2 = array_merge($data1, $data);

        $response = Http::withHeaders([
            'Connection' => 'Keep-Alive',
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'okhttp/3.9.0',
            $this->headerArray()['developer_key']
        ])
            ->post("http://staging.eko.in:8080/ekoapi/v2/billpayments/fetchbill?initiator_id=9962981729", $data2);

        return $response;
    }

    public function payBill(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $hash = $this->headerArray();
        $concatenated = $hash['secret-key-timestamp'] . $hash['secret-key'] . $request['utility_acc_no'] ?? 151627591;
        $hmacCon = hash_hmac('sha256', $concatenated, $encodedKey, true);
        $request_hash = base64_encode($hmacCon);


        $data = [
            'user_code' => auth()->user()->user_code ?? 20810200,
            'client_ref_id' => uniqid(),
            'utility_acc_no' => $request['utility_acc_no'] ?? 151627591,
            'confirmation_mobile_no' => $request['confirmation_mobile_no'] ?? 9999999999,
            'sender_name' => $request['sender_name'] ?? 'Kaushik',
            'operator_id' => $request['operator_id'] ?? 22,
            'source_ip' => $request->ip(),
            'latlong' => $request['latlong'],
            'amount' => $request['amount'] ?? 50,
            'billfetchresponse' => $request['bill']??null
        ];

        $response = Http::withHeaders([
            'Connection' => 'Keep-Alive',
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'okhttp/3.9.0',
            'Content-Type' => 'application/json',
            'request_hash' => $request_hash,
            'developer_key' => $hash['developer_key']
        ])->post("http://staging.eko.in:8080/ekoapi/v2/billpayments/fetchbill?initiator_id=9962981729", $data);

        if ($response['status'] == 0) {
            $metadata = [
                'status' => true,
                'sender_id' => $response['data']['sender_id'],
                'amount' => $response['data']['amount'],
                'operator_name' => $response['data']['operator_name'],
                'refernce_id' => $data['client_ref_id']
            ];
            $opening_balance = auth()->user()->wallet;
            $closing_balance = $data['amount'];
            $transaction_id = "BBPSE" . uniqid();
            $this->transaction($data['amount'], "BBPS recharge for {$response['data']['operator_name']}", 'bbps', auth()->user()->id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata));
            $this->bbpsEkoCommission(auth()->user()->id, $data['operator_id'], $data['amount']);
            $this->apiRecords($data['client_ref_id'], 'eko', $response);
        } else {
            $metadata = [
                'status' => false,
                'amount' => $data['amount'],
                'message' => $response['message']
            ];

            $this->apiRecords($data['client_ref_id'], 'eko', $response);
        }
        return response(['metadata' => $metadata]);
    }
}
