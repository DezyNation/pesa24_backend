<?php

namespace App\Http\Controllers\Paysprint\AePS;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class AepsApiController extends Controller
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

    public function enquiry(Request $request)
    {
        $key = '060e37d1f82cde00';
        $iv = '788a4b959058271e';


        $pid = '<PidData>
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
        </PidData>';

        $data = [
            'latitude' => '22.44543',
            'longitude' => '77.434',
            'mobilenumber' => '9900000099',
            'referenceno' => uniqid(),
            'ipaddress' => $request->ip(),
            'adhaarnumber' => '715547838073',
            'accessmodetype' => 'SITE',
            'nationalbankidentification' => 652294,
            'requestremarks' => 'AePS enquiry',
            'data' => $pid,
            'pipe' => 'bank2',
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

    public function bankList()
    {
        $token = $this->token();
        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => 'MzNkYzllOGJmZGVhNWRkZTc1YTgzM2Y5ZDFlY2EyZTQ=',
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
    }
}
