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

class KycVerificationController extends Controller
{

    protected $otp_ref_id;

    function __construct()
    {
    }
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
        ])->post('https://api.apiclub.in/api/v1/aadhaar_v2/send_otp', $data);
        if ($response->json($key = 'status') == 'success') {
            return response()->json(['message' => $response->json($key = 'response.ref_id')]);
        }
        return response($response->json($key = 'response'), 419);
    }

    public function verifyOtpAadhaar(Request $request)
    {
        $data = [
            'ref_id' => $request['refId'],
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

        $eko = $this->userOnboard();
        $paysprint = $this->onboard();
        if (!$eko['original']['message']) {
            return response("Could not implement", 501);
        }

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

        $transaction_id = "ONB" . strtoupper(Str::random(5));

        $this->transaction($role_details[0]['fee'], 'Onboard and Package fee', 'onboarding', auth()->user()->id, $opening_balance, $transaction_id, $final_amount, 0);

        return redirect($paysprint->json($key = 'redirecturl'));
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

    public function userOnboard()
    {

        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $residence_address['line'] = strval(auth()->user()->line);
        $residence_address['city'] = strval(auth()->user()->city);
        $residence_address['state'] = strval('Haryana');
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
            'shop_name' => auth()->user()->company_name
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/onboard', $data);

        Log::channel('response')->info($response);

        if (collect($response->json($key = 'data'))->has('user_code')) {
            DB::table('users')->where('id', auth()->user()->id)->update([
                'user_code' => $response->json($key = 'data')['user_code']
            ]);

            return json_decode(json_encode(response(['message' => 1], 200), true), true);
        }
        return json_decode(json_encode(response(['message' => 0], 400), true), true);
    }

    public function onboard()
    {
        $token = $this->token();

        $data = [
            'merchantcode' => auth()->user()->user_code,
            'mobile' => auth()->user()->phone_number,
            'is_new' => 0,
            'email' => auth()->user()->email,
            'firm' => auth()->user()->company_name ?? 'PAYMONEY',
            'callback' => 'https://pesa24.in/api/apiservice/paysprint-onboarding-callbackurl.php',
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'ZTU2ZjlmYTBkOWFkMjVmM2VlNjE5MDUwMDUzYjhiOGU=',
            'Content-Type: application/json'
        ])->post('https://api.paysprint.in/api/v1/service/onboard/onboard/getonboardurl', $data);
        Log::channel('response')->info($response);
        return $response;
    }
}
