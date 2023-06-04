<?php

namespace App\Http\Controllers\Pesa24;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\v1\UserResource;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class KycVerificationController extends Controller
{

    protected $otp_ref_id;

    function __construct()
    {
    }
    /*--------------------------------Aadhar Verification--------------------------------*/
    public function sendOtpAadhaar(Request $request)
    {
        $user_id = auth()->user()->id;
        $request->validate([
            'aadhaar_no' => ['required', Rule::unique('users', 'aadhaar')->ignore($user_id)]
        ]);
        $data = [
            'aadhaar_no' => $request['aadhaar_no']
        ];
        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('APICLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/api/v1/aadhaar_v2/send_otp', $data);
        if ($response->json($key = 'status') == 'success') {
            DB::table('users')->where('id', $user_id)->update(['aadhaar' => $request['aadhaar_no'], 'updated_at' => now()]);
            return response()->json(['message' => $response->json($key = 'response.ref_id')]);
        }
        return response($response->json($key = 'response'), 419);
    }

    public function verifyOtpAadhaar(Request $request)
    {

        $user_id = auth()->user()->id;
        $data = [
            'ref_id' => $request['refId'],
            'otp' => $request['otp']
        ];
        // return $data['ref_id'];
        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('APICLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/api/v1/aadhaar_v2/submit_otp', $data);

        if ($response->json($key = 'code') == 200) {

            DB::table('k_y_c_verifications')->updateOrInsert(
                ['user_id' => $user_id],
                ['aadhar' => 1]
            );

            DB::table('users')->where('id', $user_id)->update([
                'name' => $response['response']['name'],
                'dob' => date("Y-m-d", strtotime(str_replace('/', '-', $response['response']['dob']))),
                'gender' => $response['response']['gender'],
                'line' => implode(", ", [$response['response']['address']['house'] ?? "", $response['response']['address']['street'] ?? "", $response['response']['address']['vtc'] ?? "", $response['response']['address']['subdist'] ?? "", $response['response']['address']['loc'] ?? "", $response['response']['address']['po'] ?? "", $response['response']['address']['subdist'] ?? "", $response['response']['address']['dist'] ?? ""]),
                'city' => $response['response']['address']['loc'] ?? "",
                'state' => $response['response']['address']['state'] ?? ""
            ]);

            return response()->json(['message' => "OTP Verified"]);
        } else {
            DB::table('users')->where('id', $user_id)->update(['aadhaar' => null]);
            return response($response->json($key = 'response'), 419);
        }
    }

    /*--------------------------------Pan Verification--------------------------------*/

