<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;
use CURLFile;
use Illuminate\Support\Facades\Log;

class PANController extends CommissionController
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

    /*------------------------------PAN NSDL------------------------------*/
    public function generateUrl(Request $request)
    {

        $request->validate([
            'title' => 'required',
            'firstName' => 'required',
            'lastName' => 'required',
            'mode' => 'required',
            'gender' => 'required',
            'email' => 'required'
        ]);

        $token = $this->token();

        $data = [
            'refid' => "PESA24" . strtoupper(uniqid() . Str::random(12)),
            'title' => '1',
            'firstname' => $request['firstName'],
            'middlename' => $request['middleName'],
            'lastname' => $request['lastName'],
            'mode' => $request['mode'] ?? 'E',
            'gender' => $request['gender'] ?? 'M',
            'redirect_url' => 'https://pesa24.co.in',
            'email' => $request['email']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'Content-Type: application/json'
        ])->post('https://api.paysprint.in/api/v1/service/pan/V2/generateurl', $data);

        if ($response['status'] == true && $response['response_code'] == 1) {
            $metadata = [
                'status' => $response['status'] ?? null,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'reference_id' => $data['refid'],
                'event' => 'PAN Card NSDL'
            ];

            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0];
            $transaction_id = "DMT" . strtoupper(Str::random(9));
            $this->transaction(0, 'PAN Card generation', 'pan', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->panCommission('generation', auth()->user()->id);
        }

        // $response2 = Http::asForm()->post("{$response['data']['url']}", [
        //     'encdata' => $response['data']['encdata'],
        //     'email' => $request['email'],
        //     'refid' => $data['refid']
        // ]);
        // Log::info($response2);
        return  $response;
    }

    public function panStatus(Request $request)
    {

        $token = $this->token();

        $data = [
            'refid' => $request['refid'],
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://api.paysprint.in/api/v1/service/pan/V2/pan_status', $data);

        return $response;
    }

    public function file()
    {
        $token = $this->token();
        $data = [
            'doctype' => 'PAN',
            'bene_id' => 1257678,
            'passbook' => 'ASas',
            'panimage' => 'asas'
        ];


        $response = Http::attach('passbook', file_get_contents('../storage/app/pan/aqdr41MnYbMgcA6kv7vaFuVFrozdnOJya8NbFhc4.jpeg', true), 'passbook')->attach('panimage', file_get_contents('../storage/app/pan/aqdr41MnYbMgcA6kv7vaFuVFrozdnOJya8NbFhc4.jpeg', true), 'panimage')
            ->asForm()->withHeaders([
                'Token' => $token,
                'Authorisedkey' => env('AUTHORISED_KEY'),
            ])->post('https://api.paysprint.in/api/v1/service/payout/payout/uploaddocument', $data);

        return $response;
    }
}
