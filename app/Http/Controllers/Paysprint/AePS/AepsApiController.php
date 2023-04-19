<?php

namespace App\Http\Controllers\Paysprint\AePS;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CommissionController;

class AepsApiController extends CommissionController
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

    public function enquiry(Request $request)
    {
        $key = '060e37d1f82cde00';
        $iv = '788a4b959058271e';


        $pid = $request['pid'];
        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0] ?? 22.78,
            'longitude' => $latlong[1] ?? 19.45,
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'mobilenumber' => $request['customerId'],
            'adhaarnumber' => $request['aadhaarNo'],
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => 652294,
            'requestremarks' => 'AePS enquiry',
            'data' => $pid,
            'pipe' => 'bank3',
            'timestamp' => now(),
            'submerchantid' => 1,
            'transactiontype' => 'BE',
            'is_iris' => 'No'
        ];

        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);

        $token = $this->token();
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
        ])->post('https://paysprint.in/service-api/api/v1/service/aeps/balanceenquiry/index', ['body' => $body]);

        return $response;
    }

    public function withdrwal(Request $request)
    {

        $key = '060e37d1f82cde00';
        $iv = '788a4b959058271e';

        $pid = $request['pid'];

        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0] ?? 22.78,
            'longitude' => $latlong[1] ?? 19.45,
            'mobilenumber' => $request['customerId'],
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'amount' => $request['amount'],
            'adhaarnumber' => $request['aadhaarNo'],
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => 652294,
            'requestremarks' => 'AePS Withdrwal',
            'data' => $pid,
            'pipe' => 'bank2',
            'timestamp' => now(),
            'submerchantid' => 9971412064,
            'transactiontype' => 'BE',
            'is_iris' => 'No'
        ];

        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);

        $token = $this->token();
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
        ])->post('https://paysprint.in/service-api/api/v1/service/aeps/cashwithdraw/index', ['body' => $body]);

        if ($response['status'] == true && $response['response_code'] == 1) {
            $metadata = [
                'status' => $response['status'],
                'message' => $data['message'],
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $data['amount'];
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);

            $transaction_id = "AEPSW" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "AePS withdrawal for {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->aepsComission($data['amount'], auth()->user()->id);
        } else {
            $metadata = [
                'status' => false,
                'message' => "Trasaction failed",
                'amount' => $data['amount'],
                'reference_id' => $data['referenceno'],
                'mobile_number' => $data['mobilenumber'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $transaction_id = "AEPS" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "AePS withdrawal for {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));
        }
        // $this->aepsCommssion($data['amount'], auth()->user()->id);
        return $response;
    }

    public function bankList()
    {
        $token = $this->token();
        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://paysprint.in/service-api/api/v1/service/aeps/banklist/index', []);

        return $response;
    }

    public function transactionStatus()
    {
        $key = '060e37d1f82cde00';
        $iv = '788a4b959058271e';

        $token = $this->token();
        $data = [
            'reference' => '234234S4433'
        ];

        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
        ]);

        return $response;
    }

    public function miniStatement(Request $request)
    {
        $key = '060e37d1f82cde00';
        $iv = '788a4b959058271e';


        $pid = $request['pid'];

        $data = [
            'latitude' => $request['latitude'] ?? 22.78,
            'longitude' => $request['longitude'] ?? 19.45,
            'mobilenumber' => $request['mobileNumber'] ?? 9971412064,
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'adhaarnumber' => $request['aadhaarNumber'] ?? 715547838073,
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => 990320,
            'requestremarks' => 'AePS mini statement',
            'data' => $pid,
            'pipe' => 'bank3',
            'timestamp' => now(),
            'submerchantid' => auth()->user()->phone_number,
            'transactiontype' => 'BE',
            'is_iris' => 'No'
        ];

        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);

        $token = $this->token();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://paysprint.in/service-api/api/v1/service/aeps/ministatement/index', ['body' => $body]);

        if ($response['status'] == true && $response['response_code'] == 1) {
            $metadata = [
                'status' => $response['status'],
                'message' => $data['message'],
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $data['amount'];
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);

            $transaction_id = "AEPSW" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "AePS Mini Statement for {$data['mobilenumber']}", 'mini-statement', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->aepsMiniComission($data['amount'], auth()->user()->id);
        }

        return $response;
    }

    public function aadhaarPay(Request $request)
    {
        $key = '060e37d1f82cde00';
        $iv = '788a4b959058271e';

        $pid = $request['pid'];

        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0] ?? 22.78,
            'longitude' => $latlong[1] ?? 19.45,
            'mobilenumber' => $request['customerId'],
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'amount' => $request['amount'],
            'adhaarnumber' => $request['aadhaarNo'],
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => 652294,
            'requestremarks' => 'AePS Withdrwal',
            'data' => $pid,
            'pipe' => 'bank2',
            'timestamp' => now(),
            'submerchantid' => 9971412064,
            'transactiontype' => 'BE',
            'is_iris' => 'No'
        ];

        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);

        $token = $this->token();
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
        ])->post('https://paysprint.in/service-api/api/v1/service/aadharpay/aadharpay/index', ['body' => $body]);

        if ($response['status'] == true && $response['response_code'] == 1) {
            $metadata = [
                'status' => $response['status'],
                'message' => $data['message'],
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'bankiin' => $response['bankiin'],
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $data['amount'];
            User::where('id', auth()->user()->id)->update([
                'wallet' => $balance_left
            ]);

            $transaction_id = "AAPAY" . strtoupper(Str::random(9));
            $this->transaction($data['amount'], "Aadhaar Pay {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->aepsComission($data['amount'], auth()->user()->id);
        }
        // $this->aepsCommssion($data['amount'], auth()->user()->id);
        return $response;
    }
}
