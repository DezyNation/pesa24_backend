<?php

namespace App\Http\Controllers\Razorpay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PayoutController extends Controller
{
    public function bankPayout(Response $request, $amount)
    {
        $data = [
            'account_number' => '2323230013085171',
            'fund_account_id' => $request['id'],
            'amount' => $amount,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'purpose' => 'payout',
            'reference_id' => uniqid(),
        ];

        $transfer =  Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')->withHeaders([
            'Content-Type' => 'application/json'
        ])->post('https://api.razorpay.com/v1/payouts', $data);

        DB::table('payouts')->insert([
            'user_id' => auth()->user()->id,
            'payout_id' => $transfer['id'] ?? 0,
            'entity' => $transfer['entity'] ?? 0,
            'fund_account_id' => $transfer['fund_account_id'] ?? 0,
            'amount' => $transfer['amount'] ?? 0,
            'currency' => $transfer['currency'] ?? 0,
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
        ]);
        if ($transfer->status() == 200) {
            return response('Transaction sucessfull', 200);
        } else {
            return response('Transaction not sucessfull', 400);
        }
    }


    public function fetchPayoutUser()
    {
        $payout = DB::table('payouts')->get([
            'payout_id',
            'benificiary_name',
            'account_number',
            'amount'
        ]);

        return $payout;
    }

    public function fetchPayoutAdmin()
    {
        $payout = DB::table('payouts')->get();

        return $payout;
    }
}