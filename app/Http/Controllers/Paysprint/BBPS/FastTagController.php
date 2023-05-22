<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class FastTagController extends CommissionController
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

    public function operatorList()
    {
        $token = $this->token();
        $response = Http::withHeaders([
            'Token' => $token,
            'Content-Type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://paysprint.in/service-api/api/v1/service/fastag/Fastag/operatorsList', []);
        return $response;
    }

    public function fetchConsumer(Request $request)
    {
        $request->validate([
            'operator' => 'required',
            'canumber' => 'required',
        ]);

        $token = $this->token();

        $data = [
            'operator' => $request['operator'] ?? 321,
            'canumber' => $request['canumber'] ?? 123345454
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Content-Type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://paysprint.in/service-api/api/v1/service/fastag/Fastag/fetchConsumerDetails', $data);

        return $response;
    }

    public function payAmount(Request $request)
    {
        $request->validate([
            'canumber' => 'required',
            'latlong' => 'required',
            'bill' => 'required'
        ]);
        $latlong = explode(",", $request['latlong']);
        $token = $this->token();
        $data = [
            'canumber' => $request['canumber'],
            'amount' => $request['amount'],
            'referenceid' => uniqid(),
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'bill_fetch' => json_encode($request['bill'], true)
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Content-Type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://paysprint.in/service-api/api/v1/service/fastag/Fastag/recharge', $data);

        return $response;
    }

    public function status(Request $request)
    {
        $token = $this->token();
        $data = [
            'referenceid' => $request['referenceId']
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Content-Type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://paysprint.in/service-api/api/v1/service/fastag/Fastag/status', $data);

        return $response;
    }
}
