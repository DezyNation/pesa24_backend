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
        switch ($tennure) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            default:
                $start = Carbon::today();
                $end = Carbon::tomorrow();
                break;
        }

        $table = DB::table('transactions')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->join('users', 'users.id', '=', 'transactions.trigered_by');
        // $table = DB::table('transactions')
        //     ->join('users', 'users.id', '=', 'transactions.trigered_by');

        $aeps = $table->where(['transactions.trigered_by' => auth()->user()->id??85, 'service_type' => 'aeps'])->select('transactions.*')->get();

        $credit_aeps = $aeps->sum('credit_amount');
        $debit_aeps = $aeps->sum('debit_amount');
        $count_aeps = $aeps->count();

        $bbps = $table->where(['transactions.trigered_by' => auth()->user()->id??85, 'service_type' => 'bbps'])->select('transactions.*')->get();
        dd($table);
        $count_bbps = $bbps->count();
        $credit_bbps = $bbps->sum('credit_amount');
        $debit_bbps = $bbps->sum('debit_amount');
        return $count_bbps;
        $dmt = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'dmt'])->select('transactions.*')->get();

        $credit_dmt = $dmt->sum('credit_amount');
        $debit_dmt = $dmt->sum('debit_amount');
        $count_dmt = $dmt->count();

        $pan = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'pan'])->select('transactions.*')->get();

        $credit_pan = $pan->sum('credit_amount');
        $debit_pan = $pan->sum('debit_amount');
        $count_pan = $pan->count();

        $payout = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'payout'])->select('transactions.*')->get();

        $credit_payout = $payout->sum('credit_amount');
        $debit_payout = $payout->sum('debit_amount');
        $count_payout = $payout->count();

        $lic = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'lic'])->select('transactions.*')->get();

        $credit_lic = $lic->sum('credit_amount');
        $debit_lic = $lic->sum('debit_amount');
        $count_lic = $lic->count();

        $fastag = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'fastag'])->select('transactions.*')->get();

        $credit_fastag = $fastag->sum('credit_amount');
        $debit_fastag = $fastag->sum('debit_amount');
        $count_fastag = $fastag->count();

        $array = [
            'aeps' => [
                'credit' => $credit_aeps,
                'debit' => $debit_aeps,
                'count' => $count_aeps
            ],
            'bbps' => [
                'credit' => $credit_bbps,
                'debit' => $debit_bbps,
                'count' => $count_bbps
            ],
            'dmt' => [
                'credit' => $credit_dmt,
                'debit' => $debit_dmt,
                'count' => $count_dmt
            ],
            'pan' => [
                'credit' => $credit_pan,
                'debit' => $debit_pan,
                'count' => $count_pan
            ],
            'payout' => [
                'credit' => $credit_payout,
                'debit' => $debit_payout,
                'count' => $count_payout
            ],
            'lic' => [
                'credit' => $credit_lic,
                'debit' => $debit_lic,
                'count' => $count_lic
            ],
            'fastag' => [
                'credit' => $credit_fastag,
                'debit' => $debit_fastag,
                'count' => $count_fastag
            ]
        ];

        return response($array);
    }
}
