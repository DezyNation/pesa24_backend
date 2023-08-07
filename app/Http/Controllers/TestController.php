<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->join('users as beneficiaries', 'beneficiaries.id', '=', 'transactions.user_id')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('transactions.user_id', 'transactions.trigered_by', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.service_type')
            // 'users.name as trigered_by_name', 'users.phone_number as trigered_by_phone', 'users.organization_id', 'beneficiaries.name', 'beneficiaries.phone_number', 'users.wallet as wallet_amount', 
            ->where('users.organization_id', 7)
            ->where('roles.name', '!=', 'admin')
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::tomorrow()])
            ->latest('transactions.created_at')
            ->get()
            // ->groupBy(['trigered_by', 'service_type'])
            // ->sum('debit_amount')
        ;

        $records = collect($data);
        // $records->groupBy(['trigered_by', 'service_type']);
        $transaction = $records->groupBy(['trigered_by', 'service_type'])->map(function ($item) {
            return $item->map(function ($key) {
                return ['transactions' => $key, 'debit_amount' => $key->sum('debit_amount'), 'credit_amount' => $key->sum('credit_amount')];
            });
        });

        return $transaction;
    }
}
