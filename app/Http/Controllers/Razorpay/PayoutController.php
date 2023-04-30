<?php

namespace App\Http\Controllers\Razorpay;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class PayoutController extends CommissionController
{
    public function bankPayout(Response $request, $amount, $service_id)
    {
        $data = [
            'account_number' => '2323230013085171',
            'fund_account_id' => $request['id'],
            'amount' => $amount * 100,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'purpose' => 'payout',
            'reference_id' => uniqid(),
        ];

        $transfer =  Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')->withHeaders([
            'Content-Type' => 'application/json'
        ])->post('https://api.razorpay.com/v1/payouts', $data);

        Log::channel('response')->info($transfer);

        DB::table('payouts')->insert([
            'user_id' => auth()->user()->id,
            'payout_id' => $transfer['id'] ?? 0,
            'entity' => $transfer['entity'] ?? 0,
            'fund_account_id' => $transfer['fund_account_id'] ?? 0,
            'amount' => $amount ?? 0,
            'currency' => $transfer['currency'] ?? 0,
            'account_number' => $request['bank_account']['account_number'],
            'fees' => $transfer['fees'] ?? 0,
            'tax' => $transfer['tax'] ?? 0,
            'status' => $transfer['status'] ?? 0,
            'utr' => $transfer['utr'] ?? null ?? 0,
            'mode' => $transfer['mode'] ?? 0,
            'purpose' => $transfer['purpose'] ?? 0,
            'reference_id' => $transfer['reference_id'] ?? 0,
            'narration' => $transfer['narration'] ?? 0,
            'batch_id' => $transfer['batch_id'] ?? 0,
            'description' => $transfer['status_details']['description'] ?? 0,
            'source' => $transfer['status_details']['source'] ?? 0,
            'reason' => $transfer['status_details']['reason'] ?? 0,
            'added_at' => $transfer['created_at'] ?? 0,
            'beneficiary_name' => $request['bank_account']['name'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
        $balance_left = $walletAmt[0] - $amount;
        if ($transfer->status() == 200) {
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);
            $transaction_id = "PAY" . strtoupper(Str::random(5));
            $metadata = [
                'status' => true,
                'amount' => $amount,
                'reference_id' => $data['reference_id'],
                'to' => $request['bank_account']['name'] ?? null,
            ];
            $this->transaction($amount, 'Bank Payout', 'dmt', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            return response(['Transaction sucessfull', 'metadata' => $metadata], 200);
        } else {
            $metadata = [
                'status' => false,
                'amount' => $data['amount'] / 100,
                'reference_id' => $data['reference_id'],
                'to' => $request['bank_account']['name'] ?? null,
                'r_status' => $transfer->status()
            ];
            return response(['Transaction sucessfull', 'metadata' => $metadata], 200);
        }
    }

    public function fetchPayoutUser()
    {
        $payout = DB::table('payouts')->where('user_id', auth()->user()->id)->latest()->take(10)->get([
            'payout_id',
            'beneficiary_name',
            'account_number',
            'amount',
            'status',
            'created_at'
        ]);

        return $payout;
    }

    public function fetchPayoutUserAll()
    {
        $payout = DB::table('payouts')->where('user_id', auth()->user()->id)->latest()->get([
            'payout_id',
            'beneficiary_name',
            'account_number',
            'amount',
            'status',
            'created_at'
        ]);

        return $payout;
    }

    public function fetchPayoutAdmin()
    {
        $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')->where([
            'users.organization_id' => auth()->user()->organization_id
        ])->select('payouts.*', 'users.name')->paginate(20);

        return $payout;
    }

    public function payoutCall()
    {
        $id = 'pout_00000000000001';
        $transfer =  Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')->withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://api.razorpay.com/v1/payouts/$id");

        DB::table('payouts')->where('payout_id', $id)->update([
            'status' => $transfer['status'],
            'updated_at' => now()
        ]);

        return $transfer['status'];
    }
}
