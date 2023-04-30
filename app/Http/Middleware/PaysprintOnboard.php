<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaysprintOnboard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $merchant_code = auth()->user()->paysprint_merchant;
        if (is_null($merchant_code) || empty($merchant_code)) {
            return response("AePS onboarding incomplete, go to profile page.", 501);
        }
        return $next($request);
    }
}
