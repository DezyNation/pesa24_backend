<?php

namespace App\Http\Controllers\Eko\AePS;

use App\Http\Controllers\CommissionController;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AepsApiController extends CommissionController
{

    public function headerArray()
    {
        $key = "d2fe1d99-6298-4af2-8cc5-d97dcf46df30";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        return [
            'developer_key' => env('DEVELOPER_KEY'),
            'secret-key' => $secret_key,
            'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    public function requestHash(Request $request)
    {
        $aadhar = $request['aadharNo'] ?? 123456789012;
        $amount = $request['amount'] ?? 100;
        $usercode = 20810200;
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $string = $secret_key_timestamp . $aadhar . $amount . $usercode;
        $signature_req_hash = hash_hmac('SHA256', $string, $encodedKey, true);
        $request_hash = base64_encode($signature_req_hash);
        $public_key = env('EKO_PUBLIC_KEY');
        // $decodedKey1 = base64_decode($public_key);

        $search = [
            "-----BEGIN PUBLIC KEY-----",
            env('EKO_PUBLIC_KEY'),
            "-----END PUBLIC KEY-----",
            "\n",
            "\r",
            "\r\n"
        ];
        $public_key_resource = $search[0] . PHP_EOL . wordwrap($public_key, 64, "\n", true) . PHP_EOL . $search[2];
        openssl_public_encrypt($aadhar, $signature_req_hash, $public_key_resource);
        $encrypted_aadhar = base64_encode($signature_req_hash);

        return [
            'request_hash' => $request_hash,
            'encrypted_aadhaar' => $encrypted_aadhar,
            'user_code' => $usercode,
            'amount' => $amount
        ];
    }

    public function moneyTransfer(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/
        $encryption = $this->requestHash($request);

        /*--------------------------------Data------------------------------------ */

        $data = [
            "service_type" => "2",
            "initiator_id" => env('INITIATOR_ID'),
            "user_code" => $encryption['user_code'],
            "customer_id" => $request['customerId'] ?? 9971412064,
            "bank_code" => $request['bankCode'] ?? 'HDFC',
            "amount" => $encryption['amount'],
            "client_ref_id" => "PESA24AEPS" . strtoupper(uniqid()),
            "pipe" => "0",
            "aadhar" => $encryption['encrypted_aadhaar'],
            "latlong" => "81,81,12",
            "notify_customer" => "0",
            "piddata" => $request['pid'],
            "sourceip" => $request->ip()
        ];

        /*---------------------------Hit EKO api------------------------------*/

        $response = Http::withHeaders(array_merge($this->headerArray(), [
            // 'Content-Type' => 'application/json',
            'request_hash' => $encryption['request_hash']
        ]))->post('http://staging.eko.in:8080/ekoapi/v2/aeps', $data);


        return $response;
        $this->apiRecords($data['client_ref_id'], 'eko', $response);
        $transaction_id = "AEP" . strtoupper(Str::random(5));
        $opening_balance = auth()->user()->wallet;
        $closing_balance = $opening_balance + $encryption['amount'];

        $metadata = [
            'status' => true,
            'amount' => $encryption['amount']
        ];
        $this->transaction($encryption['amount'], 'AePS: Withdrawal', 'banking', auth()->user()->id, $opening_balance, $transaction_id, json_encode($metadata), $closing_balance);
        $this->aepsComission($encryption['amount'], auth()->user()->id);
        return $response;
    }

    public function miniStatement(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/
        $encryption = $this->requestHash($request);
        /*--------------------------------Data------------------------------------ */

        $data = [
            "service_type" => "4",
            "initiator_id" => 9962981729,
            "user_code" => $encryption['user_code'],
            "customer_id" => $request['customerId'] ?? 9971412064,
            "bank_code" => $request['bankCode'] ?? 'HDFC',
            "amount" => $encryption['amount'],
            "client_ref_id" => "PESA24AEPSM" . strtoupper(uniqid()),
            "pipe" => "0",
            "aadhar" => $encryption['encrypted_aadhaar'],
            "latlong" => $request['latlong'],
            "notify_customer" => "0",
            "piddata" => $request['pid'],
            "sourceip" => $request->ip()
        ];

        /*---------------------------Send Data------------------------------*/

        $response = Http::withHeaders(array_merge($this->headerArray(), [
            'Content-Type' => 'application/json',
            'request_hash' => $encryption['request_hash']
        ]))->post('https://staging.eko.in:25004/ekoapi/v2/aeps', $data);
        $this->apiRecords($data['client_ref_id'], 'eko', $response);
        return $response;
    }


    public function balanceEnquiry(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/

        $encryption = $this->requestHash($request);

        /*--------------------------------Data------------------------------------ */

        $data = [
            "service_type" => "3",
            "initiator_id" => 9962981729,
            "user_code" => $encryption['user_code'],
            "customer_id" => $request['customerId'] ?? 9971412064,
            "bank_code" => $request['bankCode'] ?? 'HDFC',
            "amount" => $encryption['amount'],
            "client_ref_id" => "PESA24AEPSB" . strtoupper(uniqid()),
            "pipe" => "0",
            "aadhar" => $encryption['encrypted_aadhaar'],
            "latlong" => $request['latlong'],
            "notify_customer" => "0",
            "piddata" => $request['pid'],
            "sourceip" => $request->ip()
        ];

        /*---------------------------Send Data------------------------------*/

        $response = Http::withHeaders(array_merge($this->headerArray(), [
            'Content-Type' => 'application/json',
            'request_hash' => $encryption['request_hash']
        ]))->post('https://staging.eko.in:25004/ekoapi/v2/aeps', $data);
        $this->apiRecords($data['client_ref_id'], 'eko', $response);
        return $response;
    }

    public function aepsInquiry(Request $request)
    {
        $initiator_id = 9962981729;
        $transaction_id = $request['transction_id'];

        $response = Http::withHeaders(
            $this->headerArray()
        )->get("https://staging.eko.in:25004/ekoapi/v1/transactions/$transaction_id?initiator_id=$initiator_id");
        $this->apiRecords($transaction_id, 'eko', $response);
        return $response;
    }

    public function fundSettlement(Request $request)
    {
        $data = [
            'service_code' => "39",
            'initiator_id' => 7411111111,
            'user_code' => auth()->user()->user_code ??  20310006
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);
        $this->apiRecords($data['user_code'], 'eko', $response);
        return $response;
    }

    public function bankSettlement(Request $request)
    {

        $data = [
            'service_code' => 39,
            'initiator_id' => 7411111111,
            'user_code' => auth()->user()->user_code ?? 20310006,
            'bank_id' => 108,
            'ifsc' => $request['ifsc'],
            'account' => $request['acc_num'],
        ];

        $response = Http::asForm()->withHeaders(
            $this->headerArray()
        )->put("https://staging.eko.in:25004/ekoapi/v1/agent/user_code:{$data['user_code']}/settlementaccount", $data);
        $this->apiRecords($data['user_code'], 'eko', $response);
        return $response;
    }

    public function initiateSettlement(Request $request)
    {
        $usercode = auth()->user()->user_code ?? 99029899;
        $data = [
            'service_code' => 39,
            'initiator_id' => 7411111111,
            'amount' => $request['amount'] ?? 1000,
            'recipient_id' => $request['recipient_id'] ?? 9971412064,
            'payment_mode' => 5,
            'client_ref_id' => "PESA24SET".strtoupper(uniqid().Str::random(10))
        ];

        $response = Http::asForm()->withHeaders(array_merge($this->headerArray() ,[
            'cache-control' => 'no-cache',
        ]))->post("http://staging.eko.in:8080/ekoapi/v1/agent/user_code:$usercode/settlement", $data);
        return $response;
        $this->apiRecords($data['client_ref_id'], 'eko', $response);
    }

}


/**
 * initiate transaction.
 * charge the fixed amount
 * check role and make a transaction of commission
 * check if parent exists
 * assign commission to parents recursively
 * 
 */

// $result = DB::table('users')
//     ->join('package_user', 'users.id', '=', 'package_user.user_id')
//     ->join('packages', 'package_user.package_id', '=', 'packages.id')
//     ->join('package_service', 'packages.id', '=', 'package_service.package_id')
//     ->join('service_user', 'users.id',  '=', 'service_user.user_id')
//     ->join('services', 'package_service.service_id', '=', 'services.id')
//     ->select('package_service.*')
//     ->where(['service_user.user_id' => $user_id, 'service_user.service_id' => $service_id, 'package_service.service_id' => $service_id, 'package_user.user_id' => $user_id])
//     ->get();
// $array = json_decode($result, true);

/*       $aadhar = $request['aadharNo'] ?? 123456789012;
$amount = $request['amount'] ?? 100;
$usercode = 99099211;
$key = "f74c50a1-f705-4634-9cda-30a477df91b7";
$encodedKey = base64_encode($key);
$secret_key_timestamp = round(microtime(true) * 1000);
$signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
$secret_key = base64_encode($signature);
$string = $secret_key_timestamp . $aadhar . $amount . $usercode;
$signature_req_hash = hash_hmac('SHA256', $string, $encodedKey, true);
$request_hash = base64_encode($signature_req_hash);
$public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCXa63O/UXt5S0Vi8DM/PWF4yugx2OcTVbcFPLfXmLm9ClEVJcRuBr7UDHjJ6gZgG/qcVez5r6AfsYl2PtKmYP3mQdbR/BjVOjnrRooXxwyio6DFk4hTTM8fqQGWWNm6XN5XsPK5+qD5Ic/L0vGrS5nMWDwjRt59gzgNMNMpjheBQIDAQAB';
// $decodedKey1 = base64_decode($public_key);

$search = [
    "-----BEGIN PUBLIC KEY-----",
    'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCXa63O/UXt5S
        0Vi8DM/PWF4yugx2OcTVbcFPLfXmLm9ClEVJcRuBr7UDHjJ6gZgG
        /qcVez5r6AfsYl2PtKmYP3mQdbR/BjVOjnrRooXxwyio6DFk4hTT
        M8fqQGWWNm6XN5XsPK5+qD5Ic/L0vGrS5nMWDwjRt59gzgNMNMpjheBQIDAQAB',
    "-----END PUBLIC KEY-----",
    "\n",
    "\r",
    "\r\n"
];
$public_key_resource = $search[0] . PHP_EOL . wordwrap($public_key, 64, "\n", true) . PHP_EOL . $search[2];
openssl_public_encrypt($aadhar, $signature_req_hash, $public_key_resource);
$encrypted_aadhar = base64_encode($signature_req_hash);
*/