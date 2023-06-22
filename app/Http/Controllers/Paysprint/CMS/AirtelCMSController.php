<?php

namespace App\Http\Controllers\Paysprint\CMS;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class AirtelCMSController extends Controller
{
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

    public function generateUrl(Request $request)
    {
        $data = [
            'transaction_id' => $request['transactionId'],
            'refid' => uniqid(),
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
        ];

        $token = $this->token();
        Log::channel('response')->info('request', $request->all());
        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://paysprint.in/service-api/api/v1/service/airtelcms/V2/airtel/index', $data);
        Log::channel('response')->info('response', $response->json());
            $this->apiRecords($data['transaction_id'], 'paysprint', $response);
            if (array_key_exists('responsecode', $response->json())) {

                if ($response['responsecode'] == 1) {
                    DB::table('cms_records')->insert([
                        'user_id' => auth()->user()->id,
                        'reference_id' => $data['refid'],
                        'biller_id' => $request['billerId'],
                        'transaction_id' => $data['transaction_id'],
                        'created_at' => now(),
                        'provider' => 'airtel'
                    ]);
            } else {
                $response = $response['message'];
            }
        }

        return $response;
    }

    public function transactionStatus(Request $request)
    {
        $data = [
            'refid' => $request['referenceId'] ?? uniqid()
        ];

        $token = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://api.paysprint.in/api/v1/service/airtelcms/airtel/status', $data);

        return $response;
    }
}
