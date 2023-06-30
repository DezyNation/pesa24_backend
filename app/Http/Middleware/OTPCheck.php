<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class OTPCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate([
            'otp' => 'required'
        ]);
        $user = User::findOrFail(auth()->user()->id);
        if (!Hash::check($request['otp'], $user->otp)) {
            return response("OTP is wrong!", 406);
        }
        return $next($request);
    }
}
