<?php

namespace App\Http\Controllers\Eko\Agent;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\v1\UserResource;
use CURLFile;

class AgentManagementController extends Controller
{

    public function headerArray()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => env('DEVELOPER_KEY'),
            // 'secret-key' => $secret_key,
            // 'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    public function activateService(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        if ($request['serviceCode'] == 43 || $request['serviceCode'] == 52) {
            $data = [
                'service_code' => $request['serviceCode'],
                'initiator_id' => '9962981729',
                'user_code' => auth()->user()->user_code,
                'modelname' => $request['modelname'],
                'devicenumber' => $request['devicenumber'],
                'office_address' => json_encode(['line' => strval($request['line']), 'city' => strval($request['city']), 'state' => strval($request['state']), 'pincode' => strval($request['pincode'])]),
                'address_as_per_proof' => json_encode(['line' => strval($request['lineProof']), 'city' => strval($request['cityProof']), 'state' => strval($request['stateProof']), 'pincode' => strval($request['pincodeProof'])])
            ];
            $pan = $request->file('pancard');
            $aadhar_front = $request->file('aadhar_front');
            $aadhar_back = $request->file('aadhar_back');

            $response = Http::asForm()->attach('pancard', file_get_contents($pan), 'pan.pdf')->attach('aadhar_front', file_get_contents($aadhar_front), 'aadhar_front.pdf')->attach('aadhar_back', file_get_contents($aadhar_back), 'aadhar_back.pdf')->withHeaders([
                'developer_key' => 'becbbce45f79c6f5109f848acd540567',
                'secret-key-timestamp' => $secret_key_timestamp,
                'secret-key' => $secret_key,
            ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
        } else {
            $data = [
                'service_code' => $request['service_code'],
                'initiator_id' => '9962981729',
                'user_code' => auth()->user()->user_code,
            ];
            $response = Http::asForm()->withHeaders([
                'developer_key' => 'becbbce45f79c6f5109f848acd540567',
                'secret-key-timestamp' => $secret_key_timestamp,
                'secret-key' => $secret_key,
            ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
        }

        return $response;
    }

    public function aepsOnboard(Request $request)
    {
        $data = [
            'service_code' => $request['serviceCode'] ?? 43,
            'initiator_id' => '9962981729',
            'user_code' => auth()->user()->user_code ?? 20810200,
            'modelname' => $request['modelname'] ?? 'ANYDFEFE',
            'devicenumber' => $request['devicenumber'] ?? 'q1232e',
            'office_address' => json_encode(['line' => strval($request['line'] ?? 'ABC'), 'city' => strval($request['city'] ?? 'ASD'), 'state' => strval($request['state'] ?? 'Haryana'), 'pincode' => strval($request['pincode'] ?? 110033)]),
            'address_as_per_proof' => json_encode(['line' => strval($request['line'] ?? 'ABC'), 'city' => strval($request['city'] ?? 'ASD'), 'state' => strval($request['state'] ?? 'Haryana'), 'pincode' => strval($request['pincode'] ?? 110033)]),
            // 'address_as_per_proof' => json_encode(['line' => strval($request['lineProof']), 'city' => strval($request['cityProof']), 'state' => strval($request['stateProof']), 'pincode' => strval($request['pincodeProof'])]),
            'pan_card' => new CURLFile('../storage/app/pan/pan.jpeg', '', 'pan_card'),
            'aadhar_front' => new CURLFile('../storage/app/aadhar_front/aadhaarfront.jpeg', '', 'aadhar_front'),
            'aadhar_back' => new CURLFile('../storage/app/aadhar_back/aadharback.jpeg', '', 'aadhar_back')
        ];
        // $pan = "../storage/app/pan/pan.jpeg";
        // $aadhar_front = "../storage/app/aadhar_front/aadhaarfront.jpeg";
        // $aadhar_back = "../storage/app/aadhar_back/aadharback.jpeg";

        // $response = Http::
        // attach('pan_card', file_get_contents($pan, true), 'pan_card.jpeg')
        // ->attach('aadhar_front', file_get_contents($aadhar_front, true), 'aadhar_front.jpeg')
        // ->attach('aadhar_back', file_get_contents($aadhar_back, true), 'aadhar_back.jpeg')
        // ->asForm()
        // ->withHeaders(
        //     $this->headerArray()
        // )->put('http://staging.eko.in:8080/ekoapi/v1/user/service/activate', $data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://staging.eko.in:25004/ekoapi/v1/user/service/activate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $data,
        // array('form-data' => 'service_code=53&initiator_id='.$initiator_id.'&user_code=20111378&devicenumber=1710I506336&modelname=Mantra&office_address='.json_encode($arr).'&address_as_per_proof='.json_encode($arr).'','pan_card'=> new CURLFILE('../images/kyc-verification/1_pancard.jpg'),'aadhar_front'=> new CURLFILE('../images/kyc-verification/1_adfront.jpg'),'aadhar_back'=> new CURLFILE('../images/kyc-verification/1_adback.jpg')),
        CURLOPT_HTTPHEADER => $this->headerArray()
        // array(
        // 'secret-key: '.$secret_key.'',
        // 'secret-key-timestamp: '.$secret_key_timestamp.'',
        // 'developer_key: '.$developer_key.''
        // ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
         dd($response);
    }

    public function services()
    {
        $initiator_id = 9962981729;

        $response = Http::withHeaders(
            $this->headerArray()
        )->get("http://staging.eko.in:8080/ekoapi/v1/user/services?initiator_id=$initiator_id");

        return $response;
    }

    public function userServiceInquiry()
    {
        $usercode = auth()->user()->user_code;
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $initiator_id = 9962981729;

        $response = Http::accept('*/*')->withHeaders([
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive',
            'Host' => 'staging.eko.in:25004',
            'Cache-Control' => 'no-cache',
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v1/user/services/user_code:$usercode?initiator_id:$initiator_id", ['initiator_id' => $initiator_id]);

        return $response;
    }

    public function newService(Request $request)
    {
        $data = [
            'service_code' => $request['service_code'] ?? 14,
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => auth()->user()->user_code ?? 20810200,
        ];
        $response = Http::asForm()->withHeaders(
        $this->headerArray()
        )->put('http://staging.eko.in:8080/ekoapi/v1/user/service/activate', $data);

        return $response;
    }
}
