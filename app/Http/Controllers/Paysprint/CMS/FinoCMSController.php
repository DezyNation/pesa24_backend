<?php

namespace App\Http\Controllers\Paysprint\CMS;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FinoCMSController extends Controller
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
            'refid' => "PESA24FINOCMS".uniqid(),
        ];

        $token = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://api.paysprint.in/api/v1/service/finocms/fino/generate_url', $data);

        if ($response['response_code'] == 1) {
            DB::table('cms_records')->insert([
                'user_id' => auth()->user()->id,
                'reference_id' => $data['refid'],
                'transaction_id' => $data['transaction_id'],
                'created_at' => now(),
                'provider' => 'fino'
            ]);
        }

        return $response;
    }

    public function transactionStatus(Request $request)
    {
        $data = [
            'refid' => $request['referenceId']
        ];

        if ($request['provider'] == 'fino') {
            $url = 'https://api.paysprint.in/api/v1/service/finocms/fino/status';
        } else {
            $url = 'https://api.paysprint.in/api/v1/service/airtelcms/airtel/status';
        }

        $token = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post($url, $data);

        return $response;
    }
}
