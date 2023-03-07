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
            'aadhaar_no' => $request['aadhaar_no'] ?? 715547838073,
        ];
        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => '4610b9780288d5479ce99b799a4c686b',
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/send_otp', $data);

        session()->put('otp_ref_id', $response['response']['ref_id']);

        return $response['response']['message'];
    }

    public function verifyOtpAadhaar(Request $request)
    {
        $data = [
            'ref_id' => session()->get('otp_ref_id'),
            'otp' => $request['otp']
        ];

        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => '4610b9780288d5479ce99b799a4c686b',
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/submit_otp', $data);

        if (auth()->user()->dob == $response['response']['dob']) {
            DB::table('kyc')->updateOrInsert(
                ['user_id' => auth()->user()->id],
                ['aadhar' => 1]
            );
        } else {
            return response("Could not verify your aadhar", 419);
        }

        session()->forget('otp_ref_id');
        return $response;
    }

    /*--------------------------------Pan Verification--------------------------------*/

    public function panVerification(Request $request)
    {
        $data = [
            'pan_no' => $request['pan_no'],
        ];

        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => '4610b9780288d5479ce99b799a4c686b',
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/submit_otp', $data);

        if ($response['response']['registered_name'] == auth()->user()->name) {
            DB::table('kyc')->updateOrInsert(
                ['user_id' => auth()->user()->id],
                ['pan' => 1]
            );
        } else {
            return response("Could not verify your aadhar", 419);
        }

        return $response;
    }
}
