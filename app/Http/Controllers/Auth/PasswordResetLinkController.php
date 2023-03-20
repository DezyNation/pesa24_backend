<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
            Mail::raw("Dear User, Your new password for Login to Pesa24 is $password", function($message) use ($request){
                $message->from('info@pesa24.co.in', 'Pesa24');
                $message->to($request['email'], 'User');
                $message->subject('Password reset');
                $message->priority(1);
            });


        return response()->json(['status' => 'Check your email for password']);
    }
}
