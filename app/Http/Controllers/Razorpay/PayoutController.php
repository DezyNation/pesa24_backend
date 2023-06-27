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
use Illuminate\Validation\Rule;

class PayoutController extends CommissionController
{
    public function bankPayout(Response $request, $amount, array $account_details)
    {
        $data = [
            'account_number' => '409001982207',
            'fund_account_id' => $request['id'],
            'amount' => $amount * 100,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'purpose' => 'payout',
            'reference_id' => "JANPAY" . uniqid(),
        ];

        $transfer =  Http::withBasicAuth('rzp_live_XgWJpiVBPIl3AC', '1vrEAOIWxIxHkHUQdKrnSWlF')->withHeaders([
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
            'ifsc' => $account_details['ifsc'],
            'mode' => $transfer['mode'] ?? 0,
            'purpose' => $transfer['purpose'] ?? 0,
            'reference_id' => $data['reference_id'] ?? 0,
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
        $transaction_id = $data['reference_id'];
        $this->apiRecords($data['reference_id'], 'razorpay', $transfer);
        if ($transfer['status'] == 'processing' || $transfer['status'] == 'processed') {
            $metadata = [
                'status' => $transfer['status'],
                'amount' => $amount,
                'account_number' => $request['bank_account']['account_number'],
                'ifsc' => $account_details['ifsc'],
                'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['reference_id'],
                'to' => $request['bank_account']['name'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $metadata2 = [
                'status' => $transfer['status'],
                'amount' => $amount,
                'account_number' => $request['bank_account']['account_number'],
                'ifsc' => $account_details['ifsc'],
                // 'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['reference_id'],
                'to' => $request['bank_account']['name'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->transaction($amount, "Bank Payout for acc {$metadata['account_number']}", 'payout', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            return response(['Transaction sucessfull', 'metadata' => $metadata2], 200);
        } else {
            $metadata = [
                'status' => $transfer['status'],
                'amount' => $data['amount'] / 100,
                'account_number' => $request['bank_account']['account_number'],
                'ifsc' => $account_details['ifsc'],
                'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['reference_id'],
                'to' => $request['bank_account']['name'] ?? null,
                'r_status' => $transfer->status(),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $metadata2 = [
                'status' => $transfer['status'],
                'amount' => $data['amount'] / 100,
                'account_number' => $request['bank_account']['account_number'],
                'ifsc' => $account_details['ifsc'],
                // 'utr' => null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['reference_id'],
                'to' => $request['bank_account']['name'] ?? null,
                'r_status' => $transfer->status(),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->transaction($data['amount'] / 100, "Bank Payout for acc {$metadata['account_number']}", 'payout', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->transaction(0, "Refund Bank Payout for acc {$metadata['account_number']}", 'payout', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata), $data['amount'] / 100);
            return response(['Transaction sucessfull', 'metadata' => $metadata2], 200);
        }
    }

    public function fetchPayoutUser()
    {
        $payout = DB::table('payouts')->where('user_id', auth()->user()->id)->latest()->take(10)->get([
            'payout_id',
            'beneficiary_name',
            'account_number',
            'amount',
            'utr',
            'reference_id',
            'ifsc',
            'account_number',
            'beneficiary_name',
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

    public function fetchPayoutAdmin(Request $request, $processing = null)
    {
        $search = $request['search'];
        if (!empty($search)) {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->where("payouts.account_number", 'LIKE', '%'.$search.'%')->orWhere("payouts.reference_id", 'LIKE', '%'.$search.'%')
                ->select('payouts.*', 'users.name')->latest()->paginate(100);

            return $payout;
        }
        if ($processing == 'processing') {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->where('payouts.status', 'processing')
                ->select('payouts.*', 'users.name')->latest()->get();

            return $payout;
        }
        $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
            ->where([
                'users.organization_id' => auth()->user()->organization_id
            ])
            ->where('payouts.status', '!=', 'processing')
            ->select('payouts.*', 'users.name')->latest()->paginate(80);

        return $payout;
    }

    public function payoutCall(Request $request)
    {
        $request->validate([
            'payoutId' => ['required', 'exists:payouts,payout_id']
        ]);
        $id = $request['payoutId'];
        $get_payout = DB::table('payouts')->where(['payout_id' => $id, 'status' => 'processing']);
        if (!$get_payout->exists()) {
            return response($get_payout->get());
        }
        $payout = $get_payout->get();
        $payout = $payout[0];
        $transfer =  Http::withBasicAuth('rzp_live_XgWJpiVBPIl3AC', '1vrEAOIWxIxHkHUQdKrnSWlF')->withHeaders([
            'Content-Type' => 'application/json'
        ])->get("https://api.razorpay.com/v1/payouts/$id");

        $this->apiRecords($payout->reference_id, 'razorpay', $transfer);

        DB::table('payouts')->where('payout_id', $id)->update([
            'status' => $transfer['status'],
            'utr' => $transfer['utr'],
            'updated_at' => now()
        ]);

        $reference_id = $payout->reference_id;

        $array = [
            'event' => 'admin update payout',
            'status' => $transfer['status'],
        ];
        $this->apiRecords($reference_id, 'janpay', json_encode($array));
        DB::table('transactions')->where('transaction_id', $reference_id)->update(['metadata->utr' => $transfer['utr']]);

        if ($transfer['status'] == 'processed') {
            $this->payoutCommission($payout->user_id, $payout->amount, $reference_id, $payout->account_number);
        } elseif ($transfer['status'] == 'rejected' || $transfer['status'] == 'reversed' || $transfer['status'] == 'cancelled') {
            $user = User::find($payout->user_id);
            $closing_balance = $user->wallet + $payout->amount;
            $metadata = [
                'status' => $transfer['status'],
                'utr' => $transfer['utr'],
                'reference_id' => $reference_id,
                'amount' => $payout->amount
            ];
            $account_number = $payout->account_number;
            $this->notAdmintransaction(0, "Payout Reversal for account $account_number", 'payout', $payout->user_id, $user->wallet, $reference_id, $closing_balance, json_encode($metadata), $payout->amount);
            $commission = $this->razorpayReversal($payout->amount, $payout->user_id, $reference_id);
        }

        return $transfer['status'];
    }
}
