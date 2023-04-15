<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MinimumBalance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::with('roles:name')->select('id', 'minimum_balance', 'wallet')->findOrFail(auth()->user()->id)->makeVisible(['wallet', 'minimum_balance']);
        $minimumBalance = $user['roles'][0]['pivot']['minimum_balance'];
        $final_amount = $user->wallet - $request['amount'];
        if ($final_amount < $minimumBalance || $final_amount < $user->minimum_balance) {
            return response("You have not enough balance to make this transaction.", 403);
        }
        return $next($request);
    }
}
