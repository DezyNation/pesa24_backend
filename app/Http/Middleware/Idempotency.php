<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class Idempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::has($request->header('x-razorpay-event-id') ?? 1234) || Cache::has($request['payoutId'])) {
            Log::channel('reversals')->info('idempotency', $request->all());
            return response("Request is processing", 200);
        }
        return $next($request);
    }
}
