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

        $metadata = [
            'status' => true,
            'amount' => $amount,
            'refernce_id' => strtoupper(uniqid())
        ];
        $this->transaction($amount, 'Service Activation', 'service', auth()->user()->id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata));

        return response('Service acivated.');
    }

    public function aepsEnroll()
    {
        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $user = User::find(auth()->user()->id ?? 85);
        $pan = $user->pan_photo;
        $aadhar_front = $user->aadhar_front;
        $aadhar_back = $user->aadhar_back;
        $pan_photo = file_get_contents("../storage/app/$pan");
        $aadhar_front_photo = file_get_contents("../storage/app/$aadhar_front");
        $aadhar_back_photo = file_get_contents("../storage/app/$aadhar_back");

        $data = ['form-data' =>[
            'service_code' =>  52,
            'initiator_id' => 9758105858,
            'user_code' => $user->user_code,
            'modelname' => $user->model_name ??'MODEL12',
            'devicenumber' => $user->device_number??123465,
            'office_address' => json_encode(['line' => strval($user->line), 'city' => strval($user->city), 'state' => strval($user->state), 'pincode' => strval($user->pincode)]),
            'address_as_per_proof' => json_encode(['line' => strval($user->line), 'city' => strval($user->city), 'state' => strval($user->state), 'pincode' => strval($user->pincode)]),
        ]];
        // return $data;

        $response = Http::attach('pancard', $pan_photo, 'pan.jpg')->attach('aadhar_front', $aadhar_front_photo, 'aadhar_front.jpg')->attach('aadhar_back', $aadhar_back_photo, 'aadhar_back.jpg')
            ->withHeaders([
                'developer_key' => "28fbc74a742123e19bcda26d05453a18",
                'secret-key-timestamp' => $secret_key_timestamp,
                'secret-key' => $secret_key,
            // ])->put('https://api.eko.in:25002/ekoicici/v1/user/service/activate', ['form-data' => "service_code={$data['service_code']}&initiator_id=9758105858&user_code={$data['user_code']}&devicenumber={$data['devicenumber']}4&modelname={$data['modelname']}&office_address={$data['office_address']}&address_as_per_proof={data['address_as_per_proof']}"]);
            ])->put('https://api.eko.in:25002/ekoicici/v1/user/service/activate', [$data]);

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
        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'service_code' => $id,
            'initiator_id' => 9758105858,
            'user_code' => auth()->user()->user_code ?? 208991002,
        ];
        $response = Http::asForm()->withHeaders([
            'developer_key' => "28fbc74a742123e19bcda26d05453a18",
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://api.eko.in:25002/ekoicici/v1/user/service/activate', $data);
        Log::channel('response')->info($response);

        return $response;
    }

    public function ekoActicvateService($service_code)
    {
        if ($service_code == 52) {
            $data = $this->aepsEnroll();
            return $data;
        } else {
            $data = $this->generalService($service_code);
            return $data;
        }
    }
}
