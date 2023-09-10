<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Concurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user_id = auth()->user()->id;
        if (Cache::has(time() . $request['payload']['payout']['entity']['notes']['userId']) || Cache::has(time() . $request['beneficiaryId']) || Cache::has(time() . auth()->user()->id)) {
            Log::channel('concurrency', $request->all());
            return response("Please wait, another transaction is in process.", 503);
        }
        return $next($request);
    }
}
