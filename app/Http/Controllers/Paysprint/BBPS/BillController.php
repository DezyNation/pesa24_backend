<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use App\Http\Controllers\CommissionController;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BillController extends CommissionController
{

    public function token(): string
    {
        $key = env('JWT_KEY');
        $payload = [
            'timestamp' => time(),
            'partnerId' => env('PAYSPRINT_PARTNERID'),
            'reqid' => abs(crc32(uniqid()))
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    public function operatorParameter($id = null)
    // : \Illuminate\Support\Collection
    {
        $token  = $this->token();

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/bill-payment/bill/getoperator/307", []);

        is_null($id) ?
            $response = collect($response->json($key = 'data'))->groupBy('category')
            : $response =  collect($response->json($key = 'data'))->whereIn('id', $id);
        return $response;
        // return collect($response->json($key = 'data'))->whereIn('id', $id);
    }

    public function fetchBill(Request $request)
    {
        $token = $this->token();


        $data = [
            'operator' => $request['operator_id'],
            'canumber' => $request['canumber'],
            'mode' => 'online'
        ];

        $response = Http::acceptJson()->withHeaders([
            'token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/fetchbill', $data);

        return $response;
    }

    public function payBill(Request $request)
    {
        $token = $this->token();

        $data = [
            'operator' => $request['operator_id'],
            'canumber' => $request['canumber'],
            'amount' => $request['amount'],
            'referenceid' => uniqid() . Str::random(12),
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'mode' => 'online',
            'bill_fetch' => json_encode($request['bill'], true)
        ];

        $response = Http::acceptJson()->withHeaders([
            'token' => $token,
            'content-type' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/paybill', $data);

        if ($response->json($key = 'response_code') == 1 || $response->json($key = 'response_code') == 0) {
            $metadata = [
                'status' => $response['status'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'],
                'amount' => $data['amount'],
                'operatorid' => $response['operatorid'],
                'reference_id' => $data['referenceid'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $data['amount'];

            $transaction_id = "BBPS" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "Bill Payment", 'bbps', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->bbpsPaysprintCommission(auth()->user()->id, $data['operator'], $data['amount']);

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
            $this->transaction($data['amount'], 'Bill payment', 'bbps', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));

            return response([$response['message'], 'metadata' => $metadata], 400);
        }
        return response([$response['message'], 'metadata' => $metadata]);
    }


    public function statusEnquiry(Request $request)
    {
        $token = $this->token();

        $data = [
            'referenceid' => $request['refer']
        ];

        $response = Http::acceptJson()->withHeaders([
            'token' => $token,
            'content-type' => 'application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/status', $data);

        return $response;
    }
}
