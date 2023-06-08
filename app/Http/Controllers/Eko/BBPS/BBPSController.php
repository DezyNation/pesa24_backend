<?php

namespace App\Http\Controllers\Eko\BBPS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;
use Illuminate\Support\Facades\Log;

class BBPSController extends CommissionController
{

    public function headerArray()
    {
        $key = "d2fe1d99-6298-4af2-8cc5-d97dcf46df30";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => "becbbce45f79c6f5109f848acd540567",
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
            $url = "https://api.eko.in:25002/ekoicici/v2/billpayments/operators/{$request['operator_id']}";
        } else {
            $url = "https://api.eko.in:25002/ekoicici/v2/billpayments/operators/?category=$category_id";
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
        )->get("https://api.eko.in:25002/ekoicici/v2/billpayments/operators_category");

        return $response;
    }

    public function operatorField($operator_id)
    {

        $response = Http::acceptJson()->withHeaders(
            $this->headerArray()
        )->get("https://api.eko.in:25002/ekoicici/v2/billpayments/operators/$operator_id");

        return $response;
    }

    public function fetchBill(Request $request)
    {

        $data = [
            'user_code' => auth()->user()->user_code,
            'client_ref_id' => uniqid(),
            'source_ip' => $request->ip(),
            'confirmation_mobile_no' => $request['confirmation_mobile_no'],
            'utility_acc_no' => $request['utility_acc_no'],
            'sender_name' => $request['sender_name'],
            'operator_id' => $request['operator_id'],
            'latlong' => $request['latlong']
        ];
        // $data1 = $request->all();
        // $data2 = array_merge($data1, $data);

        $response = Http::withHeaders(array_merge($this->headerArray(), [
            'Connection' => 'Keep-Alive',
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'okhttp/3.9.0'
        ]))->post("https://api.eko.in:25002/ekoicici/v2/billpayments/fetchbill?initiator_id=9758105858", $data);

        return $response;
    }

    public function payBill(Request $request)
    {


        $data = [
            'user_code' => 20810200,
            // auth()->user()->user_code,
            'client_ref_id' => uniqid(),
            'utility_acc_no' => $request['utility_acc_no'],
            'confirmation_mobile_no' => $request['confirmation_mobile_no']??auth()->user()->phone_number,
            'sender_name' => $request['sender_name']??auth()->user()->name,
            'operator_id' => $request['operator_id'],
            'source_ip' => $request->ip(),
            'latlong' => $request['latlong'],
            'amount' => $request['amount'],
            'hc_channel' => 1,
            'billfetchresponse' => $request['bill'] ?? ''
        ];

        $response = Http::asJson()->withHeaders(array_merge($this->headerArray(), [
            'Connection' => 'Keep-Alive',
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'okhttp/3.9.0',
        ]))->post("http://staging.eko.in:8080/ekoicici/v2/billpayments/paybill?initiator_id=9962981729", $data);
        // return $response;
        $opening_balance = auth()->user()->wallet;
        $closing_balance = $opening_balance - $data['amount'];
        $transaction_id = "BBPSE" . uniqid();
        Log::channel('response')->info($response);
        if (!array_key_exists('status', $response->json())) {
            $metadata = [
                'status' => false,
                'amount' => $data['amount'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'operator_id' => $request['operator_id'],
                'canumber' => $request['utility_acc_no'],
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']
            ];
            $this->transaction(0, "BBPS recharge failed", 'bbps', auth()->user()->id, $opening_balance, $transaction_id, $opening_balance, json_encode($metadata));
            return response(['metadata' => $metadata]);
        }
        if ($response['status'] == 0) {
            $metadata = [
                'status' => true,
                'sender_id' => $response['data']['sender_id'],
                'user' => auth()->user()->name,
                'canumber' => $request['utility_acc_no'],
                'user_id' => auth()->user()->id,
                'operator_id' => $request['operator_id'],
                'user_phone' => auth()->user()->phone_number,
                'amount' => $response['data']['amount'],
                'operator_name' => $response['data']['operator_name'],
                'reference_id' => $data['client_ref_id']
            ];

            $this->transaction($data['amount'], "BBPS recharge for {$response['data']['operator_name']}", 'bbps', auth()->user()->id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata));
            $this->bbpsEkoCommission(auth()->user()->id, $data['operator_id'], $data['amount']);
            $this->apiRecords($data['client_ref_id'], 'eko', $response);
        } else {
            $metadata = [
                'status' => false,
                'amount' => $data['amount'],
                'canumber' => $request['utility_acc_no'],
                'operator_id' => $request['operator_id'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']
            ];

            $this->transaction(0, "BBPS recharge for: {$data['utility_acc_no']}", 'bbps', auth()->user()->id, $opening_balance, $transaction_id, $opening_balance, json_encode($metadata));
            $this->apiRecords($data['client_ref_id'], 'eko', $response);
        }
        return response(['metadata' => $metadata]);
    }
}
