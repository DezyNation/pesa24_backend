<?php

namespace App\Http\Controllers\Razorpay;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Razorpay\FundAccountController;

class ContactController extends FundAccountController
{
    public function createContact(Request $request)
    {
        if ($request['amount'] > 50) {
            $user = User::findOrFail(auth()->user()->id);
            $otp_generated_at = Carbon::parse($user->otp_generated_at);
            $current_time = Carbon::parse(now());
            $difference = $current_time->diffInRealMinutes($otp_generated_at);
            if ($difference > 1) {
                return response("OTP expired!", 406);
            }
            if (!Hash::check($request['otp'], $user->otp)) {
                return response("OTP is wrong!", 406);
            }
        }

        $data = [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'contact' => auth()->user()->phone_number,
            'type' => 'employee',
            'reference_id' =>  "DEV".uniqid(),
        ];

        return response(['metadata' => $data]);
        $key = env('RAZORPAY_KEY');
        $secret = env('RAZORPAY_SECRET');
        $response = Http::withBasicAuth($key, $secret)->withHeaders([
            'Content-type' => 'application/json'
        ])->post('https://api.razorpay.com/v1/contacts', $data);
        if (array_key_exists('id', $response->json())) {
            DB::table('users')->where('id', auth()->user()->id)->update(['rzp_contact_id' => $response['id'], 'updated_at' => now()]);
        }
        return $this->createFundAcc($request);
    }
}
