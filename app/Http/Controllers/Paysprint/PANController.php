<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

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
            'refid' => "PESA24".strtoupper(uniqid() . Str::random(12)),
            'title' => $request['title'] ?? 'Mr',
            'firstname' => $request['firstName'] ?? 'Rishi',
            'middlename' => $request['middleName'],
            'lastname' => $request['lastName'] ?? 'Kumar',
            'mode' => $request['mode'] ?? 'Online',
            'gender' => $request['gender'] ?? 'M',
            'redirect_url' => 'https://pesa24.co.in',
            'email' => $request['email']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/pan/V2/generateurl', $data);

        if ($response['status'] == true && $response['response_code'] == 1) {
            $metadata = [
                'status' => $response['status'] ?? null,
                'reference_id' => $data['refid'] ?? null,
                'event' => 'PAN Card NSDL'
            ];

            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0];
            $transaction_id = "DMT" . strtoupper(Str::random(9));
            $this->transaction(0, 'PAN Card generation', 'pan', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->panCommission('generation', auth()->user()->id);

            return response(['metadata' => $metadata, 'response' => $response->body()], 200);
        }

        return $response;
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
        ])->post('https://paysprint.in/service-api/api/v1/service/pan/V2/pan_status', $data);

        return $response;
    }
}
