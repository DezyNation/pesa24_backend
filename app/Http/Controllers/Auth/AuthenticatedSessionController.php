<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Mail\SendOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['sendOtp', 'store']]);
    }

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
            $otp = rand(1001, 9999);
            Mail::to($user->email)->queue(new SendOtp($otp));
            $user->update(['otp' => Hash::make($otp)]);



            return response("OTP sent on your mobile number", 200);
        } else {
            $user = User::where('phone_number', $request['phone_number'])->first();
            if (!$user || !Hash::check($request['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $phone = $request['phone_number'];
            $otp = rand(1000, 9999);
            $user->update(['otp' => Hash::make($otp)]);
            $text = "$otp is your verification OTP for change your Mpin/Password. '-From P24 Technology Pvt. Ltd";
            $otp =  Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
            // return response("OTP sent on your phone", 200);
            return 'OTP was sent.';
        }
    }

    public function adminSendOtp(LoginRequest $request)
    {
        if ($request['authMethod'] == 'email') {
            $user = User::where('email', $request['email'])->first();
            if (!$user || !Hash::check($request['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $otp = rand(1001, 9999);
            Mail::to($user->email)->queue(new SendOtp($otp));
            $user->update(['otp' => Hash::make($otp)]);



            return response("OTP sent on your mobile number", 200);
        } else {
            $user = User::where('phone_number', $request['phone_number'])->first();
            if (!$user || !Hash::check($request['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $phone = $request['phone_number'];
            $otp = rand(1000, 9999);
            $user->update(['otp' => Hash::make($otp)]);
            $text = "$otp is your verification OTP for change your Mpin/Password. '-From P24 Technology Pvt. Ltd";
            $otp =  Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
            // return response("OTP sent on your phone", 200);
            return 'OTP was sent.';
        }
    }


    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'latlong' => 'required'
        ]);
        Session::put('organization_code', $request['organization_code']);

        if ($request->has('mpin')) {
            if ($request['authMethod'] == 'email') {
                $creds = $request->only(['email', 'password']);
                $user = User::where('email', $request['email'])->first();
                if (!$user || !Hash::check($request['mpin'], $user->mpin) || !Hash::check($request['password'], $user->password)) {
                    throw ValidationException::withMessages([
                        'error' => ['Email and password does not match our records.']
                    ]);
                }
                $token = auth()->attempt($creds);
                if (!$token) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                // return $this->respondWithToken($token);
                // $request->authenticateEmail();

                // $request->session()->regenerate();

                DB::table('logins')->insert([
                    'user_id' => auth()->user()->id,
                    'ip' => $request->ip(),
                    'latlong' => $request['latlong'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response(['token' => $this->respondWithToken($token), 'id' => auth()->user()->id, 'paysprint_id' => auth()->user()->paysprint_merchant, 'eko_id' => auth()->user()->user_code, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name, 'profile_pic' => auth()->user()->profile_pic], 200);
            } else {
                $creds = $request->only(['phone_number', 'password']);
                $user = User::where('phone_number', $request['phone_number'])->first();
                if (!$user || !Hash::check($request['mpin'], $user->mpin) || !Hash::check($request['password'], $user->password)) {
                    throw ValidationException::withMessages([
                        'error' => ['Phone and password does not match our records.'],
                    ]);
                }

                $token = auth()->attempt($creds);
                if (!$token) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                // return $this->respondWithToken($token);
                // $request->authenticatePhone();

                // $request->session()->regenerate();
                DB::table('logins')->insert([
                    'user_id' => auth()->user()->id,
                    'ip' => $request->ip(),
                    'latlong' => $request['latlong'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response(['token' => $this->respondWithToken($token), 'id' => auth()->user()->id, 'paysprint_id' => auth()->user()->paysprint_merchant, 'eko_id' => auth()->user()->user_code, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name, 'profile_pic' => auth()->user()->profile_pic], 200);
            }
        } else {
            if ($request['authMethod'] == 'email') {
                $creds = $request->only(['email', 'password']);
                $user = User::where('email', $request['email'])->first();
                if (!$user || !Hash::check($request['otp'], $user->otp)) {
                    throw ValidationException::withMessages([
                        'error' => ['Email and password does not match our records.'],
                    ]);
                }

                $token = auth()->attempt($creds);
                if (!$token) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                // return $this->respondWithToken($token);
                // $request->authenticateEmail();

                // $request->session()->regenerate();

                DB::table('logins')->insert([
                    'user_id' => auth()->user()->id,
                    'ip' => $request->ip(),
                    'latlong' => $request['latlong'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response(['token' => $this->respondWithToken($token), 'id' => auth()->user()->id, 'paysprint_id' => auth()->user()->paysprint_merchant, 'eko_id' => auth()->user()->user_code, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name, 'profile_pic' => auth()->user()->profile_pic, 'wallet' => auth()->user()->wallet], 200);
            } else {
                $creds = $request->only(['phone_number', 'password']);
                $user = User::where('phone_number', $request['phone_number'])->first();
                if (!$user || !Hash::check($request['otp'], $user->otp)) {
                    throw ValidationException::withMessages([
                        'error' => ['Email and password does not match our records.'],
                    ]);
                }

                $token = auth()->attempt($creds);
                if (!$token) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                // return $this->respondWithToken($token);
                // $request->authenticatePhone();
                // $request->session()->regenerate();

                DB::table('logins')->insert([
                    'user_id' => auth()->user()->id,
                    'ip' => $request->ip(),
                    'latlong' => $request['latlong'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response(['token' => $this->respondWithToken($token), 'id' => auth()->user()->id, 'paysprint_id' => auth()->user()->paysprint_merchant, 'eko_id' => auth()->user()->user_code, 'profile_complete' => auth()->user()->profile, 'role' => auth()->user()->roles, 'name' => auth()->user()->name, 'profile_pic' => auth()->user()->profile_pic], 200);
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

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
