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
        ->select('transactions.*', 'users.name as trigered_by_name', 'users.phone_number as trigered_by_phone', 'users.organization_id', 'beneficiaries.name', 'beneficiaries.phone_number', 'users.wallet as wallet_amount')
        ->where('users.organization_id', 7)
        ->where('roles.name', '!=', 'admin')
        ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::tomorrow()])
        ->latest('transactions.created_at')
        ->groupBy(['trigered_by'])
        ->get();

        return $data;
    }
}