    public function panVerification(Request $request)
    {
        $user_id = auth()->user()->id;
        $request->validate([
            'pan_no' => ['required', Rule::unique('users', 'pan_number')->ignore($user_id)]
        ]);

        $data = [
            'pan_no' => $request['pan_no'],
        ];

        $response = Http::acceptJson()->withHeaders([
            'API-KEY' => env('APICLUB_KEY'),
            'Referer' => 'docs.apiclub.in',
            'content-type' => 'application/json'
        ])->post('https://api.apiclub.in/api/v1/verify_pan', $data);

        if ($response->json($key = 'status') == 'success') {
            if ($response->json($key = 'response.registered_name') == strtoupper(auth()->user()->name)) {
                DB::table('k_y_c_verifications')->updateOrInsert(
                    ['user_id' => auth()->user()->id],
                    ['pan' => 1]
                );
                DB::table('users')->where('id', $user_id)->update(['pan_number' => $request['pan_no'], 'updated_at' => now()]);
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
        $paysprint = $this->onboard();
        return $paysprint;
    }

    public function token()
    {
        $key = env('JWT_KEY');
        $payload = [
            'timestamp' => time(),
            'partnerId' => env('PAYSPRINT_PARTNERID'),
            'reqid' => abs(crc32(uniqid()))
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }


    public function onboard()
    {
        $token = $this->token();

        $data = [
            'merchantcode' => "PESA24API" . auth()->user()->id,
            'mobile' => auth()->user()->phone_number,
            'is_new' => 0,
            'email' => auth()->user()->email,
            'firm' => auth()->user()->company_name ?? 'PAYMONEY',
            'callback' => 'https://api.pesa24.in/api/onboard-callback-paysprint',
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'Content-Type: application/json'
        ])->post('https://api.paysprint.in/api/v1/service/onboard/onboard/getonboardurl', $data);
        Log::channel('response')->info($response);
        DB::table('users')->where('id', auth()->user()->id)->update([
            'paysprint_merchant' => $data['merchantcode'],
            'updated_at' => now()
        ]);
        if ($response['status'] == false) {
            return response($response['message'], 400);
        }
        return $response;
    }

    public function userOnboard()
    {

        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $residence_address['line'] = strval(auth()->user()->line);
        $residence_address['city'] = strval(auth()->user()->city);
        $residence_address['state'] = strval(auth()->user()->state);
        $residence_address['pincode'] = strval(auth()->user()->pincode);

        $data = [
            'initiator_id' => 9962981729,
            'pan_number' => auth()->user()->pan_number,
            'mobile' => auth()->user()->phone_number,
            'first_name' => auth()->user()->first_name,
            'middle_name' => auth()->user()->middle_name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'residence_address' => json_encode($residence_address),
            'dob' => auth()->user()->dob,
            'shop_name' => auth()->user()->company_name ?? 'PAYMONEY',
        ];

        Log::channel('response')->info('request', $data);

        $response = Http::asForm()->withHeaders([
            'developer_key' => '28fbc74a742123e19bcda26d05453a18',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://api.eko.in:25002/ekoicici/v1/user/onboard', $data);
        Log::channel('response')->info($response);


        if (array_key_exists('user_code', $response->json($key = 'data'))) {
            DB::table('users')->where('id', auth()->user()->id)->update([
                'user_code' => $response['data']['user_code']
            ]);

            return response("Onboard Success.");
        }
        return response("Onboard fail: {$response['message']}", 502);
    }

    public function sendEkoOtp()
    {
        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => 9758105858,
            'mobile' => auth()->user()->phone_number ?? 9971412064,
        ];

        $response = Http::asForm()->withHeaders([
            'cache-control' => 'no-cache',
            'developer_key' => '28fbc74a742123e19bcda26d05453a18',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://api.eko.in:25002/ekoicici/v1/user/request/otp', $data);

        return response($response);
    }

    public function verifyMobile(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);
        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'initiator_id' => 9758105858,
            'mobile' => auth()->user()->phone_number,
            'otp' => $request['otp'],
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key' => '28fbc74a742123e19bcda26d05453a18',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->post('https://api.eko.in:25002/ekoicici/v2/user/verify', $data);

        Log::channel('response')->info($response);

        if (isset($response['status'])) {
            if ($response['status'] == 0) {
                $onboard = $this->userOnboard();
                return response($onboard);
            } else {
                return response($response['message']);
            }
        } else {
            return response($response);
        }
    }
}

            // public function panVerifyEko()
            // {
                //     $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
                //     $encodedKey = base64_encode($key);
                //     $secret_key_timestamp = round(microtime(true) * 1000);
                //     $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
                //     $secret_key = base64_encode($signature);

                //     $data = [
                    //         'pan_number' => 'MFUPK1391B',
                    //         'purpose' => 1,
                    //         'initiator_id' => 9758105858,
                    //         'purpose_desc' => 'onboard'
                    //     ];

                    //     Log::channel('response')->info('request', $data);

                    //     $response = Http::asForm()->withHeaders([
                        //         'developer_key' => '28fbc74a742123e19bcda26d05453a18',
                        //         'secret-key-timestamp' => $secret_key_timestamp,
                        //         'secret-key' => $secret_key,
                        //     ])->post('https://api.eko.in:25002/ekoicici/v2/pan/verify', $data);

                        //     Log::channel('response')->info($response);
                        //     return $response;
                        // }
                        // public function otpRequest(Request $request)
                        // {
                        //     $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
                        //     $encodedKey = base64_encode($key);
                        //     $secret_key_timestamp = round(microtime(true) * 1000);
                        //     $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
                        //     $secret_key = base64_encode($signature);

                        //     $data = [
                        //         'initiator_id' => 9758105858,
                        //         'user_code' => auth()->user()->code,
                        //         'customer_id' => auth()->user()->phone_number,
                        //         'aadhar' => auth()->user()->aadhaar,
                        //         'latlong' => $request['latlong']
                        //     ];

                        //     $response = Http::asForm()->withHeaders([
                        //         'developer_key' => '28fbc74a742123e19bcda26d05453a18',
                        //         'secret-key-timestamp' => $secret_key_timestamp,
                        //         'secret-key' => $secret_key,
                        //         ])->post('https://staging.eko.in:25004/ekoapi/v2/aeps/otp');
                        //     }