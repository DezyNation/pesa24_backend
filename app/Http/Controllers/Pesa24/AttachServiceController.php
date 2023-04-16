<?php

namespace App\Http\Controllers\Pesa24;

use App\Models\User;
use Firebase\JWT\JWT;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AttachServiceController extends Controller
{

    public function token()
    {
        $key = 'UFMwMDEyNGQ2NTliODUzYmViM2I1OWRjMDc2YWNhMTE2M2I1NQ==';
        $payload = [
            'timestamp' => now(),
            'partnerId' => 'PS001',
            'reqid' => abs(crc32(uniqid()))
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public function allServices()
    {
        $data = DB::table('services')->get([
            'name',
            'price'
        ]);

        return $data;
    }

    public function services($id)
    {
        $data = DB::table('services')->where('id', $id)->get([
            'name',
            'price'
        ]);

        return $data;
    }

    public function attachService($service_id)
    {
        if (DB::table('service_user')->where(['service_id' => $service_id, 'user_id' => auth()->user()->id])->exists()) {
            return response("Service already activated", 200);
        }
        $user = User::findOrfail(auth()->user()->id);
        $service = Service::findOrFail($service_id);
        DB::table('service_user')->updateOrInsert(
            ['service_id' => $service_id, 'user_id' => auth()->user()->id],
            [
                'paysprint_active' => 1,
                'pesa24_active' => 1,
                'eko_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        $opening_balance = $user->wallet;
        $amount = $service->price;
        $closing_balance = $opening_balance - $amount;
        $transaction_id = "SER" . strtoupper(Str::random(5));

        $user->update([
            'wallet' => $closing_balance
        ]);

        $metadata = [
            'status' => true,
            'amount' => $amount,
            'refernce_id' => strtoupper(uniqid())
        ];
        $this->transaction($amount, 'Service Activation', 'service', auth()->user()->id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata));

        return response('Service acivated.');
    }

    public function aepsEnroll($service_code)
    {
        $key = env('EKO_KEY');
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $pan = Storage::download(auth()->user()->pan_photo, 'pancard.jpeg');
        $aadhar_front = Storage::download(auth()->user()->aadhar_front, 'aadhar_front.jpeg');
        $aadhar_back = Storage::download(auth()->user()->aadhar_back, 'aadhar_back.jpeg');

        $data = [
            'service_code' =>   $service_code,
            'initiator_id' => env('EKO_INITIATOR_ID'),
            'user_code' => auth()->user()->user_code,
            'modelname' => auth()->user()->model_name,
            'devicenumber' => auth()->user()->device_number,
            'office_address' => json_encode(['line' => strval(auth()->user()->line), 'city' => strval(auth()->user()->city), 'state' => strval(auth()->user()->state), 'pincode' => strval(auth()->user()->pincode)]),
            'address_as_per_proof' => json_encode(['line' => strval(auth()->user()->line), 'city' => strval(auth()->user()->city), 'state' => strval(auth()->user()->state), 'pincode' => strval(auth()->user()->pincode)]),
            'pancard' => $pan,
            'aadhar_front' => $aadhar_front,
            'aadhar_back' => $aadhar_back
        ];

        Log::channel('response')->info($data['pancard']);

        $response = Http::asForm()
            ->withHeaders([
                'developer_key' => env('EKO_DEVELOPER_KEY'),
                'secret-key-timestamp' => $secret_key_timestamp,
                'secret-key' => $secret_key,
            ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
        Log::channel('response')->info($response);
        return $response;
    }


    public function paysprintOnboard()
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

    public function generalService($id)
    {
        $key = env('EKO_KEY');
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'service_code' => $id,
            'initiator_id' => env('EKO_INITIATOR_ID'),
            'user_code' => auth()->user()->user_code,
        ];
        $response = Http::asForm()->withHeaders([
            'developer_key' => env('EKO_DEVELOPER_KEY'),
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
        Log::channel('response')->info($response);

        return $response;
    }
}
