<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class LICController extends CommissionController
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
        $request->validate([
            'canumber' => 'required',
        ]);
        $data = [
            'canumber' => $request['canumber'],
            'ad1' => $request['ad1'] ?? "",
            'mode' => 'online'
        ];

        $token = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://paysprint.in/service-api/api//v1/service/bill-payment/bill/fetchlicbill', $data);

        return $response;
    }

    public function payLicBill(Request $request)
    {
        $request->validate([
            'canumber' => 'required',
            'latlong' => 'required',
            'bill' => 'required',
            'amount' => 'required'
        ]);
        $latlong = explode(",", $request['latlong']);
        $token = $this->token();
        $data = [
                'canumber' => $request['canumber'],
                'mode' => 'online',
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
            'Authorisedkey' => env('AUTHORISED_KEY')
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/paylicbill', $data);

        
        if ($response->json($key = 'response_code') == 1 || $response->json($key = 'response_code') == 0) {
            $metadata = [
                'status' => $response['status'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'],
                'amount' => $data['amount'],
                'reference_id' => $data['referenceid'],
                'acknowldgement_number' => $response['ackno'] ?? null,
            ];
            $walletAmt = auth()->user()->wallet;
            $balance_left = $walletAmt - $data['amount'];

            $transaction_id = "BBPS" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "Bill Payment for LIC", 'lic', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->licCommission(auth()->user()->id, $data['amount']);

            return response(['metadata' => $metadata]);

        } elseif ($response->json($key = 'response_code') == 16 || $response->json($key = 'response_code') == 6 || $response->json($key = 'response_code') == 12) {
            $metadata = [
                'status' => false,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'canumber' => $data['canumber'],
                'amount' => $data['amount'],
            ];
            return response(["Server Busy pleasy try later!", 'metadata' => $metadata], 501);
        } else {
            $metadata = [
                'status' => false,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'canumber' => $data['canumber'],
                'amount' => $data['amount'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $transaction_id = "DMT" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], 'Bill payment', 'lic', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));

            return response([$response['message'], 'metadata' => $metadata], 400);
        }
        
        return $response;
    }
}
