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


    /*------------------------------------------Recharge v1------------------------------------------*/
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

    public function browsePlans(Request $request)
    {
        $token = $this->token();
        $data = [
            'circle' => $request['networkCircle'],
            'op' => $request['selectedOperatorName']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/browseplan", $data);


        return $response;
    }

    public function doRecharge(Request $request)
    {

        $token = $this->token();
        $data = [
            'operator' =>  $request['operator'],
            'canumber' =>  $request['canumber'],
            'amount' =>  $request['amount'],
            'referenceid' => uniqid(),
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/recharge/recharge/dorecharge', $data);

        return $response;
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

        $client = new Client();

        $response = $client->request('POST', 'https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/hlrcheck', [
            'body' => "{'number':9971412064,'type':'mobile'}",
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
                'Token' => $token,
                'accept' => 'application/json',
            ],
        ]);
        // $response = Http::acceptJson()->withHeaders([
        //     'Token' => $token,
        //     'accept' => 'application/json',
        //     'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
        //     'content-type' => 'application/json',
        // ])->post("https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/hlrcheck", $data);


        return $response;
    }

    // public function locationOperator(Request $request)
    // {
    //     $token = $this->token();
    //     $data = [
    //         'service_name' => 'M',
    //         'service_provider_name' => 'Vi',
    //         'location_name' => 'Mum',
    //         'refid' => uniqid()
    //     ];

    //     $response =  Http::acceptJson()->withHeaders([
    //         'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
    //         'Token' => $token,
    //         'content-type' => 'application/json',
    //     ])->post('https://paysprint.in/service-api/api/v1/service/recharge/plan/list', $data);

    //     return $response;
    // }

    public function recharge(Request $request)
    {
        $token = $this->token();
        $data = [
            'operator' =>  $request['operator'],
            'canumber' =>  $request['canumber'],
            'amount' =>  $request['amount'],
            'referenceid' => uniqid(),
            'location' => $request['location']
        ];
        return $request->all();

        $response = Http::withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/recharge/Recharge_v2/dorecharge', $data);

        return $response;
    }


    public function location(Request $request)
    {
        $token  = $this->token();
        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/recharge/Recharge_v2/location", []);

        return $response;
    }
}
