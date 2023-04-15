<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\CommissionController;
use Illuminate\Support\Facades\Http;

class DMTController extends CommissionController
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

    public function remiterQuery(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['customerId'],
            'bank3_flag' => 'NO'
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/remitter/queryremitter', $data);

        return $response;
    }

    public function registerRemiter(Request $request)
    {
        $token = $this->token();
        $name = explode(" ", $request['customerName']);
        $data = [
            'mobile' => $request['customerId'],
            'firstname' => $name[0],
            'lastname' => $name[1],
            'address' => $request['street'],
            'otp' => $request['otp'],
            'pincode' => $request['pincode'],
            'stateresp' => $request['stateresp'],
            'bank3_flag' => 'NO',
            'dob' => $request['customerDob'],
            'gst_state' => 07
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/remitter/registerremitter', $data);

        return $response;
    }

    /*--------------------------------------Benificiary--------------------------------------*/

    public function registerBeneficiary(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['customerId'],
            'benename' => $request['beneficiaryName'],
            'bankid' => $request['bankCode'],
            'accno' => $request['accountNumber'],
            'ifsccode' => $request['ifsc'],
            'verified' => 0,
            'bank3_flag' => 'NO',
            'dob' => auth()->user()->dob,
            'gst_state' => 07,
            'pincode' => $request['pinCode'],
            'address' => $request['address']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary', $data);

        return $response;
    }

    public function deleteBeneficiary(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['mobile'],
            'bene_id' => $request['bene_id']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/deletebeneficiary', $data);

        return $response;
    }

    public function fetchBeneficiary(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['customerId']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/fetchbeneficiary', $data);

        return $response;
    }

    public function beneficiaryById(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['mobile'],
            'beneid' => $request['beneid']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/fetchbeneficiarybybeneid', $data);

        return $response;
    }


    /*------------------------------------------------- Transaction-------------------------------------------------*/

    public function penneyDrop(Request $request)
    {
        $token = $this->token();

        $data = [
            'refid' => uniqid() . Str::random(10),
            'account_number' => $request['accountNumber'],
            'ifsc' => $request['ifsc'],
            'ifsc_details' => true
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/verification/bank/verify', $data);

        return $response;
    }

    public function newTransaction(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['customerId'],
            'accno' => $request['accountNumber'],
            'benename' => $request['beneficiaryName'],
            'referenceid' => uniqid(),
            'pincode' => auth()->user()->pincode,
            'address' => auth()->user()->line,
            'bankid' => $request['selectedBankCode'],
            'gst_state' => 07,
            'dob' => auth()->user()->dob,
            'amount' => $request['amount'],
            'pipe' => 'bank1',
            'txntype' => $request['transactionType'],
            'bene_id' => $request['beneficiaryId']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/transact/transact', $data);

        if ($response->json($key = 'status') == true) {

            $metadata = [
                'status' => $response['status'] ?? null,
                'reference_id' => $data['referenceid'] ?? null,
                'amount' => $response['txn_amount'] ?? null,
                'account_number' => $response['account_number'] ?? null,
                'remitter' => $response['remitter'] ?? null,
                'beneficiary_name' => $response['benename'] ?? null,
                'acknowldgement_number' => $response['ackno'] ?? null,
                'remarks' => $response['remarks'] ?? null,
                'message' => $response['message'] ?? null
            ];

            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $request['amount'];
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);
            $transaction_id = "DMT" . strtoupper(Str::random(9));
            $this->transaction($request['amount'], 'DMT Transaction', 'dmt', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->dmtCommission(auth()->user()->id, $request['amount']);
        }

        return $response;
    }

    public function transactionStatus(Request $request)
    {
        $token = $this->token();

        $data = [
            'referenceid' => $request['referenceid']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/transact/transact/querytransact', $data);

        return $response;
    }

    public function refundOtp(Request $request)
    {
        $token = $this->token();

        $data = [
            'refernceid' => $request['refernceId'],
            'ackno' => $request['ackno']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/transact/transact/querytransact', $data);

        return $response;
    }

    public function claimRefund(Request $request)
    {
        $token = $this->token();

        $data = [
            'refernceid' => $request['refernceId'],
            'ackno' => $request['ackno'],
            'otp' => $request['otp']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/refund/refund/', $data);

        return $response;
    }
}
