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
use Illuminate\Support\Facades\Http;
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
            // 'parent' => ['integer'],
            'userPlan' => ['required', 'integer'],
            'firstName' => ['required', 'string'],
            // 'lastName' => ['required', 'string'],
            'userEmail' => ['required', 'email', 'unique:users,email'],
            'userPhone' => ['required', 'digits:10', 'unique:users,phone_number'],
            // 'alternativePhone' => ['digits:10'],
            // 'dob' => ['required', 'date', 'before_or_equal:-18 years'],
            // 'gender' => ['required', 'alpha'],
            // 'aadhaarNum' => ['required', 'digits:12', 'unique:users,aadhaar'],
            // 'panNum' => ['required', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}/', Rule::unique('users', 'pan_number')],
            'capAmount' => ['required', 'integer'],
            // 'phoneVerified' => ['required'],
            // 'emailVerified' => ['required'],
            'userRole' => ['required', 'exists:roles,name'],
            // 'line' => ['required', 'string'],
            // 'city' => ['required', 'string'],
            // 'state' => ['required', 'string'],
            // // 'firmName' => ['string'],
            // 'pincode' => ['required', 'string'],
            // 'isActive' => ['required', 'boolean'],
            // // 'gst' => 'string',
            // 'pan' => 'required',
            // 'aadhaarFront' => 'required',
            // 'aadhaarBack' => 'required',
            // 'profilePic' => 'required'
        ]);
        $id = auth()->user()->organization_id;

        if ($request->hasFile('pan')) {
            $pan = $request->file('pan')->store('pan');
        } else {
            $pan = null;
        }
        if ($request->hasFile('aadhaarFront')) {
            $aadhar_front = $request->file('aadhaarFront')->store('aadhar_front');
        } else {
            $aadhar_front = null;
        }
        if ($request->hasFile('aadhaarBack')) {
            $aadhar_back = $request->file('aadhaarBack')->store('aadhar_back');
        } else {
            $aadhar_back = null;
        }
        if ($request->hasFile('profilePic')) {
            $profile = $request->file('profilePic')->store('profile');
        } else {
            $profile = null;
        }


        $password = Str::random(8);
        $mpin = rand(1001, 9999);
        $to = $request['userEmail'];
        $name = $request['firstName'] . " " . $request['middleName'] . " " . $request['lastName'];

        $user = User::create([
            'first_name' => $request['firstName'] ?? null,
            'last_name' => $request['lastName'] ?? null,
            'name' => $name,
            'has_parent' => $request['hasParent'] ?? null,
            'phone_number' => $request['userPhone'] ?? null,
            'email' => $request['userEmail'],
            'alternate_phone' => $request['alternativePhone'] ?? null,
            'middle_name' => $request['middleName'] ?? null,
            'gender' => $request['gender'] ?? null,
            'user_code' => $request['user_code'] ?? null,
            'company_name' => $request['firmName'] ?? null,
            'firm_type' => $request['companyType'] ?? null,
            'gst_number' => $request['gst'] ?? null,
            'dob' => $request['dob'] ?? null,
            'pan_number' => $request['panNum'] ?? null,
            'aadhaar' => $request['aadhaarNum'] ?? null,
            'onboard_fee' => 0,
            'referal_code' => $request['referal_code'] ?? null,
            'email_verified_at' => null,
            'password' => Hash::make($password),
            'mpin' => Hash::make($mpin),
            'kyc' => 0,
            'line' => $request['line'] ?? null,
            'city' => $request['city'] ?? null,
            'state' => $request['state'] ?? null,
            'pincode' => $request['pincode'] ?? null,
            'profile' => 0,
            'aadhar_front' => $aadhar_front,
            'aadhar_back' => $aadhar_back,
            'minimum_balance' => $request['capAmount'] ?? null,
            'pan' => $pan,
            'is_active' => $request['isActive'] ?? null,
            'profile_pic' => $profile,
            'organization_id' => $id
        ])->assignRole($request['userRole']);
        $data = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'organisation_code' => $request['organization_code'] ?? 'JANPAY',
            'allowed_pages' =>
            [
                'allBasic', 'basicServiceActivate', 'basicTransactionLedger', 'allAeps', 'aepsTransaction', 'aepsAadhaarPay', 'aepsReport', 'allBbps', 'bbpsTransaction', 'bbpsReport', 'alldmt', 'dmtTransaction', 'dmtReport', 'allPayout', 'payoutTransaction', 'payoutReport', 'allRecharge', 'rechargeTransaction', 'rechargeReport', 'allPan', 'panTransaction', 'panReport', 'allCms', 'cmsTransaction', 'cmsReport', 'allLic', 'licTransaction', 'licReport', 'allAxis', 'axisTransaction', 'axisReport', 'allFastag', 'fastagTransaction', 'fastagReport', 'allMatm', 'matmTransaction', 'matmReport'
            ]
        ];
        Http::post('https://janpay-webhooks.vercel.app/api/users', json_encode($data));

        if ($request['hasParent']) {
            DB::table('user_parent')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'parent_id' => $request['parent'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        $name = $user->name;
        $phone = $user->phone_number;
        DB::table('package_user')->insert([
            'user_id' => $user->id,
            'package_id' => $request['userPlan'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if (!$request['isActive']) {
            return response()->json(['message' => 'User created Successfully']);
        }
        $newmsg = "Dear $name , You have registered sucessfully, your ID'-$phone, Password'-$password, Mpin'-$mpin Don't Share anyone. From'-P24 Technology Pvt. Ltd";
        $sms = Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$newmsg", []);
        Mail::raw("Hello Your one time password is $password and MPIN is $mpin", function ($message) use ($to, $name) {
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
        return new UserResource(User::with('roles')->where('organization_id', auth()->user()->organization_id)->findOrFail($id));
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
            'dob' => 'required|date|before_or_equal:-13 years'
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
        $request->validate([
            'newNumber' => [Rule::unique('users', 'phone_number')->ignore(auth()->user()->phone_number)]
        ]);
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
        $org_id = auth()->user()->organization_id;
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

    public function userInfo(Request $request, string $role, $id = null)
    {
        $search = $request['search'];
        $org_id = auth()->user()->organization_id;
        
        if (is_null($id)) {
            $user = User::role($role)->with('packages:name')->where(['organization_id' => $org_id])->paginate(100);
            return $user;
        }

        if (!empty($search) || !is_null($search)) {
            $user = User::role($role)->with('packages:name')->where(['organization_id' => $org_id])->where('id', 'like', '%' . $search . '%')->orWhere('phone_number', 'like', '%' . $search . '%')->get();
            return $user;
        }

        $user = User::role($role)->with('packages:name')->where(['id' => $id, 'organization_id' => $org_id])->paginate(100);
        return $user;
    }

    public function userInfoPackage(Request $request, string $role, $id = null)
    {
        $search = $request['search'];
        $org_id = auth()->user()->organization_id;
        if (!empty($search) || !is_null($search)) {
            $user = User::role($role)->with('packages:name')->where(['organization_id' => $org_id])->where('id', 'like', '%' . $search . '%')->orWhere('phone_number', 'like', '%' . $search . '%')->get();
            return $user;
        }
        if (is_null($id)) {
            $user = User::role($role)->where(['organization_id' => $org_id])->get(['users.id', 'users.name', 'users.profile_pic']);
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

    public function childUser(string $role, $id = null)
    {
        $org_id = auth()->user()->organization_id;
        if (is_null($id)) {
            $user = DB::table('user_parent')
                ->join('users', 'users.id', '=', 'user_parent.user_id')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('package_user', 'package_user.user_id', '=', 'users.id')
                ->join('packages', 'packages.id', 'package_user.package_id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where(['user_parent.parent_id' => auth()->user()->id, 'roles.name' => $role])
                ->select('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.alternate_phone', 'users.line', 'users.line', 'users.city', 'users.state', 'users.pincode', 'users.wallet', 'users.minimum_balance', 'users.kyc', 'roles.name as role_name', 'users.aadhar_front', 'users.aadhar_back', 'users.pan_photo', 'users.profile_pic', 'packages.name as package_name', 'users.gender', 'users.dob', 'users.gst_number')
                ->get();
            return  $user;
        }

        $user = DB::table('user_parent')
            ->join('users', 'users.id', '=', 'user_parent.user_id')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('package_user', 'package_user.user_id', '=', 'users.id')
            ->join('packages', 'packages.id', 'package_user.package_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where(['user_parent.parent_id' => auth()->user()->id, 'users.id' => $id, 'roles.name' => $role])
            ->select('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.alternate_phone', 'users.line', 'users.line', 'users.city', 'users.state', 'users.pincode', 'users.wallet', 'users.minimum_balance', 'users.kyc', 'roles.name as role_name', 'users.aadhar_front', 'users.aadhar_back', 'users.pan_photo', 'users.profile_pic', 'packages.name as package_name', 'users.gender', 'users.dob', 'users.gst_number')
            ->get();
        return $user;
    }

    public function userReport($id = null)
    {
        if (!is_null($id)) {
            $bool = DB::table('user_parent')->where(['parent_id' => auth()->user()->id, 'user_id' => $id]);
            if ($bool->exists()) {
                $data = DB::table('transactions')
                    ->join('users', 'users.id', '=', 'transactions.trigered_by')
                    ->where('trigered_by', $id)
                    ->select('users.name', 'transactions.*', 'users.phone_number')
                    ->paginate(50);
            } else {
                return response("No transaction found.", 404);
            }
        } else {
            $data = DB::table('transactions')
                ->join('user_parent', 'user_parent.user_id', '=', 'transactions.trigered_by')
                ->join('users', 'users.id', '=', 'transactions.trigered_by')
                ->where('user_parent.parent_id', auth()->user()->id)
                ->select('users.name', 'transactions.*', 'users.phone_number')
                ->paginate(50);
        }

        return $data;
    }
}

// $user = User::role($role)->with(['children' => function ($query) use ($role) {
//     $query->select('user_id', 'parent_id', 'name')->role($role);
// }])->where(['id' => $id, 'organization_id' => $org_id])->get();
// return $user;