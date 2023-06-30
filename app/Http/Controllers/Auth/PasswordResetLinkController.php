<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'mpin' => ['required', 'digits:4']
        ]);

        // // We will send the password reset link to this user. Once we have attempted
        // // to send the link, we will examine the response then see the message we
        // // need to show to the user. Finally, we'll send out a proper response.
        // $status = Password::sendResetLink(
        //     $request->only('email')
        // );

        // if ($status != Password::RESET_LINK_SENT) {
        //     throw ValidationException::withMessages([
        //         'email' => [__($status)],
        //     ]);
        // }
        $user = User::where('email', $request['email'])->first();
        $password = Str::random(8);
        if (!$user || !Hash::check($request['mpin'], $user->mpin)) {
            throw ValidationException::withMessages([
                'error' => 'Email and MPIN did not match'
            ]);
        }
        User::where('email', $request['email'])->update([
            'password' => Hash::make($password)
        ]);
        Mail::raw("Dear User, Your new password for Login to Pesa24 is $password", function ($message) use ($request) {
            $message->from('info@pesa24.co.in', 'JANPAY');
            $message->to($request['email'], 'User');
            $message->subject('Password reset');
            $message->priority(1);
        });


        return response()->json(['status' => 'Check your email for password']);
    }

    public function adminSendCreds(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            // 'remarks' => 'required'
        ]);
        $email = $request['email'];
        $mpin = rand(1001, 9999);
        $password = Str::random(8);
        $user = User::where('email', $email);
        $user->update([
            'password' => Hash::make($password),
            'mpin' => Hash::make($mpin)
            // 'credential_remarks' => $request['remarks']
        ]);

        $new_user = User::where('email', $email)->get();
        // $phone = $user->get();
        $phone = $new_user[0]->phone_number;
        $name = $new_user[0]->name;
        // SMS api

        $newmsg = "Dear $name , You have registered sucessfully, your ID'-$phone, Password'-$password, Mpin'-$mpin Don't Share anyone. From'-P24 Technology Pvt. Ltd";
        $sms = Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$newmsg", []);
        // Log::channel('response')->info('sms-creds', $sms->json());
        Mail::raw("Dear User, Your new password for Login to Janpay is $password and MPIN is $mpin", function ($message) use ($request) {
            $message->from('info@pesa24.co.in', 'Janpay');
            $message->to($request['email'], $request['name']);
            $message->subject('Password reset');
            $message->priority(1);
        });

        return true;
    }
}
