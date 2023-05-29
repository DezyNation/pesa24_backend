<?php

namespace App\Http\Controllers\Pesa24\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function overView()
    {
        $table = DB::aeps('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by');

        $aeps = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'apes']);

        $credit_aeps = $aeps->sum('credit_amount');
        $debit_aeps = $aeps->sum('debit_amount');
        $count_aeps = $aeps->count();

        $bbps = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'bbps']);

        $credit_bbps = $bbps->sum('credit_amount');
        $debit_bbps = $bbps->sum('debit_amount');
        $count_bbps = $bbps->count();

        $dmt = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'dmt']);

        $credit_dmt = $dmt->sum('credit_amount');
        $debit_dmt = $dmt->sum('debit_amount');
        $count_dmt = $dmt->count();

        $pan = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'pan']);

        $credit_pan = $pan->sum('credit_amount');
        $debit_pan = $pan->sum('debit_amount');
        $count_pan = $pan->count();

        $payout = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'payout']);

        $credit_payout = $payout->sum('credit_amount');
        $debit_payout = $payout->sum('debit_amount');
        $count_payout = $payout->count();

        $lic = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'lic']);

        $credit_lic = $lic->sum('credit_amount');
        $debit_lic = $lic->sum('debit_amount');
        $count_lic = $lic->count();

        $fastag = $table->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => 'fastag']);

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
