<?php

namespace App\Http\Controllers\Eko\BBPS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class BBPSController extends Controller
{
    public function operators($category_id=null)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
    
        $response = Http::acceptJson()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v2/billpayments/operators/?category=$category_id");
    
        return $response;
    }

    public function operatorCategoryList()
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);

        $response = Http::acceptJson()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v2/billpayments/operators_category");

        return $response;
    }

    public function operatorField($operator_id)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
    
        $response = Http::acceptJson()->withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->get("https://staging.eko.in:25004/ekoapi/v2/billpayments/operators/$operator_id");
    
        return $response;
    }

    public function fetchBill(Request $request)
    {
        $key = "f74c50a1-f705-4634-9cda-30a477df91b7";
        $encodedKey = base64_encode($key);
        $secret_key_timestamp = round(microtime(true) * 1000);
        $signature = hash_hmac('SHA256', $secret_key_timestamp, $encodedKey, true);
        $secret_key = base64_encode($signature);
        
        $data1 = [
            'user_code' => auth()->user()->user_code,
            'client_ref_id' => uniqid(),
            'source_ip' => $request->ip(),

        ];
        $data = $request->all();
        $data2 = $data1 + $data;
        return $data2;

        $response = Http::withHeaders([
            'developer_key'=> 'becbbce45f79c6f5109f848acd540567',
            'Connection' => 'Keep-Alive',
            'Accept-Encoding' => 'gzip',
            'User-Agent' => 'okhttp/3.9.0',
            'secret-key-timestamp'=> $secret_key_timestamp,
            'secret-key'=> $secret_key,
        ])->post("https://staging.eko.in:25004/ekoapi/v2/billpayments/fetchbill?initiator_id=9962981729", $data2);
    
        return $response;
    }
}
