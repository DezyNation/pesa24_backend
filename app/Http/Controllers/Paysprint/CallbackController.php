<?php

namespace App\Http\Controllers\Paysprint;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function onboardCallback(Request $request)
    {
        $user_id = DB::table('users')->where('phone_number', $request['param.merchant_id'])->pluck('id');

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
            'status' => true,
        ];

        $data = $this->transaction($role_details[0]['fee'], 'Onboard and Package fee', 'onboarding', $user_id[0], $opening_balance, $transaction_id, $final_amount, json_encode($metadata));
        Log::info($data);
    }

    public function dmtCallback(Request $request)
    {
        
    }
    
}
