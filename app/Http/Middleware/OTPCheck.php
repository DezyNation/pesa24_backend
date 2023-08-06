<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
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
        $otp_generated_at = Carbon::parse($user->otp_generated_at);
        $current_time = Carbon::parse(now());
        $difference = $current_time->diffInRealMinutes($otp_generated_at);
        if ($difference > 1) {
            return response("OTP expired!", 406);
        }
        if (!Hash::check($request['otp'], $user->otp)) {
            return response("OTP is wrong!", 406);
        }
        return $next($request);
    }
}
