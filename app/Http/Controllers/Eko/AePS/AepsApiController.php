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
            // 'secret-key' => $secret_key,
            // 'secret-key-timestamp' => $secret_key_timestamp
        ];
    }

    public function pid()
    {
        $pid = "<?xml version='1.0'?>
                <PidData>
                <Resp errCode='0' errInfo='Success.' fCount='1' fType='0' nmPoints='29' qScore='61' />
                <DeviceInfo dpId='MANTRA.MSIPL' rdsId='MANTRA.WIN.001' rdsVer='1.0.6' mi='MFS100' mc='MIIEGDCCAwCgAwIBAgIEAQNmQDANBgkqhkiG9w0BAQsFADCB6jEqMCgGA1UEAxMhRFMgTWFudHJhIFNvZnRlY2ggSW5kaWEgUHZ0IEx0ZCA3MUMwQQYDVQQzEzpCIDIwMyBTaGFwYXRoIEhleGEgb3Bwb3NpdGUgR3VqYXJhdCBIaWdoIENvdXJ0IFMgRyBIaWdod2F5MRIwEAYDVQQJEwlBaG1lZGFiYWQxEDAOBgNVBAgTB0d1amFyYXQxHTAbBgNVBAsTFFRlY2huaWNhbCBEZXBhcnRtZW50MSUwIwYDVQQKExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMQswCQYDVQQGEwJJTjAeFw0yMjEyMjkwNjIxMTlaFw0yMzAxMjgwNjM2MTdaMIGwMSUwIwYDVQQDExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMR4wHAYDVQQLExVCaW9tZXRyaWMgTWFudWZhY3R1cmUxDjAMBgNVBAoTBU1TSVBMMRIwEAYDVQQHEwlBSE1FREFCQUQxEDAOBgNVBAgTB0dVSkFSQVQxCzAJBgNVBAYTAklOMSQwIgYJKoZIhvcNAQkBFhVzdXBwb3J0QG1hbnRyYXRlYy5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDFJQAaWua0qcD6YpL25Xdqfbuj5Nn59tLKr0ESkTyLdxLDjOo6xF93tj8APbjyBIK3lhjx+/VX6wnfTa4X3t+0MieX4mX6i7wbrXtEXr3X8c9+yX6En0dgFOFxeKwdJRiv6Fq0cf+N2X4bzPG+7IRFqsO0NoDqJXV8jhBVNqErau12H+X9uSUmuL+G+9znd+OtOGzk73kQhbpD5uGFaz70yg/Atvi/HuN0OJ5rj71VWcr67BRlrwR89lJg2mKZLEmuEezCqj/dJpg6nvQwSRrCQHoNO6v8A+kO7gPzCBRqjN6+zolKY92QRQDd6N0agP0jlFOVXxbkvfkG8NTOeevBAgMBAAEwDQYJKoZIhvcNAQELBQADggEBAIJfUxB+jRPYULnMqZxpWkNXMbdlwy4NB9X/WqtvnB0uJLHopnQFmKCLOivnpxl7vwbaKgfvcrrt/y+2hOodrMfvnQhiTWyVsbD10Gc0DHro6oATTA3CItCCwmgQU0yHYzC1IaRAZnA3vKq4FNTQg1eAg76ZouIQ2HNRl6niTcrJszmcOBMQPAWRA+oIZkBWmUJsC7uU0c375atwluELAJ9ZIHVAKBDUk/tCdIX78gpvP9rKWctT21hxzlcuY0I7JumcAMfxJhZ3X5VlUd8ecXMtOuJgO7xKd4N0jQS4texZkc5GegD2DMMlWfsW/07Osx1SxcOeBWfbU0WCf64f4Sc=' dc='4da240d5-fb80-492c-9fa2-7493a8868466'>
                    <additional_info>
                    <Param name='srno' value='4904844' />
                    <Param name='sysid' value='651FCBF442F7F1DFBFF0' />
                    <Param name='ts' value='2022-12-29T18:19:09+05:30' />
                    </additional_info>
                </DeviceInfo>
                <Skey ci='20250923'>A+YyFa648DrH+/EOOJPDxtzL+Cna3RmWbGlPLYNmBqDpUsDd368GkQvJGpFj+xJvP22EpEEig1GxWgBYmMtn9lDFZFRCZoN0N+48lPLdLsFrfAcoQOPGUq30NSiF3EizD1vPGVzBOawoerTQjbEBouHdjGk6djnnypBaTPhtJcp9IreTqbjYMaVcOLOj9gcjPDk7skR+fnIFp+iGLuKiJ/hifh3NxZFbTNFAb+pdSDOV3oczKnH56Oy5nmtqXpxLy5eytri93jG83hbX63y4ypGDJx/91Zxqgza1qdksVyj9N7O982vJF8fBawvPJ3HOHLBbdNNdrOioXRwWXu1TKg==</Skey>
                <Hmac>YgYoK0WegdPcwFRUcTR3DT/eAqHwsxydgLcOGzLGA9fzQuXpie4uSkuqENComHU1</Hmac>
                <Data type='X'>MjAyMi0xMi0yOVQxODoxOTowOe3S/UkQT93TW9IJgypTv1QlErT35fWfG/Ajwr2odnAiRZMCL+8lp4ZpGlBHUtlMtRv0OoD9UJ2ZXD49uYsh5ho7f/n8KPNyAqhacyCTG+WJrRK0Td3m8MxOOVfprQ0PtdRxDLgh1k6Cg84/oim2/qK0mltC2o55TKbEvIVsUAfCBAubTsTHoIcQ0YbukkAg/2jqPDIYTv/K+3G5R6vitU6RTiW1vk6Pmz+3CtIUaAL9OqPvdooqgEZHeV77ek0UlTiXRNDsRQAZdpYBGMq18osc9FZcurumGBcotbebqZqPYLrPH0sVbvNKsbP+ItuKbHr9V6ZCw0yqIKzb2XyK70yBohBvOznP0AaogVzTKEBwFLKV7vX3++2W2BEgUxjbPEi+NBg5+HbJE203IeyrwLhA/X129UXolGB2PSF8E1pmBCxv+fssmNj9OhdZubzI4EuJdfhdos/P5cp2qn8C7sxepSNjF8hbvtQS0GDndUqJnqJsLz8X99skZwm6RXviPUtItR4Zj1gYB2GVC9IVot/ghKizQivDVx+yw6GUTx8V8txGFvQsq+g+5F8Kzl3FlSx4ccUZU01ECDtw1OHEiHDj9VZVnTm86dYNejR76lXCpqAJb197MmaUlPlqNFV7HymExEO/Cf1edSRr/wnLYAt3gamPCjw4UC/9ikkcev5b56XpsUSJRx9metAiEiSmil9JiWACsewgn7ntipFEUxm+nZN7EW4zTakpt+YIWvx+j9qEcS2ShiUWBmkjVyRWnVazA3uEb/yrEFB7HinjA3P4fDw1sqRs4TXi6t1ioXbeYQa5TzSz9KcC7kcjDMCEX9fcv17o+r5/KRtd08nOfnDx7vTEal2kyTlXmaLuRoD09ljQcY5poWPLUFx6Azb0h5jlKjxt7EBMkVYYSAtRYWKKsCfKHnGEuzlmRHQ7IGkraWzmEQWFzkmxFXrMbjOxyr57sXvvZivrnJygBRZWmW+e6jILkvJqoRu6jMKhvA0rRn637neO0UmfA1SR1UhXBCC7yS8WxGvZqfqe8ycqdNSh0I2ZnpIbf0mTjtYHYone+CzNUeDYKzk+NsrgHOLFrj8JcOM=</Data>
                </PidData>";

        return $pid;
    }

    public function requestHash(Request $request)
    {
        $aadhar = $request['aadhaarNo'] ?? 715547838073;
        $amount = $request['amount'] ?? 0;
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
            "user_code" => $encryption['user_code'] ?? 20810200,
            "customer_id" => $request['customerId'] ?? 9971412064,
            "bank_code" => $request['bankCode'] ?? 'HDFC',
            "amount" => $encryption['amount'] ?? 100,
            "client_ref_id" => "PESA24AEPS" . strtoupper(uniqid()),
            "pipe" => "0",
            "aadhar" => $encryption['encrypted_aadhaar'],
            "latlong" => "81,81,12",
            "notify_customer" => "0",
            "piddata" => $request['pid'] ?? $this->pid(),
            "sourceip" => $request->ip()
        ];

        /*---------------------------Hit EKO api------------------------------*/

        $response = Http::withHeaders(array_merge($this->headerArray(), [
            'Content-Type' => 'application/json',
            // 'request_hash' => $encryption['request_hash']
        ]))->post('http://staging.eko.in:8080/ekoapi/v2/aeps', $data);

        $this->apiRecords($data['client_ref_id'], 'eko', $response);
        if ($response['status'] == 0) {
            $transaction_id = "AEP" . strtoupper(Str::random(5));
            $opening_balance = auth()->user()->wallet;
            $closing_balance = $opening_balance + $encryption['amount'];

            $metadata = [
                'status' => true,
                'amount' => $encryption['amount'],
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'user_id' => auth()->user()->id,
                'message' => $response['message'],
                'reference_id' => $data['client_ref_id']
            ];
            $this->transaction($encryption['amount'], 'AePS: Withdrawal', 'aeps-cw', auth()->user()->id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata));
            $this->aepsComission($encryption['amount'], auth()->user()->id);

        } else {
            $metadata = [
                'status' => false,
                'Amount' => $encryption['amount'],
                'User Name' => auth()->user()->name,
                'User ID' => auth()->user()->id,
                'Message' => $response['message']
            ];
        }

        return response(['metadata' => $metadata]);
    }

    public function  miniStatement(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/
        $encryption = $this->requestHash($request);
        /*--------------------------------Data------------------------------------ */

        $data = [
            "service_type" => "4",
            "initiator_id" => 9962981729,
            "user_code" => $encryption['user_code'] ?? 20810200,
            "customer_id" => $request['customerId'] ?? 9971412064,
            "bank_code" => $request['bankCode'] ?? 'HDFC',
            "amount" => $encryption['amount'] ?? 0,
            "client_ref_id" => "PESA24AEPSM" . strtoupper(uniqid()),
            "pipe" => "0",
            "aadhar" => $encryption['encrypted_aadhaar'],
            "latlong" => $request['latlong'] ?? "81,81,12",
            "notify_customer" => "0",
            "piddata" => $request['pid'] ?? $this->pid(),
            "sourceip" => $request->ip()
        ];

        /*---------------------------Send Data------------------------------*/

        $response = Http::withHeaders(array_merge($this->headerArray(), [
            'Content-Type' => 'application/json',
            // 'request_hash' => $encryption['request_hash']
        ]))->post('http://staging.eko.in:8080/ekoapi/v2/aeps', $data);
        // $this->apiRecords($data['client_ref_id'], 'eko', $response);

        if ($response['status'] == 0) {
            $metadata = [
                'status' => true,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'bank_ref_num' => $response['data']['bank_ref_num'],
                'mini_statment' => $response['data']['mini_statement_list'],
                'message' => $response['message']
            ];
            $this->aepsMiniComission(auth()->user()->id);
        } else {
            $metadata = [
                'status' => false,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message']
            ];
        }
        return response(['metadata' => $metadata]);
    }


    public function balanceEnquiry(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/

        $encryption = $this->requestHash($request);

        /*--------------------------------Data------------------------------------ */

        $data = [
            "service_type" => "3",
            "initiator_id" => 9962981729,
            "user_code" => $encryption['user_code'] ?? 20810200,
            "customer_id" => $request['customerId'] ?? 9971412064,
            "bank_code" => $request['bankCode'] ?? 'HDFC',
            "amount" => $encryption['amount'],
            "client_ref_id" => "PESA24AEPSB" . strtoupper(uniqid()),
            "pipe" => "0",
            "aadhar" => $encryption['encrypted_aadhaar'],
            "latlong" => $request['latlong'] ?? "81,81,12",
            "notify_customer" => "0",
            "piddata" => $request['pid'] ?? $this->pid(),
            "sourceip" => $request->ip()
        ];

        /*---------------------------Send Data------------------------------*/

        $response = Http::withHeaders(array_merge($this->headerArray(), [
            'Content-Type' => 'application/json',
            // 'request_hash' => $encryption['request_hash']
        ]))->post('http://staging.eko.in:8080/ekoapi/v2/aeps', $data);
        if ($response['status'] == 0) {
            $metadata = [
                'status' => true,
                'customer_balance' => $response['data']['customer_balance'],
                'bank_ref_num' => $response['data']['bank_ref_num'],
                'aadhar' => $response['data']['aadhar'],
                // 'merchantname' => $response['data']['merchantname'],
                'message' => $response['message'],
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,

            ];
        } else {
            $metadata = [
                'status' => false,
                'message' => $response['message'],
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
            ];
        }
        return response(['metadata' => $metadata]);
        $this->apiRecords($data['client_ref_id'], 'eko', $response);
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

    // public function fundSettlement(Request $request)
    // {
    //     $data = [
    //         'service_code' => "39",
    //         'initiator_id' => 7411111111,
    //         'user_code' => auth()->user()->user_code ??  20810200
    //     ];

    //     $response = Http::asForm()->withHeaders(
    //         $this->headerArray()
    //     )->put('http://staging.eko.in:8080/ekoapi/v1/user/service/activate', $data);
    //     return $response;
    //     $this->apiRecords($data['user_code'], 'eko', $response);
    // }

    public function bankSettlement(Request $request)
    {

        $initiator_id = 7411111111;

        $data = [
            'service_code' => 39,
            'initiator_id' => $initiator_id,
            // 'user_code' => auth()->user()->user_code ?? 20310006,
            'bank_id' => 108,
            'ifsc' => $request['ifsc'] ?? 'SBIN0000001',
            'account' => $request['acc_num'] ?? 34567891238,
        ];

        $user_code = 20810200;

        $response = Http::asForm()->withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567'
        ])->put("http://staging.eko.in:8080/ekoapi/v1/agent/user_code:$user_code/settlementaccount", $data);
        return $response;
        $this->apiRecords($data['user_code'], 'eko', $response);
    }

    public function getSttlmentAccount(Request $request)
    {
        $initiator_id = 7411111111;
        $data = [
            'service_code' => 39,
            'initiator_id' => $initiator_id,
        ];
        $user_code = 20810200;
        $response = Http::asForm()->withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567'
        ])->get("http://staging.eko.in:8080/ekoapi/v1/agent/user_code:$user_code/settlementaccounts", $data);

        return $response;
    }

    public function initiateSettlement(Request $request)
    {
        $usercode = auth()->user()->user_code ?? 20810200;
        $data = [
            'service_code' => 39,
            'initiator_id' => 7411111111,
            'amount' => $request['amount'] ?? 100,
            'recipient_id' => $request['recipient_id'] ?? 9971412064,
            'payment_mode' => 5,
            'client_ref_id' => "PESA24SET" . strtoupper(uniqid() . Str::random(10))
        ];

        $response = Http::asForm()->withHeaders(array_merge($this->headerArray(), [
            'cache-control' => 'no-cache',
        ]))->post("http://staging.eko.in:8080/ekoapi/v1/agent/user_code:$usercode/settlement", $data);
        return $response;
        $this->apiRecords($data['client_ref_id'], 'eko', $response);
    }

    public function bankList()
    {
        $data = DB::table('eko_banks_list')->get(['bank_id', 'name', 'short_code']);
        return $data;
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