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
            // 'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // 'role' => ['required'],
        ]);

        $code = Session::put('organization_code', $request['organization_code']);
        $org_id = DB::table('organizations')->where('code', $request['organization_code'])->pluck('id');
        return $org_id;

        $email = $request['email'];
        $phone = $request['phone'];
        $username = $request['first_name'];
        $mpin = rand(1001, 9999);
        $name = $request['first_name'] . " " . $request['last_name'];
        $password = Str::random(8);
        $user = User::create([
            'name' => $request['first_name'] . " " . $request['last_name'],
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'email' => $request['email'],
            'phone_number' => $request['phone'],
            'password' => Hash::make($password),
            'mpin' => Hash::make($mpin),
            'organization_id' => $org_id[0]
        ])->assignRole('retailer');
        Session::forget('organization_code');
        // $this->email($email, $username, $password);
        // return ['Login' => true];
        event(new Registered($user));

        Mail::raw("Hello Your one time password is $password and Mpin'-$mpin", function ($message) use ($email, $name) {
            $message->from('info@pesa24.co.in', 'John Doe');
            $message->to($email, $name);
            $message->subject('Welcome to Pesa24');
            $message->priority(1);
        });

        $newmsg = "Dear $username , Welcome to Rpay. You have registered sucessfully, your ID'-$phone, Password'-$password, Mpin'-$mpin Now you can login https://rpay.live/. From'-P24 Technology Pvt. Ltd";
        // Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$newmsg", []);
        // Auth::login($user);

        return response()->noContent();
    }

    // public function email($to, $name, $password)
    // {
    //         Mail::raw("Hello Your one time password is $password", function ($message, $to, $name) {
    //         $message->from('info@pesa24.co.in', 'John Doe');
    //         $message->to($to, $name);
    //         $message->subject('Welcome to Pesa24');
    //         $message->priority(1);
    //     });

    //     return "Mail sent";
    // }
}
