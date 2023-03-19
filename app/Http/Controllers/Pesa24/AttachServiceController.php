<?php

namespace App\Http\Controllers\Pesa24;

use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AttachServiceController extends Controller
{
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

    public function attachService($id)
    {
        $user = User::findOrfail(auth()->user()->id);
        $service = Service::findOrFail($id);
        if ($id == 45) {
            $this->aepsEnroll(45);
            $this->paysprintOnboard();
        } else {
            $this->generalService($id);
        }
        return response("Sucecssfully enrolled", 200);
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
        $pan = Storage::disk('local')->get(auth()->user()->pan);
        $aadhar_front = Storage::disk('local')->get(auth()->user()->aadhar_front);
        $aadhar_back = Storage::disk('local')->get(auth()->user()->aadhar_back);

        $response = Http::asForm()->attach('pancard', file_get_contents($pan), 'pan.pdf')->attach('aadhar_front', file_get_contents($aadhar_front), 'aadhar_front.pdf')->attach('aadhar_back', file_get_contents($aadhar_back), 'aadhar_back.pdf')->withHeaders([
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
    }
}
