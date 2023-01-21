<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KycVerificationController extends Controller
{
    public function sendOtpAadhaar(Request $request)
    {
        $data = [
            'aadhaar_no' => $request['aadhaar_no'],
        ];
        $response = Http::acceptJson()->withHeaders([
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/send_otp', $data);

        return $response;
    }

    public function verifyOtpAadhaar(Request $request)
    {
        $data = [
            'ref_id' => $request['ref_id'],
            'otp' => $request['otp']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/submit_otp', $data);

        return $response;
    }

    public function panVerification()
    {
        $data = [
            'pan_no' => $request['pan_no'] ?? 'MFUPK1391B',
        ];

        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => '4610b9780288d5479ce99b799a4c686b',
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/uat/v1/aadhaar_v2/submit_otp', $data);

        return $response;
    }
}
