<?php

namespace App\Http\Controllers\Eko\AePS;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AepsApiController extends Controller
{
    public function moneyTransfer(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/
        $aadhar = $request['aadhar'] ?? 715547838073;
        $amount = $request['amount'] ?? 1000;
        $usercode = Auth::user()->user_code ?? 20810200;
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

        /*--------------------------------Data------------------------------------ */

            $data = [
                "service_type" => "2",
                "initiator_id" => 9962981729,
                "user_code" => $usercode,
                "customer_id" => $request['values.customer_id'] ?? 9971412064,
                "bank_code" => $request['values.bankCode'] ?? 'HDFC',
                "amount" => $amount,
                "client_ref_id" => strtoupper(uniqid()),
                "pipe" => "0",
                "aadhar" => $encrypted_aadhar,
                "latlong" => "81,81,12",
                "notify_customer" => "0",
                "piddata" => $request['values.pid'] ?? '<PidData>
                <Resp errCode="0" errInfo="Success." fCount="1" fType="0" nmPoints="29" qScore="61" />
                <DeviceInfo dpId="MANTRA.MSIPL" rdsId="MANTRA.WIN.001" rdsVer="1.0.6" mi="MFS100" mc="MIIEGDCCAwCgAwIBAgIEAQNmQDANBgkqhkiG9w0BAQsFADCB6jEqMCgGA1UEAxMhRFMgTWFudHJhIFNvZnRlY2ggSW5kaWEgUHZ0IEx0ZCA3MUMwQQYDVQQzEzpCIDIwMyBTaGFwYXRoIEhleGEgb3Bwb3NpdGUgR3VqYXJhdCBIaWdoIENvdXJ0IFMgRyBIaWdod2F5MRIwEAYDVQQJEwlBaG1lZGFiYWQxEDAOBgNVBAgTB0d1amFyYXQxHTAbBgNVBAsTFFRlY2huaWNhbCBEZXBhcnRtZW50MSUwIwYDVQQKExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMQswCQYDVQQGEwJJTjAeFw0yMjEyMjkwNjIxMTlaFw0yMzAxMjgwNjM2MTdaMIGwMSUwIwYDVQQDExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMR4wHAYDVQQLExVCaW9tZXRyaWMgTWFudWZhY3R1cmUxDjAMBgNVBAoTBU1TSVBMMRIwEAYDVQQHEwlBSE1FREFCQUQxEDAOBgNVBAgTB0dVSkFSQVQxCzAJBgNVBAYTAklOMSQwIgYJKoZIhvcNAQkBFhVzdXBwb3J0QG1hbnRyYXRlYy5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDFJQAaWua0qcD6YpL25Xdqfbuj5Nn59tLKr0ESkTyLdxLDjOo6xF93tj8APbjyBIK3lhjx+/VX6wnfTa4X3t+0MieX4mX6i7wbrXtEXr3X8c9+yX6En0dgFOFxeKwdJRiv6Fq0cf+N2X4bzPG+7IRFqsO0NoDqJXV8jhBVNqErau12H+X9uSUmuL+G+9znd+OtOGzk73kQhbpD5uGFaz70yg/Atvi/HuN0OJ5rj71VWcr67BRlrwR89lJg2mKZLEmuEezCqj/dJpg6nvQwSRrCQHoNO6v8A+kO7gPzCBRqjN6+zolKY92QRQDd6N0agP0jlFOVXxbkvfkG8NTOeevBAgMBAAEwDQYJKoZIhvcNAQELBQADggEBAIJfUxB+jRPYULnMqZxpWkNXMbdlwy4NB9X/WqtvnB0uJLHopnQFmKCLOivnpxl7vwbaKgfvcrrt/y+2hOodrMfvnQhiTWyVsbD10Gc0DHro6oATTA3CItCCwmgQU0yHYzC1IaRAZnA3vKq4FNTQg1eAg76ZouIQ2HNRl6niTcrJszmcOBMQPAWRA+oIZkBWmUJsC7uU0c375atwluELAJ9ZIHVAKBDUk/tCdIX78gpvP9rKWctT21hxzlcuY0I7JumcAMfxJhZ3X5VlUd8ecXMtOuJgO7xKd4N0jQS4texZkc5GegD2DMMlWfsW/07Osx1SxcOeBWfbU0WCf64f4Sc=" dc="4da240d5-fb80-492c-9fa2-7493a8868466">
                <additional_info>
                <Param name="srno" value="4904844" />
                <Param name="sysid" value="651FCBF442F7F1DFBFF0" />
                <Param name="ts" value="2022-12-29T18:19:09+05:30" />
                </additional_info>
                </DeviceInfo>
                <Skey ci="20250923">A+YyFa648DrH+/EOOJPDxtzL+Cna3RmWbGlPLYNmBqDpUsDd368GkQvJGpFj+xJvP22EpEEig1GxWgBYmMtn9lDFZFRCZoN0N+48lPLdLsFrfAcoQOPGUq30NSiF3EizD1vPGVzBOawoerTQjbEBouHdjGk6djnnypBaTPhtJcp9IreTqbjYMaVcOLOj9gcjPDk7skR+fnIFp+iGLuKiJ/hifh3NxZFbTNFAb+pdSDOV3oczKnH56Oy5nmtqXpxLy5eytri93jG83hbX63y4ypGDJx/91Zxqgza1qdksVyj9N7O982vJF8fBawvPJ3HOHLBbdNNdrOioXRwWXu1TKg==</Skey>
                <Hmac>YgYoK0WegdPcwFRUcTR3DT/eAqHwsxydgLcOGzLGA9fzQuXpie4uSkuqENComHU1</Hmac>
                <Data type="X">MjAyMi0xMi0yOVQxODoxOTowOe3S/UkQT93TW9IJgypTv1QlErT35fWfG/Ajwr2odnAiRZMCL+8lp4ZpGlBHUtlMtRv0OoD9UJ2ZXD49uYsh5ho7f/n8KPNyAqhacyCTG+WJrRK0Td3m8MxOOVfprQ0PtdRxDLgh1k6Cg84/oim2/qK0mltC2o55TKbEvIVsUAfCBAubTsTHoIcQ0YbukkAg/2jqPDIYTv/K+3G5R6vitU6RTiW1vk6Pmz+3CtIUaAL9OqPvdooqgEZHeV77ek0UlTiXRNDsRQAZdpYBGMq18osc9FZcurumGBcotbebqZqPYLrPH0sVbvNKsbP+ItuKbHr9V6ZCw0yqIKzb2XyK70yBohBvOznP0AaogVzTKEBwFLKV7vX3++2W2BEgUxjbPEi+NBg5+HbJE203IeyrwLhA/X129UXolGB2PSF8E1pmBCxv+fssmNj9OhdZubzI4EuJdfhdos/P5cp2qn8C7sxepSNjF8hbvtQS0GDndUqJnqJsLz8X99skZwm6RXviPUtItR4Zj1gYB2GVC9IVot/ghKizQivDVx+yw6GUTx8V8txGFvQsq+g+5F8Kzl3FlSx4ccUZU01ECDtw1OHEiHDj9VZVnTm86dYNejR76lXCpqAJb197MmaUlPlqNFV7HymExEO/Cf1edSRr/wnLYAt3gamPCjw4UC/9ikkcev5b56XpsUSJRx9metAiEiSmil9JiWACsewgn7ntipFEUxm+nZN7EW4zTakpt+YIWvx+j9qEcS2ShiUWBmkjVyRWnVazA3uEb/yrEFB7HinjA3P4fDw1sqRs4TXi6t1ioXbeYQa5TzSz9KcC7kcjDMCEX9fcv17o+r5/KRtd08nOfnDx7vTEal2kyTlXmaLuRoD09ljQcY5poWPLUFx6Azb0h5jlKjxt7EBMkVYYSAtRYWKKsCfKHnGEuzlmRHQ7IGkraWzmEQWFzkmxFXrMbjOxyr57sXvvZivrnJygBRZWmW+e6jILkvJqoRu6jMKhvA0rRn637neO0UmfA1SR1UhXBCC7yS8WxGvZqfqe8ycqdNSh0I2ZnpIbf0mTjtYHYone+CzNUeDYKzk+NsrgHOLFrj8JcOM=</Data>
                </PidData>',
                "sourceip" => $request->ip()
            ];

        /*---------------------------Hit EKO api------------------------------*/

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
            'request_hash' => $request_hash
        ])->post('https://staging.eko.in:25004/ekoapi/v2/aeps', $data);

        /*--------------------------------Store Response--------------------------------*/


            DB::insert(
                'insert into ae_p_s_transactions (user_id, shop, service_tax, total_fee, stan, tid, client_ref_id,
                customer_id, merchant_code, merchant_name, customer_balance, sender_name, auth_code, bank_ref_num, terminal_id,
                amount, tx_status, transaction_date, aadhar, response_type_id, reason, comment, message, status) values [?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?]',
                [
                    auth()->id, $response->json($key = 'data')['shop'], $response->json($key = 'data')['service_tax'], $response->json($key = 'data')['total_fee'],
                    $response->json($key = 'data')['stan'], $response->json($key = 'data')['tid'], $data['client_ref_id'], $data['customer_id'], $response->json($key = 'data')['merchant_code'],
                    $response->json($key = 'data')['merchantname'], $response->json($key = 'data')['customer_balance'], $response->json($key = 'data')['sender_name'], $response->json($key = 'data')['auth_code'],
                    $response->json($key = 'data')['bank_ref_num'], $response->json($key = 'data')['terminal_id'], $response->json($key = 'data')['amount'], $response->json($key = 'data')['tx_status'], $response->json($key = 'data')['transaction_date'],
                    $response->json($key = 'data')['aadhar'], $response->json($key = 'response_type_id'), $response->json($key = 'data')['reason'], $response->json($key = 'data')['comment'], $response->json($key = 'message'),
                    $response->json($key = 'status')
                ]
            );

            $id = DB::insertGetId(
                'insert into transactions (user_id, transaction_for, credit_amount, debit_amount, opening_balance, balance_left, commission, transaction_id, is_flat, created_at, updated_at) values [?,?,?,?,?,?,?,?,?,?,?]',
                [
                    auth()->user()->id, 'AePS: Money Transfer', $data['amount'],
                ]
            );

            $this->commission('aeps', $data['amount'], $id);

        return $response;
    }

    public function miniStatement(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/

        $aadhar = $request->input('aadhar');
        $amount = 0;
        $usercode = auth()->user()->user_code;
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $string = $secret_key_timestamp . $aadhar . $amount . $usercode;
        $signature_req_hash = hash_hmac('SHA256', $string, $encodedKey, true);
        $request_hash = base64_encode($signature_req_hash);
        $public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCXa63O/UXt5S0Vi8DM/PWF4yugx2OcTVbcFPLfXmLm9ClEVJcRuBr7UDHjJ6gZgG/qcVez5r6AfsYl2PtKmYP3mQdbR/BjVOjnrRooXxwyio6DFk4hTTM8fqQGWWNm6XN5XsPK5+qD5Ic/L0vGrS5nMWDwjRt59gzgNMNMpjheBQIDAQAB';
        $decodedKey1 = base64_decode($public_key);

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

        /*--------------------------------Data------------------------------------ */

        $data = [
            "service_type" => "4",
            "initiator_id" => 9962981729,
            "user_code" => $usercode,
            "customer_id" => $request->input('customer_id'),
            "bank_code" => $request->input('bank'),
            "amount" => $amount,
            "client_ref_id" => "202105311125123456",
            "pipe" => "0",
            "aadhar" => $encrypted_aadhar,
            "latlong" => "81,81,12",
            "notify_customer" => "0",
            "piddata" => '<PidData>
                        <Resp errCode="0" errInfo="Success." fCount="1" fType="0" nmPoints="29" qScore="61" />
                        <DeviceInfo dpId="MANTRA.MSIPL" rdsId="MANTRA.WIN.001" rdsVer="1.0.6" mi="MFS100" mc="MIIEGDCCAwCgAwIBAgIEAQNmQDANBgkqhkiG9w0BAQsFADCB6jEqMCgGA1UEAxMhRFMgTWFudHJhIFNvZnRlY2ggSW5kaWEgUHZ0IEx0ZCA3MUMwQQYDVQQzEzpCIDIwMyBTaGFwYXRoIEhleGEgb3Bwb3NpdGUgR3VqYXJhdCBIaWdoIENvdXJ0IFMgRyBIaWdod2F5MRIwEAYDVQQJEwlBaG1lZGFiYWQxEDAOBgNVBAgTB0d1amFyYXQxHTAbBgNVBAsTFFRlY2huaWNhbCBEZXBhcnRtZW50MSUwIwYDVQQKExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMQswCQYDVQQGEwJJTjAeFw0yMjEyMjkwNjIxMTlaFw0yMzAxMjgwNjM2MTdaMIGwMSUwIwYDVQQDExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMR4wHAYDVQQLExVCaW9tZXRyaWMgTWFudWZhY3R1cmUxDjAMBgNVBAoTBU1TSVBMMRIwEAYDVQQHEwlBSE1FREFCQUQxEDAOBgNVBAgTB0dVSkFSQVQxCzAJBgNVBAYTAklOMSQwIgYJKoZIhvcNAQkBFhVzdXBwb3J0QG1hbnRyYXRlYy5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDFJQAaWua0qcD6YpL25Xdqfbuj5Nn59tLKr0ESkTyLdxLDjOo6xF93tj8APbjyBIK3lhjx+/VX6wnfTa4X3t+0MieX4mX6i7wbrXtEXr3X8c9+yX6En0dgFOFxeKwdJRiv6Fq0cf+N2X4bzPG+7IRFqsO0NoDqJXV8jhBVNqErau12H+X9uSUmuL+G+9znd+OtOGzk73kQhbpD5uGFaz70yg/Atvi/HuN0OJ5rj71VWcr67BRlrwR89lJg2mKZLEmuEezCqj/dJpg6nvQwSRrCQHoNO6v8A+kO7gPzCBRqjN6+zolKY92QRQDd6N0agP0jlFOVXxbkvfkG8NTOeevBAgMBAAEwDQYJKoZIhvcNAQELBQADggEBAIJfUxB+jRPYULnMqZxpWkNXMbdlwy4NB9X/WqtvnB0uJLHopnQFmKCLOivnpxl7vwbaKgfvcrrt/y+2hOodrMfvnQhiTWyVsbD10Gc0DHro6oATTA3CItCCwmgQU0yHYzC1IaRAZnA3vKq4FNTQg1eAg76ZouIQ2HNRl6niTcrJszmcOBMQPAWRA+oIZkBWmUJsC7uU0c375atwluELAJ9ZIHVAKBDUk/tCdIX78gpvP9rKWctT21hxzlcuY0I7JumcAMfxJhZ3X5VlUd8ecXMtOuJgO7xKd4N0jQS4texZkc5GegD2DMMlWfsW/07Osx1SxcOeBWfbU0WCf64f4Sc=" dc="4da240d5-fb80-492c-9fa2-7493a8868466">
                        <additional_info>
                        <Param name="srno" value="4904844" />
                        <Param name="sysid" value="651FCBF442F7F1DFBFF0" />
                        <Param name="ts" value="2022-12-29T18:19:09+05:30" />
                        </additional_info>
                        </DeviceInfo>
                        <Skey ci="20250923">A+YyFa648DrH+/EOOJPDxtzL+Cna3RmWbGlPLYNmBqDpUsDd368GkQvJGpFj+xJvP22EpEEig1GxWgBYmMtn9lDFZFRCZoN0N+48lPLdLsFrfAcoQOPGUq30NSiF3EizD1vPGVzBOawoerTQjbEBouHdjGk6djnnypBaTPhtJcp9IreTqbjYMaVcOLOj9gcjPDk7skR+fnIFp+iGLuKiJ/hifh3NxZFbTNFAb+pdSDOV3oczKnH56Oy5nmtqXpxLy5eytri93jG83hbX63y4ypGDJx/91Zxqgza1qdksVyj9N7O982vJF8fBawvPJ3HOHLBbdNNdrOioXRwWXu1TKg==</Skey>
                        <Hmac>YgYoK0WegdPcwFRUcTR3DT/eAqHwsxydgLcOGzLGA9fzQuXpie4uSkuqENComHU1</Hmac>
                        <Data type="X">MjAyMi0xMi0yOVQxODoxOTowOe3S/UkQT93TW9IJgypTv1QlErT35fWfG/Ajwr2odnAiRZMCL+8lp4ZpGlBHUtlMtRv0OoD9UJ2ZXD49uYsh5ho7f/n8KPNyAqhacyCTG+WJrRK0Td3m8MxOOVfprQ0PtdRxDLgh1k6Cg84/oim2/qK0mltC2o55TKbEvIVsUAfCBAubTsTHoIcQ0YbukkAg/2jqPDIYTv/K+3G5R6vitU6RTiW1vk6Pmz+3CtIUaAL9OqPvdooqgEZHeV77ek0UlTiXRNDsRQAZdpYBGMq18osc9FZcurumGBcotbebqZqPYLrPH0sVbvNKsbP+ItuKbHr9V6ZCw0yqIKzb2XyK70yBohBvOznP0AaogVzTKEBwFLKV7vX3++2W2BEgUxjbPEi+NBg5+HbJE203IeyrwLhA/X129UXolGB2PSF8E1pmBCxv+fssmNj9OhdZubzI4EuJdfhdos/P5cp2qn8C7sxepSNjF8hbvtQS0GDndUqJnqJsLz8X99skZwm6RXviPUtItR4Zj1gYB2GVC9IVot/ghKizQivDVx+yw6GUTx8V8txGFvQsq+g+5F8Kzl3FlSx4ccUZU01ECDtw1OHEiHDj9VZVnTm86dYNejR76lXCpqAJb197MmaUlPlqNFV7HymExEO/Cf1edSRr/wnLYAt3gamPCjw4UC/9ikkcev5b56XpsUSJRx9metAiEiSmil9JiWACsewgn7ntipFEUxm+nZN7EW4zTakpt+YIWvx+j9qEcS2ShiUWBmkjVyRWnVazA3uEb/yrEFB7HinjA3P4fDw1sqRs4TXi6t1ioXbeYQa5TzSz9KcC7kcjDMCEX9fcv17o+r5/KRtd08nOfnDx7vTEal2kyTlXmaLuRoD09ljQcY5poWPLUFx6Azb0h5jlKjxt7EBMkVYYSAtRYWKKsCfKHnGEuzlmRHQ7IGkraWzmEQWFzkmxFXrMbjOxyr57sXvvZivrnJygBRZWmW+e6jILkvJqoRu6jMKhvA0rRn637neO0UmfA1SR1UhXBCC7yS8WxGvZqfqe8ycqdNSh0I2ZnpIbf0mTjtYHYone+CzNUeDYKzk+NsrgHOLFrj8JcOM=</Data>
                        </PidData>',
            "sourceip" => $request['api']
        ];

        /*---------------------------Send Data------------------------------*/

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
            'request_hash' => $request_hash
        ])->post('https://staging.eko.in:25004/ekoapi/v2/aeps', $data);

        return $response;
    }


    public function balanceEnquiry(Request $request)
    {
        /*---------------------------------------------Data Encoding---------------------------------------------*/

        $aadhar = $request->input('aadhar');
        $amount = 0;
        $usercode = auth()->user()->user_code;
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $string = $secret_key_timestamp . $aadhar . $amount . $usercode;
        $signature_req_hash = hash_hmac('SHA256', $string, $encodedKey, true);
        $request_hash = base64_encode($signature_req_hash);
        $public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCXa63O/UXt5S0Vi8DM/PWF4yugx2OcTVbcFPLfXmLm9ClEVJcRuBr7UDHjJ6gZgG/qcVez5r6AfsYl2PtKmYP3mQdbR/BjVOjnrRooXxwyio6DFk4hTTM8fqQGWWNm6XN5XsPK5+qD5Ic/L0vGrS5nMWDwjRt59gzgNMNMpjheBQIDAQAB';
        $decodedKey1 = base64_decode($public_key);

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

        /*--------------------------------Data------------------------------------ */

        $data = [
            "service_type" => "3",
            "initiator_id" => 9962981729,
            "user_code" => $usercode,
            "customer_id" => $request->input('customer_id'),
            "bank_code" => $request->input('bank'),
            "amount" => $amount,
            "client_ref_id" => random_int(100000, 999999),
            "pipe" => "0",
            "aadhar" => $encrypted_aadhar,
            "latlong" => "81,81,12",
            "notify_customer" => "0",
            "piddata" => '<PidData>
                        <Resp errCode="0" errInfo="Success." fCount="1" fType="0" nmPoints="29" qScore="61" />
                        <DeviceInfo dpId="MANTRA.MSIPL" rdsId="MANTRA.WIN.001" rdsVer="1.0.6" mi="MFS100" mc="MIIEGDCCAwCgAwIBAgIEAQNmQDANBgkqhkiG9w0BAQsFADCB6jEqMCgGA1UEAxMhRFMgTWFudHJhIFNvZnRlY2ggSW5kaWEgUHZ0IEx0ZCA3MUMwQQYDVQQzEzpCIDIwMyBTaGFwYXRoIEhleGEgb3Bwb3NpdGUgR3VqYXJhdCBIaWdoIENvdXJ0IFMgRyBIaWdod2F5MRIwEAYDVQQJEwlBaG1lZGFiYWQxEDAOBgNVBAgTB0d1amFyYXQxHTAbBgNVBAsTFFRlY2huaWNhbCBEZXBhcnRtZW50MSUwIwYDVQQKExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMQswCQYDVQQGEwJJTjAeFw0yMjEyMjkwNjIxMTlaFw0yMzAxMjgwNjM2MTdaMIGwMSUwIwYDVQQDExxNYW50cmEgU29mdGVjaCBJbmRpYSBQdnQgTHRkMR4wHAYDVQQLExVCaW9tZXRyaWMgTWFudWZhY3R1cmUxDjAMBgNVBAoTBU1TSVBMMRIwEAYDVQQHEwlBSE1FREFCQUQxEDAOBgNVBAgTB0dVSkFSQVQxCzAJBgNVBAYTAklOMSQwIgYJKoZIhvcNAQkBFhVzdXBwb3J0QG1hbnRyYXRlYy5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDFJQAaWua0qcD6YpL25Xdqfbuj5Nn59tLKr0ESkTyLdxLDjOo6xF93tj8APbjyBIK3lhjx+/VX6wnfTa4X3t+0MieX4mX6i7wbrXtEXr3X8c9+yX6En0dgFOFxeKwdJRiv6Fq0cf+N2X4bzPG+7IRFqsO0NoDqJXV8jhBVNqErau12H+X9uSUmuL+G+9znd+OtOGzk73kQhbpD5uGFaz70yg/Atvi/HuN0OJ5rj71VWcr67BRlrwR89lJg2mKZLEmuEezCqj/dJpg6nvQwSRrCQHoNO6v8A+kO7gPzCBRqjN6+zolKY92QRQDd6N0agP0jlFOVXxbkvfkG8NTOeevBAgMBAAEwDQYJKoZIhvcNAQELBQADggEBAIJfUxB+jRPYULnMqZxpWkNXMbdlwy4NB9X/WqtvnB0uJLHopnQFmKCLOivnpxl7vwbaKgfvcrrt/y+2hOodrMfvnQhiTWyVsbD10Gc0DHro6oATTA3CItCCwmgQU0yHYzC1IaRAZnA3vKq4FNTQg1eAg76ZouIQ2HNRl6niTcrJszmcOBMQPAWRA+oIZkBWmUJsC7uU0c375atwluELAJ9ZIHVAKBDUk/tCdIX78gpvP9rKWctT21hxzlcuY0I7JumcAMfxJhZ3X5VlUd8ecXMtOuJgO7xKd4N0jQS4texZkc5GegD2DMMlWfsW/07Osx1SxcOeBWfbU0WCf64f4Sc=" dc="4da240d5-fb80-492c-9fa2-7493a8868466">
                        <additional_info>
                        <Param name="srno" value="4904844" />
                        <Param name="sysid" value="651FCBF442F7F1DFBFF0" />
                        <Param name="ts" value="2022-12-29T18:19:09+05:30" />
                        </additional_info>
                        </DeviceInfo>
                        <Skey ci="20250923">A+YyFa648DrH+/EOOJPDxtzL+Cna3RmWbGlPLYNmBqDpUsDd368GkQvJGpFj+xJvP22EpEEig1GxWgBYmMtn9lDFZFRCZoN0N+48lPLdLsFrfAcoQOPGUq30NSiF3EizD1vPGVzBOawoerTQjbEBouHdjGk6djnnypBaTPhtJcp9IreTqbjYMaVcOLOj9gcjPDk7skR+fnIFp+iGLuKiJ/hifh3NxZFbTNFAb+pdSDOV3oczKnH56Oy5nmtqXpxLy5eytri93jG83hbX63y4ypGDJx/91Zxqgza1qdksVyj9N7O982vJF8fBawvPJ3HOHLBbdNNdrOioXRwWXu1TKg==</Skey>
                        <Hmac>YgYoK0WegdPcwFRUcTR3DT/eAqHwsxydgLcOGzLGA9fzQuXpie4uSkuqENComHU1</Hmac>
                        <Data type="X">MjAyMi0xMi0yOVQxODoxOTowOe3S/UkQT93TW9IJgypTv1QlErT35fWfG/Ajwr2odnAiRZMCL+8lp4ZpGlBHUtlMtRv0OoD9UJ2ZXD49uYsh5ho7f/n8KPNyAqhacyCTG+WJrRK0Td3m8MxOOVfprQ0PtdRxDLgh1k6Cg84/oim2/qK0mltC2o55TKbEvIVsUAfCBAubTsTHoIcQ0YbukkAg/2jqPDIYTv/K+3G5R6vitU6RTiW1vk6Pmz+3CtIUaAL9OqPvdooqgEZHeV77ek0UlTiXRNDsRQAZdpYBGMq18osc9FZcurumGBcotbebqZqPYLrPH0sVbvNKsbP+ItuKbHr9V6ZCw0yqIKzb2XyK70yBohBvOznP0AaogVzTKEBwFLKV7vX3++2W2BEgUxjbPEi+NBg5+HbJE203IeyrwLhA/X129UXolGB2PSF8E1pmBCxv+fssmNj9OhdZubzI4EuJdfhdos/P5cp2qn8C7sxepSNjF8hbvtQS0GDndUqJnqJsLz8X99skZwm6RXviPUtItR4Zj1gYB2GVC9IVot/ghKizQivDVx+yw6GUTx8V8txGFvQsq+g+5F8Kzl3FlSx4ccUZU01ECDtw1OHEiHDj9VZVnTm86dYNejR76lXCpqAJb197MmaUlPlqNFV7HymExEO/Cf1edSRr/wnLYAt3gamPCjw4UC/9ikkcev5b56XpsUSJRx9metAiEiSmil9JiWACsewgn7ntipFEUxm+nZN7EW4zTakpt+YIWvx+j9qEcS2ShiUWBmkjVyRWnVazA3uEb/yrEFB7HinjA3P4fDw1sqRs4TXi6t1ioXbeYQa5TzSz9KcC7kcjDMCEX9fcv17o+r5/KRtd08nOfnDx7vTEal2kyTlXmaLuRoD09ljQcY5poWPLUFx6Azb0h5jlKjxt7EBMkVYYSAtRYWKKsCfKHnGEuzlmRHQ7IGkraWzmEQWFzkmxFXrMbjOxyr57sXvvZivrnJygBRZWmW+e6jILkvJqoRu6jMKhvA0rRn637neO0UmfA1SR1UhXBCC7yS8WxGvZqfqe8ycqdNSh0I2ZnpIbf0mTjtYHYone+CzNUeDYKzk+NsrgHOLFrj8JcOM=</Data>
                        </PidData>',
            "sourceip" => '103.76.251.132'
        ];

        /*---------------------------Send Data------------------------------*/

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
            'request_hash' => $request_hash
        ])->post('https://staging.eko.in:25004/ekoapi/v2/aeps', $data);

        return $response;
    }

    public function aepsInquiry(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $initiator_id = 9962981729;
        $transaction_id = $request['transction_id'];

        $response = Http::withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v1/transactions/$transaction_id?initiator_id=$initiator_id");

        return $response;
    }

    public function fundSettlement(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $data = [
            'service_code' => "39",
            'initiator_id' => 9962981729,
            'user_code' => auth()->user()->user_code ??  20310003
        ];

        $response = Http::asForm()->withHeaders([
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put('https://staging.eko.in:25004/ekoapi/v1/user/service/activate', $data);

        return $response;
    }

    public function bankSettlement(Request $request)
    {
        $usercode = $request->input('user_code');
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        $data = [
            'service_code' => 39,
            'initiator_id' => 9962981729,
            'user_code' => auth()->user()->user_code,
            'bank_id' => 108,
            'ifsc' => $request['ifsc'],
            'account' => $request['acc_num'],
        ];

        $response = Http::asForm()->withHeaders([
            'cache-control' => 'no-cache',
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->put("https://staging.eko.in:25004/ekoapi/v1/agent/user_code:$usercode/settlementaccount", $data);

        return $response;
    }

    public function initiateSettlement(Request $request)
    {
        $usercode = auth()->user()->user_code; // 20310006
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $data = [
            'service_code' => 39,
            'initiator_id' => 7411111111,
            'amount' => $request['amount'],
            'recipient_id' => $request['recipient_id'],
            'payment_mode' => 5,
            'client_ref_id' => $request['client_ref_id']
        ];

        $response = Http::asForm()->withHeaders([
            'cache-control' => 'no-cache',
            'developer_key' => 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp' => $secret_key_timestamp,
            'secret-key' => $secret_key,
        ])->post("https://staging.eko.in:25004/ekoapi/v1/agent/user_code:$usercode/settlement", $data);

        return $response;
    }

    /**
     * @param $service
     * @param int $amount
     * @param $id
     */
    public function commission($service, int $amount, $id)
    {
        $user = auth()->user();
        $user_package = DB::table('user_package')->where('user_id', auth()->user()->id)->pluck('package_id');
        $serviceId = DB::table('services')->where('service', $service)->pluck('id');
        $user_service = DB::table('package_service')->where(['package_id' => $user_package, 'service_id' => $serviceId])->where('to', '<=', $amount)->pluck('commission');
        if ($user->has_parent) {
            $parentId = DB::table('user_parent')->where('user_id', $user->id)->pluck('parent_id');
            $parent   = User::findOrFail($parentId);
            $packageId  = DB::table('user_package')->where('user_id', $parent)->pluck('package_id');
            DB::table('package_service')->where(['package_id' => $packageId, 'service_id' => $serviceId])->where('to', '<=', $amount)->pluck('commission');
            if ($parent->has_parent) {
                $parentId2 = DB::table('user_parent')->where('user_id', $parentId)->pluck('parent_id');
                $parent2 = User::findOrFail($parentId2);
                if ($parent2->has_parent) {
                    $parentId3 = DB::table('user_parent')->where('user_id', $parentId2)->pluck('parent_id');
                    $parent3 = User::findOrFail($parentId3);
                    if ($parent3->has_parent) {
                        $commission = 5;
                    } else {
                        $commission  = 5;
                    }
                } else {
                    $commission = 5;
                }
            } else {
                $commission = 5;
            }
        } else {
            $commission = 5;
        }
    }

    public function commissionTest(User $user, $id, $amount, $service_id)
    {
        if ($user->has_parent) {
            $parent_id = DB::table('user_parent')->where('user_id', $user->id)->pluck('parent_id');
            $user_package = DB::table('package_user')->where('user_id', $parent_id[0])->pluck('package_id');
            $commission = DB::table('package_service')->where(['package_id' => $user_package[0], 'service_id' => $service_id[0]])->where('to', '<=', $amount)->pluck('commission');
            $parent = User::find($parent_id[0]);
            $roles = $parent->getRoleNames();
            DB::table('transactions')->where('id', $id)->update(["$roles[0]_commission" => $commission[0]]);
            $this->commissionTest($parent, $id, $amount, $service_id);
        } else {
            DB::table('transactions')->where('id', $id)->update([
                'admin_commission' => 5
            ]);
        }

        return response("Commission asssigned", 200);
    }

    public function testTransaction()
    {
        $debit_amount = 1500;
        $credit_amount = 10;
        $wallet = 5000;
        $service = 'withdrawal';
        $id = DB::table('transactions')->insertGetId([
            'user_id' => 55,
            'transaction_id' => uniqid(),
            'credit_amount' => $credit_amount,
            'debit_amount' => $debit_amount,
            'transaction_for' => $service,
            'opening_balance' => $wallet,
            'is_flat' => 0,
            'balance_left' => $wallet + $credit_amount - $debit_amount,
        ]);
        return $this->test(55, $id, $credit_amount, $service);
        return response('Done', 200);
    }

    public function test(int $user_id, int $id, int $amount, string $service)
    {
        $user = User::with(['packages.services' => function ($query) use ($service) {
            $query->where('operator_type', 'like', "%$service%");
        }, 'parents:id,name', 'roles:name'])->select('id')->findOrFail($user_id);

        $user_role = $user['roles'][0]['name'];

        $user_service = $user['packages'][0]['services'][0];

        if ($user_service['pivot']['is_flat'] && $user_service['pivot']['is_surcharge']) {
            $commission = -$user_service['pivot']['commission'];
        } elseif (!$user_service['pivot']['is_flat'] && $user_service['pivot']['is_surcharge']) {
            $commission = -$user_service['pivot']['commission'] * $amount / 100;
        } elseif ($user_service['pivot']['is_flat'] && !$user_service['pivot']['is_surcharge']) {
            $commission = $user_service['pivot']['commission'];
        } elseif (!$user_service['pivot']['is_flat'] && !$user_service['pivot']['is_surcharge']) {
            $commission = $user_service['pivot']['commission'] * $amount / 100;
        }
        Log::info('step-user-commission');
        // return $commission;
        DB::table('transactions')->where('id', $id)->update([
            "$user_role" . "_" . "commission" => $commission,
        ]);


        if (!sizeof($user['parents']) == 0) {

            $this->test($user['parents'][0]['pivot']['parent_id'], $id, $amount, $service);
        }
        echo 'done';
    }
}
