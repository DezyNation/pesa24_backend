<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class KycVerificationController extends Controller
{

    /*--------------------------------Aadhar Verification--------------------------------*/
    public function sendOtpAadhaar(Request $request)
    {
        $data = [
            'aadhaar_no' => $request['aadhaar_no']
        ];
        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('API_CLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/send_otp', $data);
        if ($response->json($key = 'status') == 'success') {
            $otp_ref_id = $response->json($key = 'response.ref_id');
            session()->put('otp_ref_id', $otp_ref_id);
            return response()->json(['message' => 'OTP sent']);
        }
        return response()->json(['message' => $response->json($key = 'response')]);
    }

    public function verifyOtpAadhaar(Request $request)
    {
        $data = [
            'ref_id' => session()->get('otp_ref_id'),
            'otp' => $request['otp']
        ];

        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('API_CLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/submit_otp', $data);

        if ($response->json($key = 'status') == 'success') {
            if (auth()->user()->dob == $response->json($key = 'response.dob')) {
                DB::table('kyc')->updateOrInsert(
                    ['user_id' => auth()->user()->id],
                    ['aadhar' => 1]
                );
            } else {
                return response("Could not verify your aadhar", 419);
            }
        }
        session()->forget('otp_ref_id');
        return response()->json(['message' => 'Aadhar verified']);
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
        ])->post('https://api.apiclub.in/uat/v1/verify_pan', $data);

        if ($response->json($key = 'status') == 'success') {
            if ($response->json($key = 'response.registered_name') == auth()->user()->name) {
                DB::table('kyc')->updateOrInsert(
                    ['user_id' => auth()->user()->id],
                    ['pan' => 1]
                );
            } else {
                return response("Could not verify your aadhar", 419);
            }
        }

        return response()->json(['message' => 'PAN Card Verified']);
    }
}
