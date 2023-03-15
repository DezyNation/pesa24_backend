<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnboardFee
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user_fee = auth()->user()->onboard_fee;
        if (!$user_fee) {
            return response()->json(['message' => 'Please add funds to your account and pay the onboarding fee.']);
        }
        return $next($request);
    }
}
