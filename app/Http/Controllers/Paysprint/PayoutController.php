<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\CommissionController;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
use CURLFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PayoutController extends CommissionController
{
    public function token()
    {
        $key = env('JWT_KEY');
        $payload = [
            'timestamp' => now(),
            'partnerId' => env('PAYSPRINT_PARTNERID'),
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
        ])->post('https://api.paysprint.in/api/v1/service/payout/payout/list', $data);

        return $response;
    }

    public function addAccount(Request $request)
    {
        $request->validate([
            'id' => 'exists:users'
        ]);
        $user = User::findOrFail($request['id']);
        $token = $this->token();
        $data = [
            'bankid' => $user->paysprint_bank_code,
            'merchant_code' => $user->paysprint_merchant,
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

        if (array_key_exists('bene_id', $response->json())) {
            $user->update(['paysprint_bene_id' => $response->json($key = 'bene_id')]);
            $response = $this->uploadDocuments($request['id']);
            return response($response);
        } else {
            return response("Could not implement at the moment. Reason: {$response['message']}", 501);
        }
    }

    public function documents(Request $request)
    {
        // $user = DB::table('users')->where(['id' => $request['id'], 'organization_id' => auth()->user()->organization_id])->get();
        // $pan = $user[0]->pan_photo;
        // $passbook = $user[0]->passbook;
        $token = $this->token();

        $doctype = 'PAN';
        $data = [
            'passbook' => new CURLFile('../storage/app/aadhar_front/aadhaarfront.jpeg'),
            'doctype' => $doctype,
            'panimage' => new CURLFile('../storage/app/aadhar_front/aadhaarfront.jpeg'),
            // 'bene_id' => $user[0]->paysprint_bene_id
            'bene_id' => 1257678
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://paysprint.in/service-api/api/v1/service/payout/payout/uploaddocument',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Token' => $token,
                'Authorisedkey' => env('AUTHORISED_KEY'),
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ),
        ));

        $response2 = curl_exec($curl);
        curl_close($curl);

        dd($response2);

        $response = Http::asForm()->acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'Content-Type' => 'application/json'
        ])->post('https://api.paysprint.in/api/v1/service/payout/payout/uploaddocument', $data);

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
        $request->validate([
            'userId' => 'required|exists:users,id',
            'amount' => 'required|integer',
        ]);
        $token = $this->token();
        $user = User::find($request['userId']);

        $capped_amount = $user->minimum_balance;
        $wallet = $user->wallet;
        $balance_left = $wallet - $request['amount'];

        if ($balance_left < 0 || $balance_left < $capped_amount) {
            abort(400, "User does not have enough balance.");
        } elseif (is_null($user->paysprint_bene_id)) {
            abort(400, "Account not added yet.");
        }


        $data = [
            'bene_id' => $user->paysprint_bene_id,
            'amount' => $request['amount'],
            'refid' => uniqid(),
            'mode' => 'IMPS'
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/dotransaction', $data);
        Log::channel('response')->info($response);
            $this->apiRecords($data['refid'], 'paysprint', $response);
        if ($response->json($key = 'status') == true) {
            $transaction_id = "PAY" . strtoupper(Str::random(9));
            $metadata = [
                'status' => true,
                'amount' => $request['amount'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number
            ];
            $this->transaction($request['amount'], 'Payout Transaction', 'payout', $request['userId'], $user->wallet, $transaction_id, $balance_left, json_encode($metadata));
        } else {
            $metadata = [
                'status' => false,
                'amount' => $request['amount'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number
            ];
        }

        return ['metadata' => $metadata];
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
        ])->post('https://api.paysprint.in/api/v1/service/payout/payout/status', $data);

        return $response;
    }

    public function fetchMoneyTransfer()
    {
        $data = DB::table('money_transfers')
            ->join('users as recievers', 'recievers.id', '=', 'money_transfers.reciever_id')
            ->where('money_transfers.sender_id', auth()->user()->id)
            ->select('recievers.name', 'recievers.phone_number', 'recievers.id as reciever_id', 'money_transfers.*')
            ->latest()
            ->paginate(20);

        return $data;
    }

    public function moneyTransfer(Request $request)
    {
        if ($request['beneficiaryId'] == auth()->user()->id) {
            return response("You can not send to money to yourself.", 403);
        }

        $request->validate([
            'amount' => 'min:1|numeric'
        ]);
        $user = User::find($request['beneficiaryId']);
        if (!$user) {
            return response("User not found!", 404);
        }
        $transaction_id = strtoupper(uniqid() . Str::random(8));
        $metadata = [
            'status' => true,
            'event' => 'money-transfer',
            'transaction_id' => $transaction_id,
            'created_at' => date("F j, Y, g:i a"),
            'amount' => $request['amount'],
            'from' => auth()->user()->name . " " . auth()->user()->phone_number
        ];
        $data = DB::table('money_transfers')->insert([
            'sender_id' => auth()->user()->id,
            'reciever_id' => $request['beneficiaryId'],
            'amount' => $request['amount'],
            'remarks' => $request['remarks'],
            'transaction_id' => $transaction_id,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $final_amount = $user->wallet + $request['amount'];
        $this->transaction(0, 'Money Transfer to your account', 'payout', $request['beneficiaryId'], $user->wallet, $transaction_id, $final_amount, json_encode($metadata), $request['amount']);
        $user->update(['wallet' => $final_amount]);

        $metadata = [
            'status' => true,
            'event' => 'money-transfer',
            'amount' => $request['amount'],
            'transaction_id' => $transaction_id,
            'user' => auth()->user()->name,
            'user_id' => auth()->user()->id,
            'user_phone' => auth()->user()->phone_number,
            'created_at' => date("F j, Y, g:i a"),
        ];
        $user = User::findOrFail(auth()->user()->id);
        $final_amount = $user->wallet - $request['amount'];
        $transaction_id = strtoupper(uniqid() . Str::random(8));
        $this->transaction($request['amount'], 'Money Transfer to User account', 'payout', auth()->user()->id, $user->wallet, $transaction_id, $final_amount, json_encode($metadata));
        $user->update(['wallet' => $final_amount]);
        return response()->json(['message' => "Successfull", 'metadata' => $metadata]);
    }

    public function uploadDocuments($id)
    {
        $user = User::find($id);
        $token = $this->token();

        $doctype = 'PAN';
        $data = [
            // 'passbook' => fopen(Storage::path('pan/16SsPkgJeJNUNgss40ZLEp6AUiEJuDdEzEPqnd9D'), 'r'),
            'doctype' => $doctype,
            'bene_id' => $user->paysprint_bene_id
        ];
        $data2 = [
            'passbook' => file_get_contents("../storage/app/$user->passbook"),
            'panimage' => file_get_contents("../storage/app/$user->pan_photo"),
        ];
    
        $response = Http::attach('passbook', $data2['passbook'], 'passbook.jpeg')->attach('panimage', $data2['panimage'], 'panimage.jpeg')
            ->acceptJson()->withHeaders([
                'Token' => $token,
                'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
                'Content-Type: application/json'
            ])->post('https://paysprint.in/service-api/api/v1/service/payout/payout/uploaddocument', $data);
    
        return $response;
    }
}
