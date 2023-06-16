<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            // 'name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'phone' => ['required', 'digits:10', Rule::unique('users', 'phone_number')]
            // 'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // 'role' => ['required'],
        ]);

        $org_id = DB::table('organizations')->where('code', $request['organization_code'])->pluck('id');
        $email = $request['email'];
        $phone = $request['phone'];
        $username = $request['first_name'];
        $mpin = rand(1001, 9999);
        $name = $request['first_name'] . " " . $request['last_name'];
        $password = Str::random(8);
        $user = User::create([
            'name' => $request['first_name'] . " " . $request['middle_name'] . " " . $request['last_name'],
            'first_name' => $request['first_name'],
            'middle_name' => $request['middle_name'],
            'last_name' => $request['last_name'],
            'email' => $request['email'],
            'phone_number' => $request['phone'],
            'password' => Hash::make($password),
            'mpin' => Hash::make($mpin),
            'organization_id' => $org_id[0]
        ])->assignRole('retailer');
        event(new Registered($user));
        $data = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'organisation_code' => $request['organization_code'],
            [
                ''
            ]
        ];
        Http::post('https://janpay-webhooks.vercel.app/api/users', $data);
        Mail::raw("Hello Your one time password is $password and Mpin'-$mpin", function ($message) use ($email, $name) {
            $message->from('info@pesa24.co.in', 'John Doe');
            $message->to($email, $name);
            $message->subject('Welcome to Pesa24');
            $message->priority(1);
        });

        $newmsg = "Dear $username , Welcome to Rpay. You have registered sucessfully, your ID'-$phone, Password'-$password, Mpin'-$mpin Now you can login https://rpay.live/. From'-P24 Technology Pvt. Ltd";
        Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$newmsg", []);

        return response()->noContent();
    }

    public function registerAdmin(Request $request)
    {
        $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'userEmail' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'userPhone' => ['required', 'digits:10', Rule::unique('users', 'phone_number')],
            'alternatePhone' => ['required', 'digits:10', Rule::unique('users', 'alternate_phone')],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:255'],
            'firmName' => ['required', 'string', 'max:255'],
            'companyType' => ['required', 'string', 'max:255'],
            'aadhaarNum' => ['required', 'digits:12', Rule::unique('users', 'aadhaar')],
            'panNum' => ['required', 'max:10', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}/', Rule::unique('users', 'pan_number')],
            'gst' => ['string'],
            'isActive' => ['required', 'boolean'],
            'capAmount' => ['required', 'integer'],
            'line' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'pincode' => ['required', 'integer'],
        ]);

        $aadhaar_front = $request->file('aadhaarFront')->store('aadhar_front');
        $aadhaar_back = $request->file('aadhaarBack')->store('aadhar_back');
        $pan_card = $request->file('pan')->store('pan');

        $email = $request['userEmail'];
        $name =  $request['firstName'] . " " . $request['middleName'] . " " . $request['lastName'];
        $mpin = rand(1001, 9999);
        $password = Str::random(8);

        $user = User::create([
            'first_name' => $request['firstName'],
            'last_name' => $request['lastName'],
            'name' => $name,
            'email' => $email,
            'phone_number' => $request['userPhone'],
            'middle_name' => $request['middleName'],
            'alternate_phone' => $request['alternatePhone'],
            'password' => Hash::make($password),
            'mpin' => Hash::make($mpin),
            'gender' => $request['gender'],
            'dob' => $request['dob'],
            'aadhaar' => $request['aadhaarNumber'],
            'minimum_balance' => $request['capAmount'],
            'gst_number' => $request['gst'],
            'is_active' => $request['isActive'],
            'line' => $request['line'],
            'city' => $request['city'],
            'pincode' => $request['pincode'],
            'state' => $request['state'],
            'pan_number' => $request['pan'],
            'company_name' => $request['companyName'],
            'firm_type' => $request['companyName'],
            'profile' => 1,
            'aadhaar_front' => $aadhaar_front,
            'aadhaar_back' => $aadhaar_back,
            'pan_photo' => $pan_card,
            'organization_id' => auth()->user()->organization_id
        ])->assignRole('retailer');


        event(new Registered($user));
        $data = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'organisation_code' => $request['organization_code']
        ];
        Http::post('https://janpay-webhooks.vercel.app/api/users', $data);
        Mail::raw("Hello Your one time password is $password and Mpin'-$mpin", function ($message) use ($email, $name) {
            $message->from('info@pesa24.co.in', 'RPay');
            $message->to($email, $name);
            $message->subject('Welcome to Pesa24');
            $message->priority(1);
        });

        return response()->noContent();
    }

    public function adminUpdate(Request $request)
    {

        $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'userEmail' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request['userId'])],
            'userPhone' => ['required', 'digits:10', Rule::unique('users', 'phone_number')->ignore($request['userId'])],
            // 'alternatePhone' => ['integer'],
            'dob' => ['required', 'date'],
            // 'gender' => ['required', 'string', 'max:255'],
            // 'firmName' => ['string', 'max:255'],
            // 'companyType' => ['string', 'max:255'],
            'aadhaarNum' => ['required', 'digits:12', Rule::unique('users', 'aadhaar')->ignore($request['userId'])],
            'panNum' => ['required', 'max:10', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}/', Rule::unique('users', 'pan_number')->ignore($request['userId'])],
            // 'gst' => ['string'],
            'isActive' => ['required', 'boolean'],
            'capAmount' => ['required', 'integer'],
            'line' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'pincode' => ['required', 'integer'],
        ]);

        $user = User::where('organization_id', auth()->user()->organization_id)->findOrFail($request['userId']);
        if ($request->hasFile('aadhaarFront')) {
            $aadhaar_front = $request->file('aadhaarFront')->store('aadhar_front');
        } else {
            $aadhaar_front = $user->aadhar_front;
        }

        if ($request->hasFile('aadhaarBack')) {
            $aadhaar_back = $request->file('aadhaarBack')->store('aadhar_back');
        } else {
            $aadhaar_back = $user->aadhar_back;
        }

        if ($request->hasFile('pan')) {
            $pan_card = $request->file('pan')->store('pan');
        } else {
            $pan_card = $user->pan_photo;
        }

        if ($request->hasFile('profilePic')) {
            $profile = $request->file('profilePic')->store('profile_pic');
        } else {
            $profile = $user->profile_pic;
        }

        $email = $request['userEmail'];
        $name =  $request['firstName'] . " " . $request['lastName'];

        $user = User::where('id', $request['userId'])->update([
            'first_name' => $request['firstName'],
            'last_name' => $request['lastName'],
            'name' => $name,
            'email' => $email,
            'phone_number' => $request['userPhone'],
            'alternate_phone' => $request['alternatePhone'],
            'gender' => $request['gender'],
            'dob' => $request['dob'],
            'aadhaar' => $request['aadhaarNum'],
            'minimum_balance' => $request['capAmount'],
            'gst_number' => $request['gst'],
            'is_active' => $request['isActive'],
            'line' => $request['line'],
            'city' => $request['city'],
            'pincode' => $request['pincode'],
            'state' => $request['state'],
            'pan_number' => $request['panNum'],
            'company_name' => $request['firmName'],
            'firm_type' => $request['companyType'],
            'profile' => 1,
            'profile_pic' => $profile,
            'aadhar_front' => $aadhaar_front,
            'aadhar_back' => $aadhaar_back,
            'pan_photo' => $pan_card,
        ]);

        return response()->noContent();
    }
}
