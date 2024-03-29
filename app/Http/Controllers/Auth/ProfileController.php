<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\v1\UserResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Eko\Agent\AgentManagementController;

class ProfileController extends AgentManagementController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function info()
    {
        return new UserResource(User::findOrFail(Auth::id())->makeVisible(['phone_number', 'dob', 'aadhaar', 'user_code', 'company_name', 'line', 'city', 'state', 'pincode', 'profile', 'kyc', 'onboard_fee', 'pan_photo', 'aadhar_front', 'aadhar_back']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // $data = collect($request->values);
        $request->validate([
            // 'values.firstName' => ['required', 'max:255'],
            // 'values.lastName' => ['required', 'string', 'max:255'],
            // 'values.dob' => ['required', 'date', 'before_or_equal:-13 years'],
            'values.aadhaar' => ['exists:users,aadhaar', 'digits:12', 'integer', Rule::unique('users', 'aadhaar')->ignore(auth()->user()->id)],
            // 'values.line' => ['required', 'string', 'max:255'],
            // 'values.city' => ['required', 'string', 'max:255'],
            'values.pincode' => ['required', 'digits:6', 'integer'],
            // 'values.state' => ['required', 'string', 'max:255'],
            // 'values.phone' => ['required', Rule::unique('users', 'phone_number')->ignore(auth()->user()->id)],
            'values.pan' => ['required', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}/', Rule::unique('users', 'pan_number')->ignore(auth()->user()->id)],
            // 'values.firmName' => ['max:255'],
            'values.aadhaarFront' => ['file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'values.aadhaarBack' => ['file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'values.panCard' => ['file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'values.deviceNumber' => ['required', 'string'],
            'values.modelName' => ['required', 'string'],
        ]);

        if ($request->hasFile('values.aadhaarFront')) {
            $aadhaar_front = $request->file('values.aadhaarFront')->store('aadhar_front');
        }
        if ($request->hasFile('values.aadhaarBack')) {
            $aadhaar_back = $request->file('values.aadhaarBack')->store('aadhar_back');
        }
        if ($request->hasFile('values.panCard')) {
            $pan_card = $request->file('values.panCard')->store('pan');
        }
        if ($request->hasFile('values.profilePic')) {
            $profile = $request->file('values.profilePic')->store('profile');
        }

        User::where('id', auth()->user()->id)->update([
            'aadhaar' => $request['values']['aadhaar'],
            'pincode' => $request['values']['pincode'],
            'gst_number' => $request['values']['gst'] ?? null,
            // 'phone_number' => $request['values']['phone'],
            'pan_number' => $request['values']['pan'],
            'company_name' => $request['values']['firmName'] ?? null,
            'firm_type' => $request['values']['companyType'] ?? null,
            'profile' => 1,
            'profile_pic' => $profile ?? auth()->user()->profile_pic,
            'aadhar_front' => $aadhaar_front ?? auth()->user()->aadhar_front,
            'aadhar_back' => $aadhaar_back ?? auth()->user()->aadhar_back,
            'pan_photo' => $pan_card ?? auth()->user()->pan_photo,
            'device_number' => $request['values']['deviceNumber'],
            'model_name' => $request['values']['modelName'],
        ]);

        $user = User::findOrFail(auth()->user()->id)->makeVisible(['organization_id', 'wallet']);
        $role = $user->getRoleNames();
        $role_details = json_decode(DB::table('roles')->where('name', $role[0])->get(['id', 'fee']), true);
        $id = json_decode(DB::table('packages')->where(['role_id' => $role_details[0]['id'], 'organization_id' => $user->organization_id, 'is_default' => 1])->get('id'), true);
        if (!empty($id)) {
            $attach_user = DB::table('package_user')->updateOrInsert(
                [
                    'user_id' => auth()->user()->id,
                    'package_id' => $id[0]['id'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
        return new UserResource(User::findOrFail(Auth::id()));
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

    public function newMpin(Request $request)
    {
        $request->validate([
            'old_mpin' => 'required',
            'new_mpin' => 'required|digits:4|confirmed'
        ]);

        $user =  User::where('id', auth()->user()->id)->first();

        if (!$user || !Hash::check($request['old_mpin'], $user->mpin)) {
            throw ValidationException::withMessages([
                'error' => ['You entered wrong MPIN'],
            ]);
        }

        User::where('id', auth()->user()->id)->update([
            'mpin' => Hash::make($request['new_mpin']),
            'credential_remarks' => $request['credential_remarks'] ?? null
        ]);

        return response('MPIN changed successfully', 200);
    }

    public function newPass(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|max:16|confirmed'
        ]);

        $user =  User::where('id', auth()->user()->id)->first();

        if (!$user || !Hash::check($request['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'error' => ['You entered wrong Password'],
            ]);
        }

        User::where('id', auth()->user()->id)->update([
            'password' => Hash::make($request['new_password']),
            'credential_remarks' => $request['credential_remarks'] ?? null
        ]);

        return response('Password changed successfully', 200);
    }

    public function wallet()
    {
        $wallet = DB::table('users')->where('id', auth()->user()->id)->get('wallet');
        return $wallet;
    }

    public function userServices()
    {
        $result = DB::table('users')
            ->join('service_user', 'users.id', '=', 'service_user.user_id')
            ->join('services', 'service_user.service_id', '=', 'services.id')
            ->select('services.type', 'services.service_name', 'services.image_url', 'services.price')->where('users.id', '=', auth()->user()->id)->where('service_user.pesa24_active', '=', 1)->get(['type', 'service_name', 'image_url', 'price']);
        return $result;
    }

    public function addBank(Request $request)
    {
        $request->validate([
            'accountNumber' => 'required',
            'ifsc' => 'required',
            'paysprintBankCode' => 'required',
            'passbook' => 'required'
        ]);
        $passbook = $request->file('passbook')->store('passbook');
        DB::table('users')->where('id', auth()->user()->id)->update([
            'account_number' => $request['accountNumber'],
            'ifsc' => $request['ifsc'],
            'passbook' => $passbook,
            'paysprint_bank_code' => $request['paysprintBankCode'],
            'eko_bank_code' => $request['ekoBankCode'],
            'updated_at' => now()
        ]);

        return response(['message' => 'Bank details sent for review!']);
    }

    public function bank()
    {
        $data = DB::table('users')->where('id', auth()->user()->id)->get(['paysprint_bank_code', 'ifsc', 'account_number', 'bank_account_remarks', 'is_verified']);

        return $data;
    }

    public function adminUser($id)
    {
        $user = User::with(['roles', 'permissions'])->where('id', $id)->orWhere('phone_number', $id)->get();
        if (sizeof($user) == 0) {
            return response("User not found.", 404);
        }

        return new UserResource($user[0]);
    }

    public function findUser($id)
    {
        $user = User::where(['id' => $id, 'organization_id' => auth()->user()->organization_id])->orWhere('phone_number', $id)->get();
        if (sizeof($user) == 0) {
            return response("User not found.", 404);
        }
        return new UserResource($user[0]);
    }

    public function adminUpdateProfile(Request $request)
    {
        $request->validate([
            'values.firstName' => ['required', 'max:255'],
            'values.lastName' => ['required', 'string', 'max:255'],
            // 'values.dob' => ['required', 'date', 'before_or_equal:-13 years'],
            'values.aadhaar' => [Rule::unique('users', 'aadhaar')->ignore(auth()->user()->id)],
            // 'values.line' => ['required', 'string', 'max:255'],
            // 'values.city' => ['required', 'string', 'max:255'],
            // 'values.pincode' => ['required', 'digits:6', 'integer'],
            // 'values.state' => ['required', 'string', 'max:255'],
            'values.phone' => ['required', Rule::unique('users', 'phone_number')->ignore(auth()->user()->id)],
            'values.pan' => [Rule::unique('users', 'pan_number')->ignore(auth()->user()->id)],
            'values.firmName' => ['max:255'],
            // 'values.aadhaarFront' => ['file', 'mimes:jpg,jpeg,png', 'max:2048'],
            // 'values.aadhaarBack' => ['file', 'mimes:jpg,jpeg,png', 'max:2048'],
            // 'values.panCard' => ['file', 'mimes:jpg,jpeg,png', 'max:2048'],
            // 'values.deviceNumber' => ['required', 'string'],
            // 'values.modelName' => ['required', 'string'],
        ]);

        if ($request->hasFile('values.aadhaarFront')) {
            $aadhaar_front = $request->file('values.aadhaarFront')->store('aadhar_front');
        } elseif ($request->hasFile('values.aadhaarBack')) {
            $aadhaar_back = $request->file('values.aadhaarBack')->store('aadhar_back');
        } elseif ($request->hasFile('values.panCard')) {
            $pan_card = $request->file('values.panCard')->store('pan');
        } elseif ($request->hasFile('values.profilePic')) {
            $profile = $request->file('values.profilePic')->store('profile');
        }

        User::where('id', auth()->user()->id)->update([
            'first_name' => $request['values']['firstName'],
            'last_name' => $request['values']['lastName'],
            'dob' => $request['values']['dob'] ?? auth()->user()->dob,
            'aadhaar' => $request['values']['aadhaar'] ?? auth()->user()->aadhaar,
            'line' => $request['values']['line'] ?? auth()->user()->line,
            'city' => $request['values']['city'] ?? auth()->user()->city,
            'pincode' => $request['values']['pincode'] ?? auth()->user()->pincode,
            'gst_number' => $request['values']['gst'] ?? auth()->user()->gst_number,
            'state' => $request['values']['state'] ?? auth()->user()->state,
            'phone_number' => $request['values']['phone'] ?? auth()->user()->phone_number,
            'pan_number' => $request['values']['pan'] ?? auth()->user()->pan_number,
            'company_name' => $request['values']['firmName'] ?? auth()->user()->company_name,
            'firm_type' => $request['values']['companyType'] ?? auth()->user()->firm_type,
            'profile' => 1,
            'profile_pic' => $profile ?? auth()->user()->profile_pic,
            'aadhar_front' => $aadhaar_front ?? auth()->user()->aadhar_front,
            'aadhar_back' => $aadhaar_back ?? auth()->user()->aadhar_back,
            'pan_photo' => $pan_card ?? auth()->user()->pan_photo,
            'device_number' => $request['values']['deviceNumber'],
            'model_name' => $request['values']['modelName'],
        ]);

        return new UserResource(User::findOrFail(Auth::id()));
    }
}
