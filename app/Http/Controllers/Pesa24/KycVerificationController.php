<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
}
