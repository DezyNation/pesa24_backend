<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;

class AdminTransactionController extends Controller
{
    public function index()
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name', 'transactions.*', 'admin.first_name as done_by', 'admin.phone_number as done_by_phone')
            ->latest()
            ->paginate(200);
        return $data;
    }

    public function categoryIndex(Request $request, $data)
    {
        $from = $request['from'] ?? Carbon::today();
        $to = $request['to'] ?? Carbon::tomorrow();
        if (auth()->user()->organization_id = env('SUPER_ORGANIZATION')) {
            $data = DB::table('transactions')
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
                ->whereBetween('transactions.created_at', [$from, $to])
                ->where(['transactions.service_type' => $data])
                ->select('users.name', 'transactions.*', 'admin.organization_id', 'admin.first_name as done_by', 'admin.phone_number as done_by_phone')
                ->latest()
                ->paginate(200);
            return $data;
        }

        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->where(['transactions.service_type' => $data, 'admin.organization_id' => auth()->user()->organization_id])
            ->whereBetween('transactions.created_at', [$from, $to])
            ->select('users.name', 'transactions.*', 'admin.organization_id', 'admin.first_name as done_by', 'admin.phone_number as done_by_phone')
            ->latest()
            ->paginate(200);
        return $data;
    }

    public function view(Request $request, $id = null)
    {
        $search = $request['search'];
        if (!empty($search) || !is_null($search)) {
            $data = DB::table('transactions')
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
                ->where('transactions.transaction_for', 'LIKE', '%' . $search . '%')->orWhere('transactions.transaction_id', 'LIKE', '%' . $search . '%')
                ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at')
                ->latest('transactions.created_at')
                ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to']]);

            return $data;
        }
        if (is_null($id)) {
            $data = DB::table('transactions')
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
                ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at')
                ->latest('transactions.created_at')
                ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to']]);

            return $data;
        }

        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->where('transactions.trigered_by', $id)
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
            ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at')
            ->latest('transactions.created_at')
            ->paginate(200);


        return $data;
    }

    public function userTransction(Request $request, $id)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.created_at', 'transactions.updated_at', 'transactions.transaction_for as description')
            ->where('transactions.trigered_by', $id)
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
            ->latest('transactions.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to']]);
        return $data;
    }

    public function transactionPeriod()
    {
        $data  = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->select('users.name', 'transactions.*', 'admin.first_name', 'admin.phone_number')
            ->where('created_at', '>=', Carbon::now()->subDay()->toDateTimeString())
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
            ->whereJsonContains('transactions.metadata->status', 'true')
            ->latest()->paginate(200);
        return $data;
    }


    public function dailySales(Request $request)
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->join('users as beneficiaries', 'beneficiaries.id', '=', 'transactions.user_id')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('transactions.*', 'users.name as trigered_by_name', 'users.phone_number as trigered_by_phone', 'users.organization_id', 'beneficiaries.name', 'beneficiaries.phone_number', 'users.wallet as wallet_amount')
            ->where('users.organization_id', auth()->user()->organization_id)
            ->where('roles.name', '!=', 'admin')
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
            ->latest('transactions.created_at')
            ->get();

        $collection = collect($data);
        $data = $collection->groupBy(['trigered_by', 'service_type']);

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

    public function homeStatistics(Request $request)
    {
        $request->only(['tennure']);
        $event = $request['tennure'];
        $query = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->select('transactions.*');
        switch ($event) {
            case 'week':
                $data = $query->whereBetween('transactions.created_at', [Carbon::now()->endOfWeek(), Carbon::now()->endOfWeek()])
                    ->where(['users.organization_id' => auth()->user()->organization_id, 'transactions.service_type' => $request['service']]);
                break;

            case 'month':
                $data = $query->whereBetween('transactions.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->where(['users.organization_id' => auth()->user()->organization_id, 'transactions.service_type' => $request['service']]);
                break;

            default:
                $data = $query->whereBetween('transactions.created_at', [Carbon::yesterday(), Carbon::firstWeekDay()])
                    ->where(['users.organization_id' => auth()->user()->organization_id, 'transactions.service_type' => $request['service']]);
                break;
        }

        $sum = $data->sum('tranasctions.debit_amount');
        $count = $data->count();

        return response(['sum' => $sum, 'count' => $count]);
    }
}
