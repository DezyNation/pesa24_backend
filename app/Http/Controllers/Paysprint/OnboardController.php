<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class OnboardController extends Controller
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

    public function onboard(Request $request)
    {
        $token = $this->token();

        $data = [
            'merchantcode' => auth()->user()->user_code,
            'mobile' => auth()->user()->phone_number,
            'is_new' => 0,
            'email' => auth()->user()->email,
            'firm' => auth()->user()->company_name ?? 'PAYMONEY',
            'callback' => 'https://pesa24.in/appsapinew/dashboard/home.php',
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://api.paysprint.in/api/v1/service/onboard/onboard/getonboardurl', $data);

        return $response;
    }
}
