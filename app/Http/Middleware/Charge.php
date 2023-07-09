<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Charge
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate([
            'amount' => 'required'
        ]);
        $user_id = auth()->user()->id;
        $amount = $request->amount;
        $table = DB::table('payoutcommissions')
        ->join('package_user', 'package_user.package_id', '=', 'payoutcommissions.package_id')
        ->where('package_user.user_id', $user_id)->where('payoutcommissions.from', '<=', $amount)
        ->where('payoutcommissions.to', '>=', $amount)
        ->get();

    if ($table->isEmpty()) {
        return $next($request);
    }

    $table = $table[0];
    $user = User::findOrFail($user_id);
    $role = $user->getRoleNames()[0];

    $fixed_charge = $table->fixed_charge;
    $is_flat = $table->is_flat;
    $gst = $table->gst;
    $role_commission_name = $role . "_commission";
    $role_commission = $table->$role_commission_name;
    $opening_balance = $user->wallet;

    if ($is_flat) {
        $debit = $fixed_charge;
        $credit = $role_commission - $role_commission * $gst / 100;
        $closing_balance = $opening_balance - $debit + $credit;
    } else {
        $debit =  $amount * $fixed_charge / 100;
        $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
        $closing_balance = $opening_balance - $debit + $credit;
    }

    $user = User::with('roles:name')->select('id', 'minimum_balance', 'wallet')->findOrFail($user_id)->makeVisible(['wallet', 'minimum_balance']);
    $minimumBalance = $user['roles'][0]['pivot']['minimum_balance'];
    if ($closing_balance < $minimumBalance || $closing_balance < $user->minimum_balance) {
        return response("Insufficient Balance.", 403);
    }
        return $next($request);
    }
}
