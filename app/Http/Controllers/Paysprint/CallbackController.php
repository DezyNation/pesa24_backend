<?php

namespace App\Http\Controllers\Paysprint;

use App\Http\Controllers\CommissionController;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class CallbackController extends CommissionController
{
    public function onboardCallback(Request $request)
    {
        $user_id = DB::table('users')->where('paysprint_merchant', $request['param.merchant_id'])->pluck('id');

        $user = User::findOrFail($user_id[0])->makeVisible(['organization_id', 'wallet']);
        $role = $user->getRoleNames();
        $role_details = json_decode(DB::table('roles')->where('name', $role[0])->get(['id', 'fee']), true);
        $id = json_decode(DB::table('packages')->where(['role_id' => $role_details[0]['id'], 'organization_id' => $user->organization_id, 'is_default' => 1])->get('id'), true);
        $opening_balance = $user->wallet;
        $final_amount = $user->wallet - $role_details[0]['fee'];

        $attach_user = DB::table('package_user')->insert([
            'user_id' => $user_id[0],
            'package_id' => $id[0]['id'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->where('id', $user_id[0])->update([
            'wallet' => $final_amount,
            'onboard_fee' => 1,
            'updated_at' => now()
        ]);

        $transaction_id = "ONBO" . strtoupper(Str::random(8));

        $metadata = [
            'status' => 200,
            'message' => 'Transaction Successful'
        ];

        $data = $this->transaction($role_details[0]['fee'], 'Onboard and Package fee', 'onboarding', $user_id[0], $opening_balance, $transaction_id, $final_amount, json_encode($metadata));
        Log::info($data);

        echo json_encode($metadata);
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
