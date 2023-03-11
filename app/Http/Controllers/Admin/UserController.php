<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\v1\UserResource;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new UserResource(User::with('roles')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'parentDistributor' => ['required', 'integer'],
            'userPlan' => ['required', 'integer'],
            'firstName' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'userEmail' => ['reqired', 'email'],
            'userPhone' => ['required', 'digit:10'],
            'alternativePhone' => ['digit:10'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'alpha'],
            'companyType' => ['required', 'alpha'],
            'aadhaarNum' => ['required', 'digit:12'],
            'panNum' => ['required', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}/', Rule::unique('users', 'pan_number')],
            'capAmount' => ['required', 'integer'],
            'phoneVerified' => ['required'],
            'emailVerified' => ['required'],
            'line' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'firmName' => ['string'],
            'pincode' => ['required', 'string'],
            'isActive' => ['required', 'boolean'],
            'gst' => 'string',
        ]);

        if ($request->has('parentDistributor')) {
            $parent = 1;
        } else {
            $parent = 0;
        }

        $password = Str::random(8);
        $mpin = rand(4);

        $user = User::create([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'name' => $request['first_name'] . " " . $request['last_name'],
            'has_parent' => $parent,
            'phone_number' => $request['phoneNumber'],
            'email' => $request['email'],
            'alternate_phone' => $request['alternativePhone'],
            'user_code' => $request['user_code'],
            'company_name' => $request['firmName'],
            'firm_type' => $request['firm_type'],
            'gst_number' => $request['gst'],
            'dob' => $request['dob'],
            'pan_number' => $request['panNum'],
            'aadhar' => $request['aadhar'],
            'onboard_fee' => 0,
            'referal_code' => $request['referal_code'],
            'email_verified_at' => null,
            'password' => Hash::make($password),
            'mpin' => Hash::make($mpin),
            'kyc' => 0,
            'line' => $request['line'],
            'city' => $request['city'],
            'state' => $request['state'],
            'pincode' => $request['pincode'],
            'profile' => 0,
        ])->assignRole($request['role']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new UserResource(User::with('roles')->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'max:255',
            'last_name' => 'max:255',
            'aadhaar' => 'integer|max:12|min:12',
            'pan_number' => 'string|max:10|min:10|regex:[A-Z]{5}[0-9]{4}[A-Z]{1}',
        ]);

        $user =  User::find(Auth::id());
        $user->first_name = $request['first_name'];
        $user->last_name = $request['last_name'];
        $user->name = $user->first_name . " " . $user->last_name;
        $user->aadhaar = $request['aadhaar'];
        $user->pan_number = $request['pan_number'];

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function otp(Request $request)
    {
        $otp = rand(1000, 9999);
        $user = User::find(Auth::id());
        $user->update(['otp' => Hash::make($otp)]);
        // $user->otp = ;
        return $otp;
    }

    public function verifyOtp(Request $request)
    {
        $user = User::findOrFail(Auth::id());
        if (!$user || !Hash::check($request['otp'], $user->otp)) {
            throw ValidationException::withMessages([
                'error' => ['Given details do not match our records.'],
            ]);
        }
        $user->update(['phone_number' => $request['newNumber']]);

        return ['message' => 'Phone number updated sucessfully'];
    }
}
