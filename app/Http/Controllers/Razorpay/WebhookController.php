<?php

namespace App\Http\Controllers\Razorpay;

use App\Http\Controllers\CommissionController;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends CommissionController
{
    public function confirmPayout(Request $request)
    {
        $payout_id = $request['payload.payout.entity.id'];
        $data = DB::table('payouts')->where('payout_id', $payout_id);
        $data->update([
            'status' => $request['payload.payout.entity.status']
        ]);


        if ($request['payload.payout.entity.status'] == 'processed') {
            $result = $data->get();
            $this->payoutCommission($result[0]->user_id, $result[0]->amount);
        }
        if ($request['payload.payout.entity.status'] == 'reversed') {
            $result = $data->get();
            $user = User::findOrFail($result[0]->user_id);
            $opening_balance = $user->wallet;
            $closing_balance = $opening_balance + $request['payload.payout.entity.amount'] / 100;
            $user->update([
                'wallet' => $closing_balance
            ]);
            $transaction_id = "REV" . strtoupper(Str::random(5));
            $this->transaction(0, "Payout Reversal", 'payout', $result[0]->user_id, $opening_balance, $transaction_id, $closing_balance, $request['payload.payout.entity.amount'] / 100);
            $commission = $this->razorpayReversal($result[0]->amount / 100, $result[0]->user_id);
        }

        return response()->noContent();
    }

}
