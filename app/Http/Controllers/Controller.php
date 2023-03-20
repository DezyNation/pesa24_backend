<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {
        $data = DB::table('services')->where(['is_active' => 1, 'can_subscribe' => 1])->get(['id', 'type', 'service_name', 'operator_name', 'image_url', 'price']);
        return $data;
    }

    public function activateService(Request $request)
    {
        $service_id = $request['id'];
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

    public function transaction(int $amount, string $service, string $service_type, int $user_id, int $opening_balance, string $transaction_id, int $closing_balance, int $credit = 0)
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

    public function baseCommission(int $amount, int $user_id, int $service_id)
    {
        $result = DB::table('users')
            ->join('package_user', 'users.id', '=', 'package_user.user_id')
            ->join('packages', 'package_user.package_id', '=', 'packages.id')
            ->join('package_service', 'packages.id', '=', 'package_service.package_id')
            ->join('service_user', 'users.id',  '=', 'service_user.user_id')
            ->join('services', 'package_service.service_id', '=', 'services.id')
            ->select('package_service.*', 'services.service_name')
            ->where(['service_user.user_id' => $user_id, 'service_user.service_id' => $service_id, 'package_service.service_id' => $service_id, 'package_user.user_id' => $user_id])
            ->where('from', '<', $amount)
            ->where('to', '>=', $amount)
            ->get();
        $array = $result->toArray();
        $user = User::findOrFail($user_id);
        $user_role = $user->getRoleNames();
        $role_commission = $user_role[0] . "_" . "commission";
        $service_name = $result[0]['service_name'];
        $flat = $array[0]['is_flat'];
        if ($flat) {
            $commission = $amount * $array[0]["$role_commission"] / 100;
        } else {
            $commission = $array[0]["$role_commission"];
        }
        $opening_balance = $user->wallet;
        $closing_balance = $opening_balance + $commission;

        $user->update([
            'wallet' => $closing_balance
        ]);
        $transaction_id = "COM" . strtoupper(Str::random(5));
        $this->transaction($commission, "Commission for $service_name", 'commission', $user_id, $opening_balance, $transaction_id, $closing_balance);

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->baseCommission($amount, $parent_id[0], $service_id);
        }
    }
}
