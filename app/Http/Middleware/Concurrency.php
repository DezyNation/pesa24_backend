<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        if (Cache::has(time() . auth()->user()->id) || Cache::has(time() . $request['beneficiaryId'])) {
            return response("Please wait, anothr transaction is in process.", 503);
        }
        return $next($request);
    }
}
