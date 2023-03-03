<?php

namespace App\Http\Controllers\Razorpay;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Models\User;

class PayoutController extends Controller
{
    public function bankPayout(Response $request, $amount)
    {
        $data = [
            'account_number' => '2323230013085171',
            'fund_account_id' => $request['id'],
            'amount' => $amount*100,
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
            'beneficiary_name' => $request['bank_account']['name']?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
        $balance_left = $walletAmt[0] - $transfer['amount'];
        if ($transfer->status() == 200) {
            User::where('id', auth()->user()->id)->update([
            'wallet' => $balance_left
            ]);
            return response(['message' => 'Transaction sucessfull', 'payout_id' => $transfer['id'], 'beneficiary_name' => $request['bank_account']['name'], 'amount' => $transfer['amount'], 'bank_account' => $request['bank_account']['account_number'], 'balance_left' => $balance_left], 200);
        }else {
            return response('Transaction failed', 400);
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
        $payout = DB::table('payouts')->get();

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
