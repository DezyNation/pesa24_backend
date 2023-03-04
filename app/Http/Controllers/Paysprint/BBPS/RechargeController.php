<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class RechargeController extends Controller
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

    public function operatorList($type)
    {
        $data = [
            "mode" => "online",
        ];
        $token  = $this->token();
        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/bill-payment/bill/getoperator", []);

        return collect($response->json($key = 'data'))->whereIn('category', [$type]);
    }

    // public function operatorList(Request $request)
    // {

    //     $token  = $this->token();
    //     $response = Http::acceptJson()->withHeaders([
    //         'Token' => $token,
    //         'accept' => 'application/json',
    //         'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
    //         'content-type' => 'application/json',
    //     ])->post("https://paysprint.in/service-api/api/v1/service/recharge/Recharge_v2/getoperator", []);

    //     // return collect($response->json($key = 'data'))->whereIn('category', [$type]);
    //     return $response;
    // }

    // public function location(Request $request)
    // {
    //     $token  = $this->token();
    //     $response = Http::acceptJson()->withHeaders([
    //         'Token' => $token,
    //         'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
    //         'content-type' => 'application/json',
    //     ])->post("https://paysprint.in/service-api/api/v1/service/recharge/Recharge_v2/location", []);

    //     return $response;
    // }



    public function operatorParameter($id)
    {
        $data = [
            "mode" => "online",
        ];
        $token  = $this->token();
        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/bill-payment/bill/getoperator", []);

        return collect($response->json($key = 'data'))->whereIn('id', $id);
        
    }

    public function parameter()
    {
        $data = [
            "service_name" => "M",
            'service_provider_name' => 'Vi',
            'location_name' => 'Mum',
            'refid' => uniqid()
        ];
        $token  = $this->token();
        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/recharge/plan/list", $data);
        return $response->json($key = 'data');
    }

    public function hlrCheck(Request $request)
    {
        $token = $this->token();
        $data = [
            'number' => 9971412064,
            'type' => 'mobile'
        ];

        new Client();
        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/hlrcheck", ['body'=> json_encode(['number'=> 9971412064, 'type'=> 'moblie'])] );


        return $response;
    }
    
    public function browsePlans(Request $request)
    {
        $token = $this->token();
        $data = [
            'circle' => 'Delhi NCR',
            'op' => 'Airtel'
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/browseplan", $data);


        return $response->json($key = 'info');
    }
}
