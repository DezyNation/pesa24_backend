<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MultipleTransaction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $current_time = now();
        $recent_time = now()->subHours(12);
        $query = DB::table('transactions')->whereBetween('created_at', [$recent_time, $current_time])->where(['metadata->status' => 'processed', 'debit_amount' => $request['amount'], 'metadata->account_number' => $request['account']])->exists();
        if ($query) {
            return response("This transaction is not allowed at the moment", 405);
        }
        return $next($request);
    }
}
