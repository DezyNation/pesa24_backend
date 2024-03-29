<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        ])->post('https://api.paysprint.in/api/v1/service/fastag/Fastag/operatorsList', []);
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
        ])->post('https://api.paysprint.in/api/v1/service/fastag/Fastag/fetchConsumerDetails', $data);

        return $response;
    }

    public function payAmount(Request $request)
    {
        $request->validate([
            'canumber' => 'required',
            'operator' => 'required',
            'latlong' => 'required',
            'bill' => 'required'
        ]);
        $latlong = explode(",", $request['latlong']);
        $token = $this->token();
        $data = [
            'operator' => $request['operator'],
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
        ])->post('https://api.paysprint.in/api/v1/service/fastag/Fastag/recharge', $data);

        $transaction_id = "BBPS" . strtoupper(Str::random(9));
        $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
        $balance_left = $walletAmt[0] - $data['amount'];
        if ($response['response_code'] == 1) {
            $metadata = [
                'status' => $response['status'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'],
                'amount' => $data['amount'],
                'reference_id' => $data['referenceid'],
            ];


            $this->transaction($data['amount'], "Fastag Rcharge", 'fastag', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->fastCommission(auth()->user()->id, $data['amount']);
        } else {
            $metadata = [
                'status' => $response['status'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'],
                'amount' => $data['amount'],
                'reference_id' => $data['referenceid'],
            ];
            $this->transaction(0, "Fastag Recharge", 'fastag', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
        }
        return [$response->body(), 'metadata' => $metadata];
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
        ])->post('https://api.paysprint.in/api/v1/service/fastag/Fastag/status', $data);

        return $response;
    }
}
