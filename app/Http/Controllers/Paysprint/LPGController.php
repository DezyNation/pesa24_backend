<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LPGController extends Controller
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

    public function operatorList()
    {
        $token = $this->token();
        $data = ['mode' => 'online'];

        $response = Http::acceptJson()->withHeaders([
            'Content-type' => 'application/json',
            'Token' => $token,
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/lpg/getoperator', $data);

        return $response;
    }
}
