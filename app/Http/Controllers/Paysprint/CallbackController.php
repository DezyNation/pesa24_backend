<?php

namespace App\Http\Controllers\Paysprint;

use App\Http\Controllers\CommissionController;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class CallbackController extends CommissionController
{
    public function onboardCallback(Request $request)
    {
        Log::info('request', $request->all());
        $metadata = [
            'status' => 200,
            'message' => "Transaction Done"
        ];

        if ($request['event'] == 'DMT') {
            $transaction = DB::table('transactions')->where('transaction_id', $request['param']['refid'])->get();
            DB::table('transactions')->where('transaction_id', $request['param']['refid'])
                ->update(
                    [
                        'metadata->utrnumber' => $request['param']['utr'],
                        'metadata->status' => $request['param']['status'],
                        'updated_at' => now()
                    ]
                );
            $this->dmtCommission($transaction->trigered_by, $transaction->debit_amount, $request['param']['refid']);
        }

        echo json_encode($metadata);

        return redirect('dashboard.pesa24.in');
    }

    public function dmtCallback(Request $request)
    {
        Log::info('request', $request->all());
        DB::table('dmt_transactions')->where('reference_id', $request['param.refid'])->update([
            'status' => $request['param.status'],
            'callback_meatdata' => $request->all(),
            'updated_at' => now()
        ]);
        if ($request['param.staus'] == false) {
            $transaction_id = DB::table('dmt_transactions')->where('refernce_id', $request['param.refid'])->get();
            $user = DB::table('users')->where('id', $transaction_id->user_id)->get();
            $wallet = $user->wallet;
            $dmt_amount = $request['param.amount'];
            $final_amount = $wallet + $dmt_amount;
            $refund_tran_id = "REFUND" . strtoupper(Str::random(12));
            $metadata = [
                'status' => true,
                'event' => 'refund',
                'refernce_id' => $request['param.refid'],
                'amount' => $dmt_amount
            ];
            $this->transaction(0, 'DMT refund', 'dmt', $transaction_id->user_id, $wallet, $refund_tran_id, $final_amount, json_encode($metadata), $dmt_amount);
            DB::table('users')->where('id', $user->id)->update([
                'wallet' => $final_amount,
                'updated_at' => now()
            ]);
            $this->dmtReversal($dmt_amount, $user->id);
        }

        return true;
    }

    public function onboardCallback0(Request $request)
    {
        $arr['status'] = 200;
        $arr['message'] = 'Transaction Successfull';
        Log::channel('response')->info('request', $request->all());
        echo json_encode($arr);
    }
}
