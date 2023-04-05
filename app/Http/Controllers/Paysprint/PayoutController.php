<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\CommissionController;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

        $data = ['merchant_code' => '2222222'];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/list', $data);

        return $response;
    }

    public function addAccount(Request $request)
    {
        $user = User::findOrFail($request['id']);
        $token = $this->token();
        $data = [
            'bankid' => $user->paysprint_bank_code,
            'merchant_code' => 1122,
            'account' => $user->account_number,
            'ifsc' => $user->ifsc,
            'name' => $user->name,
            'account_type' => 'PRIMARY',
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/add', $data);

        if ($response->json($key = 'bene_id')) {
            $user->update(['paysprint_bene_id' => $response->json($key = 'bene_id')]);
        }

        return $response;
    }

    public function documents(Request $request)
    {
        $user = DB::table('users')->where(['id' => $request['id'], 'organization_id' => auth()->user()->organization_id])->get();
        $pan = $user[0]->pan_photo;
        $passbook = $user[0]->passbook;
        $token = $this->token();

        $doctype = 'PAN';
        $data = [
            'passbook' => Storage::path($passbook),
            'doctype' => $doctype,
            'panimage' => Storage::path($pan),
            'bene_id' => $user[0]->paysprint_bene_id
        ];

        // if ($doctype == 'PAN') {
        //     $data['panimage'] = Storage::get($pan);
        // }
        // } else {
        //     $data['front_image'] = Storage::get($user);
        //     $data['back_image'] = Storage::get($pan);
        // }

        $response = Http::attach('passbook', file_get_contents($data['passbook']), 'passbok.jpg')->attach('panimage', file_get_contents($data['panimage']), 'panimage.jpg')->asForm()->acceptJson()->withHeaders([
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
            'bene_id' => $request['beneId'] ?? '2043',
            'amount' => $request['amount'] ?? 1000,
            'refid' => uniqid(),
            'mode' => 'IMPS'
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/dotransaction', $data);

        return $response;

        if ($response->json($key = 'status') == true) {
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $request['amount'];
            $transaction_id = "PAY" . strtoupper(Str::random(9));
            $this->transaction($request['amount'], 'Payout Transaction', 'payout', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left);
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);
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

    public function moneyTransfer(Request $request)
    {
        $transaction_id = strtoupper(uniqid() . Str::random(8));
        $data = DB::table('money_transfers')->insert([
            'sender_id' => auth()->user()->id,
            'reciever_id' => $request['recieverId'],
            'amount' => $request['amount'],
            'transaction_id' => $transaction_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = User::findOrFail($request['recieverId']);
        $final_amount = $user->wallet + $request['amount'];
        $this->transaction(0, 'Money Transfer to User account', 'money-transfer', $request['recieverId'], $user->wallet, $transaction_id, $final_amount);
        DB::table('users')->where('id', $request['recieverId'])->update(['wallet' => $final_amount, 'updated_at' => now()]);

        return response()->json(['message' => "Successfull"]);
    }
}
