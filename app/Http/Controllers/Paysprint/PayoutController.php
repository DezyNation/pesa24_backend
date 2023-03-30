<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\CommissionController;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PayoutController extends CommissionController
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

    public function getList()
    {
        $token = $this->token();

        $data = ['111111'];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/list', $data);

        return $response;
    }

    public function addAccount(Request $request)
    {
        $token = $this->token();
        
        $data = [
            'bankid'=> $request['bankCode'],
            'merchant_code' => 1122,
            'account' => 00002,
            'ifsc' => $request['ifsc'],
            'name' => $request['name'],
            'account_type' => 'PRIMARY',
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/add', $data);

        return $response;
    }

    public function documents(Request $request)
    {
        $token = $this->token();

        $doctype = $request['doctype'];
        $data = [
            'doctype' => $doctype,
            'passbook' => $request->file('passbook'),
            
        ];

        if ($doctype == 'PAN') {
            $data['panimage'] = $request->file('panimage');
        } else {
            $data['front_image'] = $request->file('front_image');
            $data['back_image'] = $request->file('back_iamge');
        }

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/uploaddocument', $data);

        return $response;
    }

    public function accountStatus(Request $request)
    {
        $token = $this->token();

        $data = [
            'beneid' => 'JSKSDSD',
            'merchantid' => 'SDSDSDSDS'
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/Payout/accountstatus', $data);

        return $response;
    }

    public function doTransaction(Request $request)
    {
        $token = $this->token();

        $data = [
            'bene_id' => $request['beneId'],
            'amount' => $request['amount'],
            'refid' => uniqid(),
            'mode' => 'IMPS'
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/dotransaction', $data);

        if ($response->json($key = 'status') == true) {
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $request['amount'];
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);
            $transaction_id = "PAY".strtoupper(Str::random(9));
            $this->transaction($request['amount'], 'Payout Transaction', 'payout', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left);
            $this->payoutCommission(auth()->user()->id, $request['amount']);
        }

        return $response;
    }

    public function status(Request $request)
    {
        $token = $this->token();

        $data = [
            'refid' => $request['refId'],
            'ackno' => $request['ackno'],
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/status', $data);

        return $response;
    }
}