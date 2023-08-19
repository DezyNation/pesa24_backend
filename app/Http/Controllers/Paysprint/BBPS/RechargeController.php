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
use Illuminate\Support\Facades\Log;

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
        $request->validate([
            'operator' => 'required',
            'canumber' => 'required',
            'amount' => 'required|numeric'
        ]);
        $token = $this->token();
        $data = [
            'operator' =>  $request['operator'],
            'canumber' =>  $request['canumber'],
            'amount' =>  $request['amount'],
            'referenceid' => uniqid("RCH"),
        ];

        $transaction_id = $data['referenceid'];
        $response = Http::asJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://api.paysprint.in/api/v1/service/recharge/recharge/dorecharge', $data);

        $this->apiRecords($transaction_id, 'paysprint', $response);


        if ($response->json('response_code') == 1 || $response->json('response_code') == 2 || $response->json('response_code') == 0) {
            if ($response->json('response_code') == 1) {
                $status = 'success';
            } else {
                $status = 'pending';
            }

            DB::table('recharge_requests')->insert([
                'user_id' => auth()->user()->id,
                'provider' => 'paysprint',
                'operator' => $response['operator'],
                'operator_name' => $request['operatorName'],
                'status' => $status,
                'amount' => $data['amount'],
                'response_code' => $response['response_code'],
                'ack_no' => $response['ackno'],
                'ca_number' => $data['canumber'],
                'reference_id' => $data['referenceid'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $metadata = [
                'status' => $status,
                'mobile_number' => $data['canumber'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'operator_name' => $request['operatorName'],
                'user_phone' => auth()->user()->phone_number,
                'amount' => $data['amount'],
                'operator' => $response['operatorid'],
                'reference_id' => $response['refid'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = auth()->user()->wallet;
            $balance_left = $walletAmt - $data['amount'];

            $this->transaction($data['amount'], "Recharge for Mobile {$data['canumber']}", 'recharge', auth()->user()->id, $walletAmt, $transaction_id, $balance_left, json_encode($metadata));
            $this->rechargeCommissionPaysprint(auth()->user()->id, $data['operator'],  $request['amount'], $transaction_id, $data['canumber']);
        } else {
            $metadata = [
                'status' => 'failed',
                'mobile_number' => $data['canumber'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'operator_name' => $request['operatorName'],
                'amount' => $data['amount'],
                'refid' => $data['referenceid'],
                'operator' => $data['operator'],
                'reason' => "Server Busy"
            ];
            return [$response, 'metadata' => $metadata];
        }

        return [$response->body(), 'metadata' => $metadata];
    }

    public function statusEnquiry(Request $request)
    {
        $request->validate([
            'referenceId' => 'required|exists:recharge_requests,reference_id'
        ]);

        $reference_id = $request['referenceId'];

        $data = DB::table('recharge_requests')->where('reference_id', $reference_id)->first();

        if ($data->status == 'pending') {
            $token = $this->token();

            $array = [
                'referenceid' => $reference_id
            ];

            $response = Http::acceptJson()->withHeaders([
                'Token' => $token,
                'accept' => 'application/json',
                'Authorisedkey' => env('AUTHORISED_KEY'),
                'content-type' => 'application/json',
            ])->post("https://api.paysprint.in/api/v1/service/recharge/hlrapi/browseplan", $array);


            $this->apiRecords($reference_id, 'paysprint', $response);

            if ($response->status() !== 200) {
                return response($response['message'], $response->status());
            }

            if ($response['status'] == true && $response['data']['status'] == 1) {
                $status = 'success';
            } elseif ($response['status'] == true && $response['data']['status'] == 0) {
                $status = 'failed';
            } else {
                $status = 'pending';
            }

            DB::table('recharge_requests')->where('reference_id', $reference_id)->update([
                'status' => $status,
                'updated_at' => now()
            ]);

            DB::table('transactions')->where('transaction_id', $reference_id)->update([
                'metadata->status' => $status,
                'updated_at' => now()
            ]);

            if ($status == 'failed') {
                $user = User::find($data->user_id);
                $wallet = $user->wallet;
                $closing_balance = $wallet + $data->amount;

                $metadata = [
                    'status' => 'success',
                    'event' => 'recharge.refund',
                    'operator_name' => $response['data']['operatorname'],
                    'operator_id' => $response['data']['operatorid']
                ];
                $this->notAdmintransaction(0, "Recharge refund for {$data->ca_number}", 'recharge', $data->user_id, $wallet, $data->reference_id, $closing_balance, json_encode($metadata), $data->amount);
                $this->rechargeRevCommission($data->user_id, $response['data']['operatorname'], $data->amount, $reference_id, $data->ca_number);
            }
        }
        return $reference_id;
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
    //     ])->post('https://api.paysprint.in/api/v1/service/recharge/plan/list', $data);

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

    //     $response = $client->request('POST', 'https://api.paysprint.in/api/v1/service/recharge/hlrapi/hlrcheck', [
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
    // ])->post("https://api.paysprint.in/api/v1/service/recharge/hlrapi/hlrcheck", $data);

    //     return $response;
    // }
}
