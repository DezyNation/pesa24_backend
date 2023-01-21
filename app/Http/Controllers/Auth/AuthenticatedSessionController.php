<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Mail\SendOtp;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an OTP API.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function sendOtp(LoginRequest $request)
    {
        /**@return 
         * OTP*/
        if ($request['authMethod'] == 'email') {
            $user = User::where('email', $request['user_id'])->first();
            if (! $user || ! Hash::check($request['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $otp = rand(1000, 9999);
            Mail::to($user->email)->queue(new SendOtp($otp));
            $user->update(['otp' => Hash::make($otp)]);



            return response("OTP sent on your mobile number $otp", 200);
        } else {
            $user = User::where('phone', $request['user_id'])->first();
            if (! $user || ! Hash::check($request['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $otp = rand(1000, 9999);
            $user->update(['otp' => Hash::make($otp)]);
            return response("OTP sent on your email $otp", 200);
        }        
        
    }


    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LoginRequest $request)
    {
        if ($request['authMethod'] == 'email') {
            $user = User::where('email', $request['email'])->first();
            if (! $user || ! Hash::check($request['otp'], $user->otp)) {
                throw ValidationException::withMessages([
                    'error' => ['Given details do not match our records.'],
                ]);
            }
            $request->authenticateEmail();
            
            $request->session()->regenerate();
            
            return response(['id' => auth()->user()->id, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name], 200);
        } else {
            $user = User::where('phone', $request['phone']);
            if (! $user || ! Hash::check($request['otp'], $user->otp)) {
                throw ValidationException::withMessages([
                    'error' => ['Given details do not match our records.'],
                ]);
            }
            $request->authenticatePhone();

            $request->session()->regenerate();
    
            return response(['id' => auth()->user()->id, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name], 200);
        }
        
        return response('Bad Request', 400);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
