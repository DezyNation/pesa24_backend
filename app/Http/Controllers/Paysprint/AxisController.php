<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class AxisController extends Controller
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

    public function generateUcc(Request $request)
    {
        $request->validate([
            'type' => 'required', 'integer'
        ]);
        $token = $this->token();
        $data = [
            'merchantcode' => auth()->user()->paysprint_merchant ?? 1232232,
            'type' => $request['type']
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/axisbank-utm/axisutm/generateurl', $data);

        return $response;
    }
}
