<?php

namespace App\Http\Controllers\pesa24\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function sunTransaction($service)
    {
        $credit_sum = DB::table('transactions')->where(['user_id' => auth()->user()->id, 'service_type'=> $service])->sum('credit_amount');
        $debit_sum = DB::table('transactions')->where(['user_id' => auth()->user()->id, 'service_type'=> $service])->sum('debit_amount');
        return ['credit_amount' => $credit_sum, 'debit_amount' > $debit_sum];
    }
}
