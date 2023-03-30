<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class DMTController extends Controller
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

    public function remiterQuery(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['mobile'],
            'bank3_flag' => 'NO'
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ]);

        return $response;
    }

    public function registerRemiter(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['mobileNo'],
            'firstname' => $request['firstName'],
            'lastname' => $request['lastName'],
            'otp' => $request['otp'],
            'pincode' => $request['pinCode'],
            'stateresp' => $request['stateresp'],
            'bank3_flag' => 'NO',
            'dob'=> $request['dob'],
            'gst_state' => 07
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/remitter/registerremitter', $data);
    }

    /*--------------------------------------Benificiary--------------------------------------*/

    public function registerBeneficiary(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['mobileNo'],
            'benename' => $request['beneName'],
            'bankid' => $request['bankId'],
            'accno' => $request['accountNo'],
            'ifsccode' => $request['ifsc'],
            'verified' => 0,
            'bank3_flag' => 'NO',
            'dob'=> $request['dob'],
            'gst_state' => 07,
            'pincode' => $request['pinCode'],
            'address' => $request['address']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
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
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/deletebeneficiary', $data);

        return $response;
    }

    public function fetchBeneficiary(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['mobile']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
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
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/fetchbeneficiarybybeneid', $data);

        return $response;
    }


    /*------------------------------------------------- Transaction-------------------------------------------------*/

    public function penneyDrop(Request $request)
    {
        $token = $this->token();
        
        $data = [
            'mobile' => $request['mobileNo'],
            'accno' => $request['accountNo'],
            'benename' => $request['beneName'],
            'referenceid' => uniqid(),
            'pincode' => $request['pinCode'],
            'address' => $request['address'],
            'bankid' => $request['bankId'],
            'gst_state' => 07,
            'bene_id' => $request['beneId']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/benenameverify', $data);

        return $response;
    }

    public function newTransaction(Request $request)
    {
        $token = $this->token();

        $data = [
            'mobile' => $request['mobileNo'],
            'accno' => $request['accountNo'],
            'benename' => $request['beneName'],
            'referenceid' => uniqid(),
            'pincode' => $request['pinCode'],
            'address' => $request['address'],
            'bankid' => $request['bankId'],
            'gst_state' => 07,
            'dob' => $request['dob'],
            'amount' => $request['amount'],
            'pipe' => 'bank1',
            'txntype' => $request['txnType'],
            'bene_id' => $request['beneId']
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/transact/transact', $data);

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
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
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
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
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
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
            'content-type' => 'application/json',
        ])->post('https://paysprint.in/service-api/api/v1/service/dmt/refund/refund/', $data);

        return $response;
    }
    
}
