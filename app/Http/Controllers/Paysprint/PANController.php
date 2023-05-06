<?php

namespace App\Http\Controllers\Paysprint;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PANController extends Controller
{

    public function token()
    {
        $key = env('PAYSPRINT_KEY');
        $payload = [
            'timestamp' => time(),
            'partnerId' => env('PAYSPRINT_PARTNERID'),
            'reqid' => abs(crc32(uniqid()))
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

/*------------------------------PAN NSDL------------------------------*/
    public function generateUrl(Request $request)
    {

        $request->validate([
            'title' => 'required',

        ]);

        $token = $this->token();
             
        $data = [
            'refid' => "PESA24".strtoupper(uniqid() . Str::random(12)),
            'title' => $request['title'],
            'firstname' => $request['firstName'],
            'middlename' => $request['middleName'],
            'lastname' => $request['lastName'],
            'mode' => $request['mode'],
            'gender' => $request['gender'],
            'redirect_url' => 'https://pesa24.co.in',
            'email' => $request['email']
        ];

        $response = Http::aaceptJson()->withHeaders([
            'Token' => $token,
            
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'Content-Type: application/json'
        ])->post('https://paysprint.in/service-api/api/v1/service/pan/V2/generateurl', $data);

        return $response;
    }

    public function panStatus(Request $request)
    {

        $token = $this->token();

        $data = [
            'refid' => $request['refid'],
        ];

        $response = Http::acceptJson()->withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
        ])->post('https://paysprint.in/service-api/api/v1/service/pan/V2/pan_status', $data);

        return $response;
    }
}
