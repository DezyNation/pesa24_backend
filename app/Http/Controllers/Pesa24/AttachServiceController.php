<?php

namespace App\Http\Controllers\Pesa24;

use CURLFile;
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
                'eko_active' => 0,
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

        $user = User::find(auth()->user()->id);
        $pan = $user->pan_photo;
        $aadhar_front = $user->aadhar_front;
        $aadhar_back = $user->aadhar_back;
        $pan_photo = storage_path($pan);
        $aadhar_front_photo = "../storage/app/$aadhar_front";
        $aadhar_back_photo = "../storage/app/$aadhar_back";
        return $aadhar_back_photo;


        $target_url = "https://api.eko.in:25002/ekoicici/v1/user/service/activate";

        $cfile1 = new CURLFile(realpath($pan_photo));
        $cfile2 = new CURLFile(realpath($aadhar_front_photo));
        $cfile3 = new CURLFile(realpath($aadhar_back_photo));

        $data = [
            'service_code' =>  52,
            'initiator_id' => 9758105858,
            'user_code' => $user->user_code,
            'modelname' => $user->model_name ?? 'MODEL12',
            'devicenumber' => $user->device_number ?? 123465,
            'office_address' => json_encode(['line' => strval($user->line), 'city' => strval($user->city), 'state' => strval($user->state), 'pincode' => strval($user->pincode)]),
            'address_as_per_proof' => json_encode(['line' => strval($user->line), 'city' => strval($user->city), 'state' => strval($user->state), 'pincode' => strval($user->pincode)]),
        ];
        $post = array(
            'pan_card' => $cfile1,
            'aadhar_front' => $cfile2,
            'aadhar_back' => $cfile3,
            "form-data" => "service_code={$data['service_code']}&initiator_id={$data['initiator_id']}&user_code={$data['user_code']}&devicenumber={$data['devicenumber']}&modelname={$data['modelname']}&office_address={$data['office_address']}&address_as_per_proof={$data['address_as_per_proof']}"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data', "developer_key: 28fbc74a742123e19bcda26d05453a18", "secret-key:$secret_key", "secret-key-timestamp:$secret_key_timestamp"));
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        $response = curl_exec($ch);
        Log::channel('response')->info($response);
        $arr = json_decode($response, true);
        if ($arr['status'] == 0 || $arr['status'] == 1295) {
            $response = $this->enableEko(24);
            return $response;
        } else {
            return response("Could not activate at the moment", 502);
        }
        return $response;
    }


    // public function paysprintOnboard()
    // {
    //     $token = $this->token();

    //     $data = [
    //         'merchantcode' => auth()->user()->user_code,
    //         'mobile' => auth()->user()->phone_number,
    //         'is_new' => 0,
    //         'email' => auth()->user()->email,
    //         'firm' => auth()->user()->company_name ?? 'PAYMONEY',
    //         'callback' => 'https://pesa24.in/api/apiservice/paysprint-onboarding-callbackurl.php',
    //     ];

    //     $response = Http::withHeaders([
    //         'Token' => $token,
    //         'Authorisedkey' => 'ZTU2ZjlmYTBkOWFkMjVmM2VlNjE5MDUwMDUzYjhiOGU=',
    //         'Content-Type: application/json'
    //     ])->post('https://api.paysprint.in/api/v1/service/onboard/onboard/getonboardurl', $data);
    //     Log::channel('response')->info($response);
    //     return $response;
    // }

    public function generalService($id)
    {
        $service = Service::find($id);
        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'service_code' => $service->eko_id,
            'initiator_id' => 9758105858,
            'user_code' => auth()->user()->user_code ?? 208991002,
            'latlong' => '28.728630,77.166050'
        ];
        $response = Http::asForm()->withHeaders([
            'developer_key' => "28fbc74a742123e19bcda26d05453a18",
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://api.eko.in:25002/ekoicici/v1/user/service/activate', $data);
        Log::channel('response')->info($response);
        if (array_key_exists('status', $response->json())) {
            if ($response['status'] == 0 || $response['status'] == 1295) {
                $response = $this->enableEko($id);
                return $response;
            } else {
                return response("Could not activate at the moment", 502);
            }
        }
    }

    public function ekoActicvateService($service_code)
    {
        if ($service_code == 24) {
            $data = $this->aepsEnroll();
            return $data;
        } else {
            $data = $this->generalService($service_code);
            return $data;
        }
    }

    public function ekoServices()
    {
        $key = "12e848e9-a3a5-425e-93e9-2f4548625409";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $response = Http::withHeaders([
            'developer_key' => "28fbc74a742123e19bcda26d05453a18",
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->get('https://api.eko.in:25002/ekoicici/v1/user/services?initiator_id=9758105858');

        return $response->json();
    }

    public function enableEko($service_id)
    {
        $data = DB::table('service_user')
            ->where(['user_id' => auth()->user()->id, 'service_id' => $service_id])
            ->update([
                'eko_active' => 1,
                'updated_at' => now()
            ]);

        return response("Service activated");
    }
}
