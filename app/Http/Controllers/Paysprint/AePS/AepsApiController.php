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


        $pid = $request['pid'] ?? "<?xml version='1.0'?>
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
        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'referenceno' => "PS24APB" . time() . Str::random(4),
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

        if ($response['status'] == "true") {
            $metadata = [
                'status' => true,
                'mobile_number' => $request['customerId'],
                'adhaar_number' => $request['aadhaarNo'],
                'amount' => $response['amount'] ?? "Could not fetch.",
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'message' => $response['message'] ?? "Transaction Failed",
                'user_phone' => auth()->user()->phone_number,
            ];

            $this->transaction(0, "Balance enquiry for {$metadata['mobile_number']}", 'aeps-be', auth()->user()->id, auth()->user()->wallet, $data['referenceno'], auth()->user()->wallet, $response->json(), 0);
        } else {
            $metadata = [
                'status' => false,
                'amount' => $response['amount'] ?? "Could not fetch.",
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'message' => $response['message'] ?? "Transaction Failed",
                'user_phone' => auth()->user()->phone_number,
            ];
        }
        return ['metadata' => $metadata];
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

        $pid = $request['pid'] ?? "<?xml version='1.0'?>
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

        $latlong = explode(",", $request['latlong']);
        $transaction_id = "PS24APC" . time() . strtoupper(Str::random(4));
        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => $request['customerId'],
            'referenceno' => $transaction_id,
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
            $metadata = [
                'status' => $response['status'],
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'] ?? "Transaction Failed",
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'aadhaar_number' => $request['aadhaarNo'],
                'bank_code' => $request['bankCode'],
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            $balance_left = $walletAmt[0] + $data['amount'];

            $this->transaction(0, "AePS withdrawal for {$data['mobilenumber']}", 'aeps-cw', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata), $data['amount']);
            $this->aepsComission($data['amount'], auth()->user()->id);
        } else {
            $metadata = [
                'status' => false,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'] ?? "Transaction Failed",
                'amount' => $data['amount'],
                'aadhaar_number' => $request['aadhaarNo'],
                'bank_code' => $request['bankCode'],
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


        $pid = $request['pid'] ?? "<?xml version='1.0'?>
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

        $latlong = explode(",", $request['latlong']);

        $transaction_id = "PS24APM" . time() . Str::random(4);
        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => $request['customerId'],
            'referenceno' => $transaction_id,
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
        $this->apiRecords($data['referenceno'], 'paysprint', $response);
        $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
        $balance_left = $walletAmt[0] + $data['amount'];

        if ($response['response_code'] == 24) {
            return $this->onboard();
        }

        if ($response['status'] == true && $response['response_code'] == 1) {
            $metadata = [
                'status' => $response['status'],
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'] ?? "Transaction Failed",
                'aadhaar_number' => $request['aadhaarNo'],
                'amount' => $data['amount'],
                'bankrrn' => $response['bankrrn'],
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'reference_id' => $data['referenceno'],
                'acknowldgement_number' => $response['ackno'],
            ];

            $this->transaction(0, "AePS Mini Statement for {$data['mobilenumber']}", 'aeps-ms', auth()->user()->id, $walletAmt[0], $transaction_id, $balance_left, json_encode($metadata), $data['amount']);
            $this->aepsMiniComission(auth()->user()->id, $data['mobilenumber']);
        } else {
            $metadata = [
                'status' => false,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'] ?? "Transaction Failed",
                'transaction_id' => $transaction_id,
                'aadhaar_number' => $request['aadhaarNo'],
                'created_at' => date("F j, Y, g:i a"),
                'reference_id' => $data['referenceno'],
                'mobile_number' => $data['mobilenumber'],
            ];
            $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            // $this->transaction(0, "Mini Statement for {$data['mobilenumber']}", 'aeps', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));
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

        $pid = $request['pid'] ?? "<?xml version='1.0'?>
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

        $latlong = explode(",", $request['latlong']);
        $transaction_id = "PS24ADP" . time() . Str::random(4);
        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => $request['customerId'],
            'referenceno' => $transaction_id,
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

        // if ($response['response_code'] == 24) {
        //     return $this->onboard();
        // }

        if ($response['status'] == true && $response['response_code'] == 1) {

            $metadata = [
                'status' => $response['status'],
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'message' => $response['message'] ?? "Transaction Failed",
                'adhaar_number' => $request['aadhaarNo'],
                'amount' => $data['amount'],
                'bank_code' => $request['bankCode'],
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
            $metadata = [
                'status' => false,
                'user' => auth()->user()->name,
                'user_id' => auth()->user()->id,
                'user_phone' => auth()->user()->phone_number,
                'bank_code' => $request['bankCode'],
                'adhaar_number' => $request['aadhaarNo'],
                'transaction_id' => $transaction_id,
                'created_at' => date("F j, Y, g:i a"),
                'event' => 'aadhar-pay',
                'message' => $response['message'] ?? "Transaction Failed",
                'reference_id' => $data['referenceno'],
                'mobile_number' => $data['mobilenumber'],
            ];
            // $walletAmt = DB::table('users')->where('id', auth()->user()->id)->pluck('wallet');
            // $this->transaction(0, "Aadhaar Pay {$data['mobilenumber']}", 'aeps-ap', auth()->user()->id, $walletAmt[0], $transaction_id, $walletAmt[0], json_encode($metadata));
        }
        // $this->aepsCommssion($data['amount'], auth()->user()->id);
        return [$response->object(), 'metadata' => $metadata];
    }

    public function twoFactorReg(Request $request)
    {
        if (is_null(auth()->user()->phone_number) || is_null(auth()->user()->aadhaar)) {
            return response("Profile incomplete", 400);
        }
        $key = env('AES_ENCRYPTION_KEY');
        $iv = env('AES_ENCRYPTION_IV');

        $pid = $request['pid'] ?? "<?xml version='1.0'?>
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

        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => auth()->user()->phone_number,
            'referenceno' => uniqid("PS24-APREG"),
            'ipaddress' => $request->ip(),
            'adhaarnumber' => auth()->user()->aadhaar,
            'accessmodetype' => 'SITE',
            'data' => $pid,
            'timestamp' => time('Y-m-d H:i:s'),
            'submerchantid' => "PS24MCH" . Str::random(4) . auth()->user()->id,
        ];
        $token = $this->token();
        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://api.paysprint.in/api/v1/service/aeps/kyc/Twofactorkyc/registration', ['body' => $body]);

        if ($response['response_code'] == 1) {
            DB::table('users')->update([
                'paysprint_merchant' => $data['submerchantid'],
                'updated_at' => now()
            ]);
            return $data['submerchantid'];
        }

        return response($response->json());
    }

    public function twoFactorAuth(Request $request)
    {
        $key = env('AES_ENCRYPTION_KEY');
        $iv = env('AES_ENCRYPTION_IV');

        $pid = $request['pid'] ?? "<?xml version='1.0'?>
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

        $latlong = explode(",", $request['latlong']);

        $data = [
            'latitude' => $latlong[0],
            'longitude' => $latlong[1],
            'mobilenumber' => auth()->user()->phone_number,
            'referenceno' => uniqid("PS24-AP2FA"),
            'ipaddress' => $request->ip(),
            'adhaarnumber' => auth()->user()->aadhaar,
            'accessmodetype' => 'SITE',
            'data' => $pid,
            'timestamp' => time('Y-m-d H:i:s'),
            'submerchantid' => auth()->user()->paysprint_merchant,
        ];
        $token = $this->token();
        $cipher = openssl_encrypt(json_encode($data, true), 'AES-128-CBC', $key, $options = OPENSSL_RAW_DATA, $iv);
        $body = base64_encode($cipher);
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Token' => $token,
            'accept' => 'application/json',
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://api.paysprint.in/api/v1/service/aeps/kyc/Twofactorkyc/authentication', ['body' => $body]);


        return response($response->json());
    }
}
