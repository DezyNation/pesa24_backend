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
        if (DB::table('service_user')->where(['service_id'=> $service_id, 'user_id' => auth()->user()->id])->exists()) {
            return response(true, 200);
        }
        $user = User::findOrfail(auth()->user()->id);
        $service = Service::findOrFail($service_id);
        if ($service_id == 23) {
            // $paysprint = $this->paysprintOnboard();
            $eko = $this->aepsEnroll($service->eko_id);
            if ($eko->json($key = 'response_status_id') !== -1) {
                return response('Could not activate this service at he moment.', 501);
            }
        } else {
            $eko = $this->generalService($service->eko_id);
            if ($eko->json($key = 'response_status_id') !== 1) {
                return response('Could not activate this service at he moment.', 501);
            }
        }
        DB::table('service_user')->updateOrInsert(
            ['service_id' => $service_id, 'user_id' => auth()->user()->id],
            [
                'paysprint_active' => 1,
                'pesa24_active' => 1,
                'eko_active' => $eko['response_status_id']
            ]
        );
        $opening_balance = $user->wallet;
        $amount = $service->price;
        $closing_balance = $opening_balance-$amount;
        $transaction_id = "SER".strtoupper(Str::random(5));

        $this->transaction($amount, 'Service Activation', 'service', auth()->user()->id, $opening_balance, $transaction_id, $closing_balance);

        return response('Service will be activated in next 3 hours.');
    }

    public function aepsEnroll($service_code)
    {
        $key = env('EKO_KEY');
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'service_code' =>   $service_code,
            'initiator_id' => env('EKO_INITIATOR_ID'),
            'user_code' => auth()->user()->user_code,
            'modelname' => auth()->user()->model_name,
            'devicenumber' => auth()->user()->device_number,
            'office_address' => json_encode(['line' => strval(auth()->user()->line), 'city' => strval(auth()->user()->city), 'state' => strval(auth()->user()->state), 'pincode' => strval(auth()->user()->pincode)]),
            'address_as_per_proof' => json_encode(['line' => strval(auth()->user()->line), 'city' => strval(auth()->user()->city), 'state' => strval(auth()->user()->state), 'pincode' => strval(auth()->user()->pincode)])
        ];
        $storage = 'storage/app/';
        $pan = Storage::disk('local')->get(storage_path(auth()->user()->pan_photo));
        $aadhar_front = Storage::disk('local')->get(storage_path(auth()->user()->aadhar_front));
        $aadhar_back = Storage::disk('local')->get(storage_path(auth()->user()->aadhar_back));
        return $aadhar_back;

        $response = Http::asForm()
            ->attach('pancard', file_get_contents($pan), 'pan.pdf')->attach('aadhar_front', file_get_contents($aadhar_front), 'aadhar_front.pdf')->attach('aadhar_back', file_get_contents($aadhar_back), 'aadhar_back.pdf')
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
