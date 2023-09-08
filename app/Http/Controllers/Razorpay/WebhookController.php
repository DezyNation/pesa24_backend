<?php

namespace App\Http\Controllers\Razorpay;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\PayoutStatusUpdated;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CommissionController;
use Illuminate\Support\Facades\Cache;

class WebhookController extends CommissionController
{
    public function confirmPayout(Request $request)
    {
        Log::channel('callback')->info('callback-razorpay', $request->all());
        Cache::put($request->header('x-razorpay-event-id'), $request->header('x-razorpay-event-id'), 600);
        Cache::put(time(), time(), 60);
        DB::transaction(function () use ($request) {

            $payout_id = $request['payload.payout.entity.id'];
            $payout = DB::table('payouts')->where('payout_id', $payout_id);
            if (!$payout->exists()) {
                $array = [
                    'status' => true,
                    'message' => 'Payout Does not exists in this DB',
                    'payout_id' => $payout_id
                ];
                Log::channel('callback')->info('payout-not-found', $array);
                return response("Transaction Not found");
            }
            $payout = $payout->get();
            if ($payout[0]->status == 'processed' || $payout[0]->status == 'reversed' || $payout[0]->status == 'cancelled' || $payout[0]->status == 'failed') {
                $array = [
                    'status' => true,
                    'message' => 'transaction was processed already'
                ];
                Log::channel('callback')->info('callback-razorpay', $array);
                return response("Transaction Processed Already");
            }

            if ($request['payload.payout.entity.status'] == 'reversed') {
                Log::channel('reversals')->info('reversal', $request->all());

                return true;
            }

            $data = DB::table('payouts')->where('payout_id', $payout_id);
            $data->update([
                'status' => $request['payload.payout.entity.status'],
                'utr' => $request['payload.payout.entity.utr'],
                'updated_at' => now(),
            ]);

            DB::table('transactions')->where('transaction_id', $request['payload.payout.entity.reference_id'])->update(['metadata->status' => $request['payload.payout.entity.status'], 'metadata->utr' => $request['payload.payout.entity.utr'], 'updated_at' => now()]);

            if ($request['payload.payout.entity.status'] == 'processed') {
                $result = $data->get();
                $utr = $request['payload.payout.entity.utr'] ?? 'No UTR';
                // event(new PayoutStatusUpdated("Amount {$result[0]->amount} ($utr)", $content = "Payout {$request['payload.payout.entity.id']} {$request['payload.payout.entity.status']}", $result[0]->user_id));
                // $this->payoutCommission($result[0]->user_id, $request['payload.payout.entity.amount'] / 100, $request['payload.payout.entity.reference_id'], $result[0]->account_number);
            }
            if ($request['payload.payout.entity.status'] == 'cancelled' || $request['payload.payout.entity.status'] == 'failed' || $request['payload.payout.entity.status'] == 'rejected') {
                $result = $data->get();
                $user = User::findOrFail($result[0]->user_id);
                $metadata = [
                    'status' => $request['payload.payout.entity.status'],
                    'amount' => $request['payload.payout.entity.amount'] / 100
                ];
                $account_number = $result[0]->account_number;
                $this->transaction(0, "Payout Reversal for account $account_number", 'payout', $result[0]->user_id, $user->wallet, $request['payload.payout.entity.reference_id'], $user->wallet + $request['payload.payout.entity.amount'] / 100, json_encode($metadata), $request['payload.payout.entity.amount'] / 100);
                $commission = $this->razorpayReversal($result[0]->amount, $result[0]->user_id, $request['payload.payout.entity.reference_id'], $result[0]->account_number);
                $utr = $request['payload.payout.entity.utr'] ?? 'No UTR';
                // event(new PayoutStatusUpdated("Amount {$result[0]->amount} ($utr)", "Payout {$request['payload.payout.entity.status']}", $result[0]->user_id));
            }
            return true;
        });
    }
}
