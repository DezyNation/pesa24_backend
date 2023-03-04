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
        $user = User::with('roles:name')->select('id', 'minimum_balance', 'wallet')->findOrFail(auth()->user()->id);    
        $minimumBalance = $user['roles'][0]['pivot']['minimum_balance'];

        if ($user['wallet'] < $minimumBalance && $user['wallet'] < $user['minimum_balance']) {
            return response()->json(['Error' => 'Your balance is lower than minimum balance, please top-up your wallet.']);
        }
        return $next($request);
    }
}
