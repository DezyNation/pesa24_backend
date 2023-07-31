<?php

namespace App\Http\Controllers\SRKMoney;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;
use Illuminate\Support\Facades\DB;

class PayoutController extends CommissionController
{
    public function payout(Request $request)
    {
        $data = [
            'username' => env('SRK_USERNAME'),
            'password' => env('SRK_PASSWORD'),
            'requestid' => uniqid("JND", true),
            'sender' => auth()->user()->phone_number,
            'sendername' => auth()->user()->name,
            'service' => 'PYT1',
            'account' => $request['account'],
            // 'banksel' => $request['bankName'],
            'banksel' => 'Airtel Payments Bank',
            'bankifsc' => $request['ifsc'],
            'benename' => $request['beneficiaryName'],
            'mode' => $request['mode'],
            'amount' => $request['amount'],
        ];

        $transaction_id = $data['requestid'];
        $amount = $data['amount'];

        $response = Http::post('https://portal.srkmoney.in/ws/v1/Action/Process_payout_action', $data);

        Log::channel('response')->info($response);

        if ($response['Resp_code'] == 'ERR') {
            return response($response['Resp_desc'], 400);
        }

        DB::table('srk_money')->insert([
            'user_id' => auth()->user()->id,
            'request_id' => $data['requestid'],
            'resp_code' => $response['Resp_code'],
            'resp_desc' => $response['Resp_desc'],
            'benename' => $response['data']['benename'],
            'opid' => $response['data']['opid'],
            'txnid' => $response['data']['txnid'],
            'txn_amt' => $data['amount'],
            'txn_status' => $response['data']['txn_status'],
            'txn_desc' => $response['data']['txn_desc'],
            'date' => $response['data']['date'],
            'date_text' => $response['data']['datetext'],
            'commission' => $response['data']['commission'],
            'tds' => $response['data']['tds'],
            'total_charge' => $response['data']['totalcharge'],
            'total_ccf' => $response['data']['totalccf'],
            'tras_amt' => $response['data']['trasamt'],
            'charged_amt' => $response['data']['chargedamt'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $wallet = auth()->user()->wallet;
        $balance_left = $wallet - $amount;
        if ($response['data']['txn_status'] == 'SUCCESS' || $response['data']['txn_status'] == 'PENDING') {
            $metadata = [
                'status' => $response['data']['txn_status'],
                'amount' => $data['amount'],
                'account_number' => $data['account'],
                'ifsc' => $data['bankifsc'],
                'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['requestid'],
                'to' => $data['benename'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $metadata2 = [
                'status' => $response['data']['txn_status'],
                'amount' => $data['amount'],
                'account_number' => $data['account'],
                'ifsc' => $data['bankifsc'],
                // 'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['requestid'],
                'to' => $data['benename'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->transaction($amount, "Bank Payout for acc {$metadata['account_number']}", 'payout', auth()->user()->id, $wallet, $transaction_id, $balance_left, json_encode($metadata));
            $this->payoutCommission(auth()->user()->id, $amount, $transaction_id, $metadata['account_number']);
            return response(['Transaction sucessfull', 'metadata' => $metadata2], 200);
        } else {
            $metadata = [
                'status' => $response['data']['txn_status'],
                'amount' => $data['amount'],
                'account_number' => $data['account'],
                'ifsc' => $data['bankifsc'],
                'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['requestid'],
                'to' => $data['benename'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $metadata2 = [
                'status' => $response['data']['txn_status'],
                'amount' => $data['amount'],
                'account_number' => $data['account'],
                'ifsc' => $data['bankifsc'],
                // 'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['requestid'],
                'to' => $data['benename'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->transaction($amount, "Bank Payout for acc {$metadata['account_number']}", 'payout', auth()->user()->id, $wallet, $transaction_id, $balance_left, json_encode($metadata));
            $balance_left = $wallet;
            $this->transaction(0, "Refund Bank Payout for acc {$metadata['account_number']}", 'payout', auth()->user()->id, $wallet, $transaction_id, $balance_left, json_encode($metadata), $data['amount'] / 100);
            return response(['Transaction sucessfull', 'metadata' => $metadata2], 200);
        }
    }
}
