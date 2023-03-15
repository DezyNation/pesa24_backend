<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {
        $data = DB::table('services')->get(['id', 'type', 'service_name', 'operator_name', 'image_url', 'price']);
        return $data;
    }

    public function activateService(Request $request)
    {
        $service_id =$request['id'];
        $service = DB::table('services')->where('id', $service_id)->pluck('price');
        $user = auth()->user();
        $wallet = $user->wallet;
        $final_wallet = $service - $wallet;
        $user_update = User::where('id', $user->id)->update([
            'wallet' => $final_wallet
        ]);
        $activation = DB::table('service_user')->insertGetId([
            'service_id' => $request['id'],
            'user_id' => $user->id,
            'pesa24_active' => 1,
        ]);
    }

    public function transaction(int $amount, string $service, string $service_type, int $user_id, int $opening_balance, string $transaction_id, int $closing_balance, int $credit = null)
    {
        DB::table('transactions')->insert([
            'debit_amount' => $amount,
            'transaction_for' => $service,
            'user_id' => $user_id,
            'credit_amount' => $credit,
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'service_type' => $service_type,
            'transaction_id' => $transaction_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Transaction successful.']);
    }
}
