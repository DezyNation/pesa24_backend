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
        Log::channel('callback')->info('callback-razorpay', $request->all());
        $payout_id = $request['payload.payout.entity.id'];
        $payout = DB::table('payouts')->where('payout_id', $payout_id)->get();
        if ($payout[0]->status == 'processed' || $payout[0]->status == 'reveresed' || $payout[0]->status == 'cancelled') {
            $array = [
                'status' => true,
                'message' => 'transaction was processed already'
            ];
            Log::channel('response')->info('callback-razorpay', $array);
            return response("Transaction Processed Already");
        }
        $data = DB::table('payouts')->where('payout_id', $payout_id);
        $data->update([
            'status' => $request['payload.payout.entity.status'],
            'utr' => $request['payload.payout.entity.utr'],
            'updated_at' => now(),
        ]);

        DB::table('transactions')->where('transaction_id', $request['payload.payout.entity.reference_id'])->update(['metadata->utr' => $request['payload.payout.entity.utr']]);

        if ($request['payload.payout.entity.status'] == 'processed') {
            $result = $data->get();
            $this->payoutCommission($result[0]->user_id, $request['payload.payout.entity.amount'] / 100, $request['payload.payout.entity.reference_id']);
        }
        if ($request['payload.payout.entity.status'] == 'reversed' || $request['payload.payout.entity.status'] == 'cancelled') {
            $result = $data->get();
            $user = User::findOrFail($result[0]->user_id);
            $opening_balance = $user->wallet;
            $closing_balance = $opening_balance + $request['payload.payout.entity.amount'] / 100;
            $transaction_id = $request['payload.payout.entity.reference_id'];
            $this->transaction(0, "Payout Reversal", 'payout', $result[0]->user_id, $opening_balance, $transaction_id, $closing_balance, $request['payload.payout.entity.amount'] / 100);
            $commission = $this->razorpayReversal($result[0]->amount, $result[0]->user_id, $transaction_id);
        }

        return response()->noContent();
    }
}
