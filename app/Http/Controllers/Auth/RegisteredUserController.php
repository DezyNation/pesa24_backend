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
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Http;

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

        $email = $request['email'];
        $phone = $request['phone'];
        $user = $request['first_name'];
        $mpin = rand(1001, 9999);
        $password = Str::random(8);
        $user = User::create([
            'name' => $request['first_name']." ".$request['last_name'],
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => Hash::make($password),
            'mpin' => Hash::make($mpin)
        ])->assignRole('retailer');
        // return ['Login' => true];
        event(new Registered($user));
        Mail::raw("Hello Your one time password is $password", function ($message) {
            $message->from('john@johndoe.com', 'John Doe');
            $message->to('john@johndoe.com', 'John Doe');
            $message->subject('Welcome to Pesa24');
            $message->priority(1);
        });
        $text = "Dear $user, Welcome to Rpay. You have registered sucessfully, your ID-$phone, Password-$password, Mpin-$mpin Now you can login https://rpay.live/. From-P24 Technology Pvt. Ltd";
        Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
        // Auth::login($user);

        return response()->noContent();
    }
}
