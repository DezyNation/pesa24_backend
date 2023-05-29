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
            'timestamp' => time(),
            'partnerId' => env('PAYSPRINT_PARTNERID'),
            'reqid' => abs(crc32(uniqid()))
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public function enquiry(Request $request)
    {
        $request->validate([
            'latlong' => 'required',
            'customerId' => 'required|digits:10',
            'aadhaarNo' => 'required|digits:12',
            'pid' => 'required',
            'bankCode' => 'required'
        ]);

        $key = env('AES_ENCRYPTION_KEY');
        $iv = env('AES_ENCRYPTION_IV');


        $pid = $request['pid'];
        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'mobilenumber' => $request['customerId'],
            'adhaarnumber' => $request['aadhaarNo'],
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => $request['bankCode'],
            'requestremarks' => 'AePS enquiry',
            'data' => $pid,
            'pipe' => 'bank1',
            'timestamp' => now(),
            'submerchantid' => auth()->user()->paysprint_merchant,
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
        ])->post('https://api.paysprint.in/api/v1/service/aeps/balanceenquiry/index', ['body' => $body]);

        if ($response['response_code'] == 24) {
            return $this->onboard();
        }
        return ['metadata' => $response->object()];
    }

    public function withdrwal(Request $request)
    {

        $request->validate([
            'latlong' => 'required',
            'customerId' => 'required|digits:10',
            'amount' => 'required',
            'aadhaarNo' => 'required|digits:12',
            'pid' => 'required',
            'bankCode' => 'required'
        ]);

        $key = env('AES_ENCRYPTION_KEY');
        $iv = env('AES_ENCRYPTION_IV');

        $pid = $request['pid'];

        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => $request['customerId'],
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'amount' => $request['amount'],
            'adhaarnumber' => $request['aadhaarNo'],
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => $request['bankCode'],
            'requestremarks' => 'AePS Withdrwal',
            'data' => $pid,
            'pipe' => 'bank1',
            'timestamp' => now(),
            'submerchantid' => auth()->user()->paysprint_merchant,
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
        ])->post('https://api.paysprint.in/api/v1/service/aeps/cashwithdraw/index', ['body' => $body]);

        if ($response['response_code'] == 24) {
            return $this->onboard();
        }

        if ($response['status'] == true && $response['response_code'] == 1) {
            $transaction_id = "AEPSW" . strtoupper(Str::random(9));
            $metadata = [
                'status' => $response['status'],
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']??"Transaction Failed",
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] + $data['amount'];

            $this->transaction(0, "AePS withdrawal for {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata), $data['amount']);
            $this->aepsComission($data['amount'], auth()->user()->id);
        } else {
            $transaction_id = "AEPS" . strtoupper(Str::random(9));
            $metadata = [
                'status' => false,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']??"Transaction Failed",
                'amount' => $data['amount'],
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'reference_id' => $data['referenceno'],
                'reason' => $response['message'] ?? "null",
                'mobile_number' => $data['mobilenumber'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $this->transaction(0, "AePS withdrawal for {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));
        }
        // $this->aepsCommssion($data['amount'], auth()->user()->id);
        return response([$response->body(), 'metadata' => $metadata]);
    }

    public function bankList()
    {
        $token = $this->token();
        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://api.paysprint.in/api/v1/service/aeps/banklist/index', []);

        return $response;
    }

    public function transactionStatus(Request $request)
    {
        $request->validate([
            'reference' => 'required'
        ]);
        $key = env('AES_ENCRYPTION_KEY');
        $iv = env('AES_ENCRYPTION_IV');

        $token = $this->token();
        $data = [
            'reference' => $request['reference']
        ];

        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ]);

        return $response;
    }

    public function miniStatement(Request $request)
    {
        $request->validate([
            'latlong' => 'required',
            'customerId' => 'required|digits:10',
            'aadhaarNo' => 'required|digits:12',
            'pid' => 'required',
            'bankCode' => 'required'
        ]);

        $key = env('AES_ENCRYPTION_KEY');
        $iv = env('AES_ENCRYPTION_IV');


        $pid = $request['pid'];

        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => $request['customerId'],
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'adhaarnumber' => $request['aadhaarNo'],
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => $request['bankCode'],
            'requestremarks' => 'AePS mini statement',
            'data' => $pid,
            'amount' => 0,
            'pipe' => 'bank1',
            'timestamp' => now(),
            'submerchantid' => auth()->user()->paysprint_merchant,
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
        ])->post('https://api.paysprint.in/api/v1/service/aeps/ministatement/index', ['body' => $body]);
        $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
        $balance_left = $walletAmt[0] + $data['amount'];

        if ($response['response_code'] == 24) {
            return $this->onboard();
        }

        if ($response['status'] == true && $response['response_code'] == 1) {
            $transaction_id = "AEPSW" . strtoupper(Str::random(9));
            $metadata = [
                'status' => $response['status'],
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']??"Transaction Failed",
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];

            $this->transaction(0, "AePS Mini Statement for {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata), $data['amount']);
            $this->aepsMiniComission(auth()->user()->id);
        } else {
            $transaction_id = "MINIS" . strtoupper(Str::random(9));
            $metadata = [
                'status' => false,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']??"Transaction Failed",
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'reference_id' => $data['referenceno'],
                'mobile_number' => $data['mobilenumber'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $this->transaction(0, "Mini Statement for {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));
        }

        return [$response->object(), 'metadata' => $metadata];
    }

    public function aadhaarPay(Request $request)
    {

        $request->validate([
            'latlong' => 'required',
            'customerId' => 'required|digits:10',
            'amount' => 'required',
            'aadhaarNo' => 'required|digits:12',
            'pid' => 'required',
            'bankCode' => 'required'
        ]);

        $key = env('AES_ENCRYPTION_KEY');
        $iv = env('AES_ENCRYPTION_IV');

        $pid = $request['pid'];

        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => $request['customerId'],
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'amount' => $request['amount'],
            'adhaarnumber' => $request['aadhaarNo'],
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => $request['bankCode'],
            'requestremarks' => 'AePS Withdrwal',
            'data' => $pid,
            'pipe' => 'bank1',
            'timestamp' => now(),
            'submerchantid' => auth()->user()->paysprint_merchant,
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
        ])->post('https://api.paysprint.in/api/v1/service/aadharpay/aadharpay/index', ['body' => $body]);

        if ($response['response_code'] == 24) {
            return $this->onboard();
        }

        if ($response['status'] == true && $response['response_code'] == 1) {
            $transaction_id = "AAPAY" . strtoupper(Str::random(9));
            $metadata = [
                'status' => $response['status'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']??"Transaction Failed",
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'bankiin' => $response['bankiin'],
                'created_at' => date("F j, Y, g:i a"),
                'transaction_id' => $transaction_id,
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] - $data['amount'];

            $this->transaction($data['amount'], "Aadhaar Pay {$data['mobilenumber']}", 'aeps-ap', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata));
            $this->aepsComission($data['amount'], auth()->user()->id);
        } else {
            $transaction_id = "AADHP" . strtoupper(Str::random(9));
            $metadata = [
                'status' => false,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'event' => 'aadhar-pay',
                'message' => $response['message']??"Transaction Failed",
                'reference_id' => $data['referenceno'],
                'mobile_number' => $data['mobilenumber'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $this->transaction(0, "Aadhaar Pay {$data['mobilenumber']}", 'aeps-ap', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));
        }
        // $this->aepsCommssion($data['amount'], auth()->user()->id);
        return [$response->object(), 'metadata' => $metadata];
    }
}
