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
            ->where(['transactions.service_type' => $data, 'admin.organization_id' => auth()->user()->organization_id])
            ->select('users.name', 'transactions.*', 'admin.organization_id', 'admin.first_name as done_by', 'admin.phone_number as done_by_phone')
            ->latest()
            ->paginate(20);
        return $data;
    }

    public function view($id=null)
    {
        if (is_null($id)) {
            $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone')
            ->paginate(20);

            return $data;
        } 

        $data = DB::table('transactions')
        ->join('users', 'users.id', '=', 'transactions.user_id')
        ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
        ->where('transactions.id', $id)
        ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone')
        ->paginate(20);
        
        
        return $data;
    }

    public function userTransction($id)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name', 'admin.phone_number')
            ->where('transactions.trigered_by', $id)->latest()->paginate(20);
        return $data;
    }

    public function transactionPeriod()
    {
        $data  = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name', 'transactions.*', 'admin.first_name', 'admin.phone_number')
            ->where('created_at', '>=', Carbon::now()->subDay()->toDateTimeString())
            ->whereJsonContains('transactions.metadata->status', 'true')
            ->latest()->paginate(20);
        return $data;
    }


    public function dailySales(Request $request)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->join('users as beneficiaries', 'beneficiaries.id', '=', 'transactions.user_id')
            ->select('transactions.*', 'users.name as trigered_by_name', 'users.phone_number as trigered_by_phone', 'users.organization_id', 'beneficiaries.name', 'beneficiaries.phone_number')
            ->where('users.organization_id', auth()->user()->organization_id)
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::yesterday(), $request['to'] ?? Carbon::tomorrow()])
            ->get();

        // $data2 = DB::table('users')
        //     ->join('transactions', 'transactions.trigered_by', '=', 'users.id')
        //     ->select('users.name', 'transactions.*')
        //     ->get();

        // $data = collect($data2);

        // $groupwithcount = $data->map(function ($group) {
        //     return [
        //         [
        //             'test' => $group->name,
        //             'transaction_id' => $group->transaction_id,
        //             'debit_amount' => $group->debit_amount,
        //             'credit_amount' => $group->credit_amount,
        //         ]
        //     ];
        // });

        // return $groupwithcount;
        // $collection = collect($data);

        // $transaction = $collection->groupBy(['trigered_by', 'service_type'])->map(function ($item) {
        //     return $item->map(function ($key) {
        //         return ['transactions' => $key, 'debit_amount' => $key->sum('debit_amount'), 'credit_amount' => $key->sum('credit_amount')];
        //     });
        // });

        return $data;
    }
}
