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
            $data = DB::table('transactions')->where('trigered_by', auth()->user()->id)->get();
            return $data;
        }

        $data = DB::table('transactions')->where(['service_type' => $name, 'trigered_by' => auth()->user()->id])->get();
        return $data;
    }
}
