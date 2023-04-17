<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminTransactionController extends Controller
{
    public function index()
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name', 'transactions.*', 'admin.first_name as done_by', 'admin.phone_number as done_by_phone')
            ->latest()
            ->paginate(20);
        return $data;
    }

    public function categoryIndex($data)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->where('transactions.service_type', $data)
            ->select('users.name', 'transactions.*', 'admin.first_name as done_by', 'admin.phone_number as done_by_phone')
            ->latest()
            ->paginate(20);
        return $data;
    }

    public function view($id)
    {
        $data = DB::table('transactions')->where('id', $id)->paginate(20);
        return $data;
    }

    public function userTransction($id)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name', 'transactions.*', 'admin.first_name', 'admin.phone_number')
            ->where('user_id', $id)->latest()->paginate(20);
        return $data;
    }

    public function transactionPeriod()
    {
        $data  = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name', 'transactions.*', 'admin.first_name', 'admin.phone_number')
            ->where('created_at', '>=', Carbon::now()->subDay()->toDateTimeString())
            ->latest()->paginate(20);
        return $data;
    }


    public function dailySales(Request $request)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->join('users as beneficiaries', 'beneficiaries.id', '=', 'transactions.user_id')
            ->select('transactions.credit_amount', 'transactions.debit_amount', 'transactions.service_type', 'transactions.trigered_by', 'transactions.user_id', 'transactions.created_at', 'transactions.meta_data', 'users.name as trigered_by_name', 'users.phone_number as trigered_by_phone', 'users.organization_id', 'beneficiaries.name', 'beneficiaries.phone_number')
            ->where('users.organization_id', auth()->user()->organization_id ?? 5)
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::yesterday(), $request['to'] ?? Carbon::tomorrow()])
            ->get();

        $collection = collect($data);

        $transaction = $collection->groupBy(['trigered_by', 'service_type'])->map(function ($item) {
            return $item->map(function ($key) {
                return ['trigered_by' => $key, 'debit_amount' => $key->sum('debit_amount'), 'credit_amount' => $key->sum('credit_amount')];
            });
        });

        return $transaction;
    }
}
