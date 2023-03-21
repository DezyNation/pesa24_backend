<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\ParentUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\v1\UserResource;
use Illuminate\Support\Facades\Session;
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
            'parent' => ['integer'],
            'userPlan' => ['required', 'integer'],
            'firstName' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'userEmail' => ['required', 'email'],
            'userPhone' => ['required', 'digits:10'],
            'alternativePhone' => ['digits:10'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'alpha'],
            'companyType' => ['required', 'alpha'],
            'aadhaarNum' => ['required', 'digits:12'],
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
        $id = DB::table('organizations')->where('code', Session::get('organization_code'))->pluck('id');

        $pan = $request->file('pan')->store('pan');
        $aadhar_front = $request->file('aadhaarFront')->store('aadhar_front');
        $aadhar_back = $request->file('aadhaarBack')->store('aadhar_back');
        $profile = $request->file('profilePic')->store('profile');

        $password = Str::random(8);
        $mpin = rand(1001, 9999);
        $to = $request['userEmail'];
        $name = $request['first_name'] . " " . $request['last_name'];

        $user = User::create([
            'first_name' => $request['firstName'],
            'last_name' => $request['lastName'],
            'name' => $request['firstName'] . " " . $request['lastName'],
            'has_parent' => $request['hasParent'],
            'phone_number' => $request['phoneNumber'],
            'email' => $request['userEmail'],
            'alternate_phone' => $request['alternativePhone'],
            'user_code' => $request['user_code'],
            'company_name' => $request['firmName'],
            'firm_type' => $request['companyType'],
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
            'aadhar_front' => $aadhar_front,
            'aadhar_back' => $aadhar_back,
            'pan' => $pan,
            'profile_pic' => $profile,
        ])->assignRole($request['userType']);

        if ($request['hasParent']) {
            DB::table('user_parent')->insert([
                'user_id' => $user->id,
                'parent_id' => $request['parent'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        DB::table('package_user')->insert([
            'user_id' => $user->id,
            'package_id' => $request['userPlan'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if (!$request['isActive']) {
            return response()->json(['message' => 'User created Successfully']);
        }
        Mail::raw("Hello Your one time password is $password adn MPIN is $mpin", function ($message) use ($to, $name) {
            $message->from('info@pesa24.co.in', 'John Doe');
            $message->to($to, $name);
            $message->subject('Welcome to Pesa24');
            $message->priority(1);
        });
        return response()->json(['message' => 'User created Successfully']);
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

    public function getUsers(string $role, int $parent = null)
    {
        $org = Session::get('organization_code');
        $org_id = DB::table('organizations')->where('code', $org)->pluck('id');
        if (is_null($parent)) {
            $user = User::role($role)->with(['children' => function ($query) use ($role) {
                $query->select('user_id', 'parent_id', 'name')->role($role);
            }])->where(['organization_id' => $org_id])->get();

            return $user;
        }


        $user = User::role($role)->with(['children' => function ($query) use ($role) {
            $query->select('user_id', 'parent_id', 'name')->role($role);
        }])->where(['id' => $parent, 'organization_id' => $org_id])->get();

        return $user;
    }

    public function userInfo(string $role, $id = null)
    {

        $org = 'DEZ45';
        $org_id = DB::table('organizations')->where('code', $org)->pluck('id');
        if (is_null($id)) {
            $user = User::role($role)->with('packages:name')->where(['organization_id' => $org_id])->get();
            return $user;
        }

        $user = User::role($role)->with('packages:name')->where(['id' => $id, 'organization_id' => $org_id])->get();
        return $user;
    }

    public function active($id, $bool)
    {
        User::where('id', $id)->update([
            'is_active' => $bool
        ]);

        return response()->noContent();
    }
}

// $user = User::role($role)->with(['children' => function ($query) use ($role) {
//     $query->select('user_id', 'parent_id', 'name')->role($role);
// }])->where(['id' => $id, 'organization_id' => $org_id])->get();
// return $user;