<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Mail\SendOtp;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

        if ($request['authMethod'] == 'email') {
            $user = User::where('email', $request['email'])->first();
            if (!$user || !Hash::check($request['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $otp = 1234;
            Mail::to($user->email)->queue(new SendOtp($otp));
            $user->update(['otp' => Hash::make($otp)]);



            return response("OTP sent on your mobile number", 200);
        } else {
            $user = User::where('phone', $request['phone'])->first();
            if (!$user || !Hash::check($request['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $phone = $request['phone'];
            $otp = rand(1000, 9999);
            $user->update(['otp' => Hash::make($otp)]);
            $text = "$otp is your verification OTP for change your Mpin/Password. -From P24 Technology Pvt. Ltd";
            Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
            return response("OTP sent on your email", 200);
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
        if (is_null($request['latlong'])) {
            return response('Location not found, please enable you GPS', 500);
        }

        if (!is_null($request['mpin'])) {
            if ($request['authMethod'] == 'email') {
                $user = User::where('email', $request['user_id'])->first();
                if (!$user || !Hash::check($request['mpin'], $user->mpin)) {
                    throw ValidationException::withMessages([
                        'error' => ['Given details do not match our records.'],
                    ]);
                }
                $request->authenticateEmail();

                $request->session()->regenerate();

                return response(['id' => auth()->user()->id, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name], 200);
            } else {
                $user = User::where('phone', $request['user_id']);
                if (!$user || !Hash::check($request['mpin'], $user->mpin)) {
                    throw ValidationException::withMessages([
                        'error' => ['Given details do not match our records.'],
                    ]);
                }
                $request->authenticatePhone();

                $request->session()->regenerate();

                return response(['id' => auth()->user()->id, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name], 200);
            }
        } else {
            if ($request['authMethod'] == 'email') {
                $user = User::where('email', $request['user_id'])->first();
                if (!$user || !Hash::check($request['otp'], $user->otp)) {
                    throw ValidationException::withMessages([
                        'error' => ['Given details do not match our records.'],
                    ]);
                }
                $request->authenticateEmail();

                $request->session()->regenerate();

                return response(['id' => auth()->user()->id, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name], 200);
            } else {
                $user = User::where('phone', $request['user_id']);
                if (!$user || !Hash::check($request['otp'], $user->otp)) {
                    throw ValidationException::withMessages([
                        'error' => ['Given details do not match our records.'],
                    ]);
                }
                $request->authenticatePhone();

                $request->session()->regenerate();

                return response(['id' => auth()->user()->id, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name], 200);
            }
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
