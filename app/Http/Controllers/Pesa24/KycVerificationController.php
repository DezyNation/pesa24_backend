<?php

namespace App\Http\Controllers\Pesa24;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class KycVerificationController extends Controller
{

    /*--------------------------------Aadhar Verification--------------------------------*/
    public function sendOtpAadhaar(Request $request)
    {
        session()->forget('otp_ref_id');
        $data = [
            'aadhaar_no' => $request['aadhaar_no']
        ];
        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('API_CLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/api/v1/aadhaar_v2/send_otp', $data);
        if ($response->json($key = 'status') == 'success') {
            $otp_ref_id = $response->json($key = 'response.ref_id');
            session()->put('otp_ref_id', $otp_ref_id);
            return response()->json(['message' => $response->json($key = 'response.message')]);
        }
        return response($response->json($key = 'response'), 419);
    }

    public function verifyOtpAadhaar(Request $request)
    {
        $data = [
            'ref_id' => session()->get('otp_ref_id'),
            'otp' => $request['otp']
        ];
        // return $data['ref_id'];
        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('API_CLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/api/v1/aadhaar_v2/submit_otp', $data);
        
        if ($response->json($key = 'code') == 200) {

            DB::table('k_y_c_verifications')->updateOrInsert(
                ['user_id' => auth()->user()->id],
                ['aadhar' => 1]
            );
            session()->forget('otp_ref_id');
            return response()->json(['message' => "OTP Verified"]);
        } else {
            session()->forget('otp_ref_id');
            return response($response->json($key = 'response'), 419);
        }
    }

    /*--------------------------------Pan Verification--------------------------------*/

    public function panVerification(Request $request)
    {
        $data = [
            'pan_no' => $request['pan_no'],
        ];

        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('API_CLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/api/v1/verify_pan', $data);

        if ($response->json($key = 'status') == 'success') {
            if ($response->json($key = 'response.registered_name') == strtoupper(auth()->user()->name)) {
                DB::table('k_y_c_verifications')->updateOrInsert(
                    ['user_id' => auth()->user()->id],
                    ['pan' => 1]
                );
                return response()->json(['message' => 'PAN Card Verified']);
            } else {
                return response($response->json($key = 'response'), 419);
            }
        } else {
            return response($response->json($key = 'response'), 419);
        }
    }

    public function onboardFee()
    {
        $user = User::findOrFail(auth()->user()->id)->makeVisible(['organization_id', 'wallet']);
        $role = $user->getRoleNames();
        $role_details = json_decode(DB::table('roles')->where('name', $role[0])->get(['id', 'fee']), true);
        $id = json_decode(DB::table('packages')->where(['role_id' => $role_details[0]['id'], 'organization_id' => $user->organization_id, 'is_default' => 1])->get('id'), true);
        $opening_balance = $user->wallet;
        $final_amount = $user->wallet - $role_details[0]['fee'];

        $attach_user = DB::table('package_user')->insert([
            'user_id' => auth()->user()->id,
            'package_id' => $id[0]['id'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('users')->where('id', auth()->user()->id)->update([
           'wallet' => $final_amount,
           'onboard_fee' => 1,
           'updated_at' => now()
        ]);

        $transaction_id = "ONB".strtoupper(Str::random(5));

        $this->transaction($role_details[0]['fee'], 'Onboard and Package fee', 'onboarding', auth()->user()->id, $opening_balance, $transaction_id, $final_amount, 0);

        return response()->json(['message' => 'User onboarded successfully.']);
    }
}
