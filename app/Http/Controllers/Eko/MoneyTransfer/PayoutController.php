<?php

namespace App\Http\Controllers\Eko\MoneyTransfer;

use Stringable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class PayoutController extends CommissionController
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
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    public function payout(Request $request)
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'client_ref_id' => uniqid() . Str::random(4),
            'service_code' => 45,
            'payment_mode' => 4,
            'recipient_name' => 'Rishi Kumar',
            'account' => 123456789012,
            'ifsc' => 'SBIN0032284',
            'amount' => 1000,
            'sender_name' => 'Rupesh',
        ];

        $user_code = 20810200;

        $response = Http::withHeaders(
            $this->headerArray()
        )->post("https://staging.eko.in:25004/ekoapi/v1/agent/user_code:{$user_code}/settlement", $data);

        return $response;

        if ($response['status'] == 0) {
            $metadata = [
                'status' => true,
                'amount' => $data['amount'],
                'account' => $data['account']
            ];
            $opening_balance = auth()->user()->wallet;
            $closing_balance = $opening_balance - $data['amount'];
            $this->transaction($data['amount'], "Payout to {$data['recipient_name']}", 'payout', auth()->user()->id, $opening_balance, $data['client_ref_id'], $closing_balance, json_encode($metadata));
            $this->payoutCommission(auth()->user()->id, $data['amount']);

            return ['metadata' => $metadata];
        }
        return $response;
    }
}
