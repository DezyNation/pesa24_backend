<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PANController extends Controller
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


    public function generateUrl(Request $request)
    {
        $token = $this->token();

        $data = [
            'refid' => strtoupper(uniqid() . Str::random(12)),
            'title' => $request['title'],
            'firstname' => $request['firstName'],
            'middlename' => $request['middleName'],
            'lastname' => $request['lastName'],
            'mode' => $request['mode'],
            'gender' => $request['gender'],
            'redirect_url' => 'https://pesa24.co.in',
            'email' => $request['email']
        ];

        $response = Http::aaceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/pan/V2/generateurl', $data);

        return $response;
    }

    public function panStatus(Request $request)
    {

        $token = $this->token();

        $data = [
            'refid' => 'somerefid',
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
        ])->post('https://paysprint.in/service-api/api/v1/service/pan/V2/pan_status', $data);

        return $response;
    }
}
