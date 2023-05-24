<?php

namespace App\Http\Controllers\Paysprint\CMS;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'transaction_id' => $request['transactionId'] ?? uniqid(),
            'refid' => uniqid(),
        ];

        $token = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://api.paysprint.in/api/v1/service/airtelcms/airtel/generate_url', $data);

        if ($response['response_code'] == 1) {
            DB::table('cms_records')->insert([
                'user_id' => auth()->user()->id,
                'reference_id' => $data['refid'],
                'transaction_id' => $data['transaction_id'],
                'created_at' => now(),
                'provider' => 'airtel'
            ]);
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
