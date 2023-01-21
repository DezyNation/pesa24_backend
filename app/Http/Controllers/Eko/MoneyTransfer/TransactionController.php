<?php

namespace App\Http\Controllers\Eko\MoneyTransfer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class TransactionController extends Controller
{
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
}
