<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LICController extends Controller
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

    public function fetchBill(Request $request)
    {
        $data = [
            'canumber' => $request['canumber'],
            'ad1' => $request['add1'],
            'mode' => $request['mode']
        ];

        $token = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ='
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/fetchlicbill', $data);

        return $response;
    }

    public function payLicBill(Request $request)
    {
        $latlong = explode(",", $request['latlong']);
        $token = $this->token();
        $data = [
                'canumber' => $request['canumber'],
                'mode' => $request['mode'],
                'amount' => $request['amount'],
                'ad1' => $request['ad1'],
                'ad2' => $request['ad2'],
                'ad3' => $request['ad3'],
                'referenceid' => uniqid(),
                'latitude' => $latlong[0],
                'longitude' => $latlong[1],
                'bill_fetch' => json_encode($request['bill'], true)
        ];

        $response = Http::asJson()->withHeaders([
            'token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ='
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/paylicbill', $data);
        
        return $response;
    }
}
