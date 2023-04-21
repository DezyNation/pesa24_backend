<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use App\Models\User;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class RechargeController extends CommissionController
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
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post("https://api.paysprint.in/api/v1/service/recharge/recharge/getoperator", []);

        return collect($response->json($key = 'data'))->whereIn('category', [$type]);
    }

    public function location(Request $request)
    {
        $token  = $this->token();
        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post("https://api.paysprint.in/api/v1/service/recharge/Recharge_v2/location", []);

        return $response;
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
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post("https://api.paysprint.in/api/v1/service/recharge/hlrapi/browseplan", $data);


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
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://api.paysprint.in/api/v1/service/recharge/recharge/dorecharge', $data);


        if ($response->json('status') == true) {
            $metadata = [
                'status' => $response['status'],
                'mobile_number' => $data['canumber'],
                'amount' => $data['amount'],
                'operator' => $response['operatorid'],
                'reference_id' => $response['refid'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $data['amount'];
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);

            $transaction_id = "RECH" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "Recharge for Mobile {$data['canumber']}", 'recharge', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->rechargeCommissionPaysprint(auth()->user()->id, $data['operator'],  $request['amount']);
        } else {
            $metadata = [
                'status' => false,
                'mobile_number' => $data['canumber'],
                'amount' => $data['amount'],
                'refid' => $data['referenceid'],
                'operator' => $data['operator']
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $transaction_id = "RECH" . strtoupper(Str::random(9));
            $this->transaction(0, "Recharge for Mobile {$data['canumber']}", 'recharge', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));
        }

        return [$response, 'metadata' => $metadata];
    }

    /*------------------------------------------Recharge v1------------------------------------------*/


    /*------------------------------------------Recharge v2------------------------------------------*/
    // public function recharge(Request $request)
    // {
    //     $token = $this->token();
    //     $data = [
    //         'operator' =>  $request['operator'],
    //         'canumber' =>  $request['canumber'],
    //         'amount' =>  $request['amount'],
    //         'referenceid' => uniqid(),
    //         'location' => $request['location']
    //     ];
    //     return $request->all();

    //     $response = Http::withHeaders([
    //         'Token' => $token,
    //         'accept' => 'application/json',
    //         'Authorisedkey' => env('PAYSPRINT_PARTNERID'),
    //         'content-type' => 'application/json',
    //     ])->post('https://api.paysprint.in/api/v1/service/recharge/Recharge_v2/dorecharge', $data);

    //     return $response;
    // }
    /*------------------------------------------Recharge v2------------------------------------------*/

    public function parameter()
    {
        $token  = $this->token();

        $data = [
            "service_name" => "M",
            'service_provider_name' => 'Vi',
            'location_name' => 'Mum',
            'refid' => uniqid()
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://api.paysprint.in/api/v1/service/recharge/plan/list", $data);

        return $response->json($key = 'data');
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

    // public function hlrCheck(Request $request)
    // {
    //     $token = $this->token();
    //     $data = [
    //         'number' => 9971412064,
    //         'type' => 'mobile'
    //     ];

    //     $client = new Client();

    //     $response = $client->request('POST', 'https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/hlrcheck', [
    //         'body' => "{'number':9971412064,'type':'mobile'}",
    //         'headers' => [
    //             'Content-Type' => 'application/json',
    //             'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
    //             'Token' => $token,
    //             'accept' => 'application/json',
    //         ],
    //     ]);
    // $response = Http::acceptJson()->withHeaders([
    //     'Token' => $token,
    //     'accept' => 'application/json',
    //     'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
    //     'content-type' => 'application/json',
    // ])->post("https://paysprint.in/service-api/api/v1/service/recharge/hlrapi/hlrcheck", $data);

    //     return $response;
    // }
}
