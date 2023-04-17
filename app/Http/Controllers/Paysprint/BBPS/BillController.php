<?php

namespace App\Http\Controllers\Paysprint\BBPS;

use App\Http\Controllers\CommissionController;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class BillController extends CommissionController
{

    public function token(): string
    {
        $key = 'UFMwMDEyNGQ2NTliODUzYmViM2I1OWRjMDc2YWNhMTE2M2I1NQ==';
        $payload = [
            'timestamp' => now(),
            'partnerId' => 'PS001',
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
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post("https://paysprint.in/service-api/api/v1/service/bill-payment/bill/getoperator", []);

        is_null($id) ?
            $response = $response
            : $response =  collect($response->json($key = 'data'))->whereIn('id', $id);
        return $response;
        // return collect($response->json($key = 'data'))->whereIn('id', $id);
    }

    public function fetchBill(Request $request)
    {
        $token = $this->token();

        $bill = $request['bill'];

        $data = [
            'operator' => $request['operator'],
            'canumber' => $request['canumber'],
            'mode' => 'online'
        ];

        $finalData = array_merge($bill, $data);
        $response = Http::acceptJson()->withHeaders([
            'token' => $token,
            'content-type' => 'application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/fetchbill', $finalData);

        return $response;
    }

    public function payBill(Request $request)
    {
        $token = $this->token();

        $data = [
            'operator' => $request['operator'],
            'canumber' => $request['canumber'],
            'amount' => $request['amount'],
            'referenceid' => uniqid() . Str::random(12),
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'mode' => 'online',
            'bill' => $request['bill']
        ];

        $response = Http::acceptJson()->withHeaders([
            'token' => $token,
            'content-type' => 'application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/paybill', $data);

        if ($response->json($key = 'status') == true) {
            $metadata = [
                'status' => $response['status'],
                'message' => $data['message'],
                'amount' => $data['amount'],
                'operatorid' => $response['operatorid'],
                'reference_id' => $data['referenceid'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $data['amount'];
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);

            $transaction_id = "BBPS" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "Bill Payment", 'bbps', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->bbpsPaysprintCommission(auth()->user()->id, $data['operator'], $data['amount']);
        }

        return $response;
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
