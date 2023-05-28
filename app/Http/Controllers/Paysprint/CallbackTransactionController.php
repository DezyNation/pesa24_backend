<?php

namespace App\Http\Controllers\Paysprint;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CommissionController;
use App\Models\User;

class CallbackTransactionController extends CommissionController
{
    public function cmsDeduction($reference_id, $amount)
    {
        $data = DB::table('cms_records')->where('reference_id', $reference_id)->get();
        $user_id = $data->user_id;
        $user = User::find($user_id);
        $opening_balance = $user->wallet;
        $closing_balance = $opening_balance-$amount;
        $transaction_id = strtoupper("PESA24C".uniqid());
        $metadata = [
            'status' => true,
            'event' => 'cms',
            'amount' => $amount,
            'user' => $user->name,
            'user_id' => $user->id,
            'user_phone' => $user->phone_number
        ];
        $this->transaction($amount, 'CMS transaction', 'cms', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata));
        $this->cmsCommission($user_id, $amount, 'fino', 12);
        return true;

    }
}
