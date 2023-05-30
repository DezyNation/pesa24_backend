<?php

namespace App\Http\Controllers\Pesa24\Dashboard;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserDashboardController extends Controller
{
    public function sunTransaction($service)
    {
        $credit_sum = DB::table('transactions')->where(['user_id' => auth()->user()->id, 'service_type' => $service])->get();
        $debit_sum = DB::table('transactions')->where(['user_id' => auth()->user()->id, 'service_type' => $service])->get();
        return ['credit_amount' => $credit_sum, 'debit_amount' > $debit_sum];
    }

    public function transactionLedger($name = null)
    {
        if (is_null($name)) {
            $data = DB::table('transactions')->where('trigered_by', auth()->user()->id)->latest()->paginate(20);
            return $data;
        }

        $data = DB::table('transactions')->where(['service_type' => $name, 'trigered_by' => auth()->user()->id])->latest()->paginate(20);
        return $data;
    }

    public function overView(Request $request)
    {
        $tennure = $request['tennure'];

        $aeps = $this->userTable($tennure, 'aeps');

        $bbps = $this->userTable($tennure, 'bbps');;

        $dmt = $this->userTable($tennure, 'dmt');;

        $pan = $this->userTable($tennure, 'pan');;

        $payout = $this->userTable($tennure, 'payout');;

        $lic = $this->userTable($tennure, 'lic');;

        $fastag = $this->userTable($tennure, 'fastag');

        $cms = $this->userTable($tennure, 'cms');

        $recharge = $this->userTable($tennure, 'recharge');

        $funds = $this->fundRequestCount($tennure);

        $users = $this->countLogins($tennure);



        $array = [
            $aeps,
            $bbps,
            $dmt,
            $pan,
            $payout,
            $lic,
            $fastag,
            $cms,
            $recharge,
            $funds,
            $users
        ];

        return response($array);
    }

    public function userTable($tennure, $category)
    {
        $tennure;
        switch ($tennure) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;

            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            default:
                $start = Carbon::today();
                $end = Carbon::tomorrow();
                break;
        }
        $table = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => $category]);
        return [
            $category => [
                'credit' => $table->sum('credit_amount'),
                'debit' => $table->sum('debit_amount'),
                'count' => $table->count()
            ]
        ];
    }
}
