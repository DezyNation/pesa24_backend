<?php

namespace App\Http\Controllers\RechargeTradition;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class RechargeController extends CommissionController
{
    public function recharge(Request $request)
    {
        $data = [
            'username' => env('RECHARGE_TRADITION_USER'),
            'api_token' => env('RECHARGE_TRADITION_TOKEN'),
            'number' => $request['canumber'],
            'amount' => $request['amount'],
            'operator' => $request['secondaryOperatorCode'],
            'ref_id' => uniqid("JND", true)
        ];

        $response = Http::get('https://www.rechargetradition.com/webservices/api/recharge', $data);
        $transaction_id = $data['ref_id'];
        $status = $response['status'];
        $this->apiRecords($transaction_id, 'recharge-tradition', $response);
        Log::channel('response')->info('recharge-tradition', $response->json());
        DB::table('recharge_requests')->insert([
            'user_id' => auth()->user()->id,
            'provider' => 'recharge-tradition',
            'operator' => $data['operator'],
            'operator_name' => $request['operatorName'],
            'status' => strtolower($status),
            'amount' => $data['amount'],
            'reference_id' => $transaction_id,
            'ca_number' => $data['number'],
            'ack_no' => $response['txn_id'],
            'opt_id' => $response['opt_id'],
            'created_at' => now(),
            "updated_at" => now()
        ]);

        if ($status == 'Pending' || $status == 'Accepted' || $status == 'Success') {
            $metadata = [
                'status' => strtolower($status),
                'mobile_number' => $data['number'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'amount' => $data['amount'],
                'operator' => $response['operator'],
                'reference_id' => $transaction_id,
                'acknowldgement_number' => $response['txn_id'],
            ];
            $walletAmt = auth()->user()->wallet;
            $balance_left = $walletAmt - $data['amount'];

            $this->transaction($data['amount'], "Recharge for Mobile {$data['number']}", 'recharge', auth()->user()->id, $walletAmt, $transaction_id, $balance_left, json_encode($metadata));
            $this->rechargeCommissionPaysprint(auth()->user()->id, $data['operator'],  $request['amount'], $transaction_id, $data['number']);
            return [$response, 'metadata' => $metadata];
        } else {
            $metadata = [
                'status' => 'failed',
                'mobile_number' => $data['number'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'amount' => $data['amount'],
                'refid' => $data['ref_id'],
                'operator' => $data['operator'],
                'reason' => $response['message']
            ];
            return [$response, 'metadata' => $metadata];
        }
    }

    public function updateRecharge(Request $request)
    {
        $request->validate([
            'referenceId' => 'required|exists:recharge_requests,reference_id'
        ]);

        $reference_id = $request['referenceId'];

        $recharge = DB::table('recharge_requests')->where('reference_id', $reference_id)->first();

        if ($recharge->status !== 'Pending' || $recharge->status !== 'Accepted') {
            return response("This recharge has been processed already.");
        }

        $data = [
            'username' => env('RECHARGE_TRADITION_USER'),
            'api_token' => env('RECHARGE_TRADITION_TOKEN'),
            'ref_id' => $reference_id,
            'recharge_date' => $request['date']
        ];

        $response = Http::post('https://www.rechargetradition.com/webservices/api/statusByRefId', $data);

        if ($response['status'] == 'Failure' || $response['status'] == 'Refunded') {
            $user = User::find($recharge->user_id);
            $wallet = $user->wallet;
            $closing_balance = $wallet + $recharge->amount;

            DB::table('recharge_requests')->where('reference_id', $reference_id)->update([
                'status' => $response['status'],
                'updated_at' => now()
            ]);

            DB::table('transactions')->where('transaction_id', $reference_id)->update([
                'metadata->status' => $response['status'],
                'updated_at' => now()
            ]);

            $metadata = [
                'status' => $response['status'],
                'event' => 'recharge.refund',
                'operator_name' => $response['operator'],
                'message' => $response['message'],
                'opt_id' => $response['opt_id']
            ];

            $this->notAdmintransaction(0, "Recharge refund for {$recharge->ca_number}", 'recharge', $recharge->user_id, $wallet, $recharge->reference_id, $closing_balance, json_encode($metadata), $recharge->amount);
            $this->rechargeRevCommission($recharge->user_id, $response['operator'], $recharge->amount, $reference_id, $recharge->ca_number);
        }
    }
}
