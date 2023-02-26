<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LICController extends Controller
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

    public function fetchBill()
    {
        $data = [
            'canumber' => 334489350,
            'ad1' => 'rk3141508@gmail.com',
            'mode' => 'online'
        ];

        $token = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/fetchlicbill', [
            'body' => json_encode($data)
        ]);

        return $response;
    }

    public function payLicBill()
    {
        $token = $this->token();
        $data = [
            'canumber' => 554984552,
            'mode' => 'offline',
            'amount' => 100,
            'ad1' => 'nitesh@rnfiservices.com',
            'ad2' => 'HDC610532',
            'ad3' => 'HDC416601',
            'referenceid' => '2021052415',
            'latitude' => '27.2232',
            'longitude' => '78.26535',
            'bill_fetch' => json_encode([
                'billNumber' => 'LICI2122000037468013',
                'billAmount' => 1548.00,
                'billnetamount' => 1548.00,
                'billdate' => '25-05-2021 00:44:29',
                'acceptPayment' => true,
                'acceptPartPay' => false,
                'cellNumber' => 905514651,
                'dueFrom' => '11/05/2021',
                'dueTo' => '11/05/2021',
                'validationId' => 'HGA8V00A110382264047',
                'billId' => 'HGA8V00A110382264047B'
            ])
        ];

        $response = Http::asJson()->withHeaders([
            'token' => $token,
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/paylicbill', [
            'body' => json_encode($data)
        ]);

        return $response;
    }
}
