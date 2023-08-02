<?php

namespace App\Http\Controllers\Razorpay;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Razorpay\PayoutController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FundAccountController extends PayoutController
{


    public function createFundAcc(Request $request)
    {

        if(!Hash::check($request['mpin'], auth()->user()->mpin)){
            return response('MPIN did not match', 400);
        }

            $account_details = [
                'name' => $request['beneficiaryName'],
                'ifsc' => $request['ifsc'],
                'account_number' => $request['account']
            ];


        $data = [
            'contact_id' => auth()->user()->rzp_contact_id,
            'account_type' => 'bank_account',
            'bank_account' => $account_details
        ];

        $key = env('RAZORPAY_KEY');
        $secret = env('RAZORPAY_SECRET');
        $response = Http::withBasicAuth($key, $secret)
            ->post('https://api.razorpay.com/v1/fund_accounts', $data);

            Log::channel('response')->info($response);
            if (isset($response['error'])) {
                return response()->json(['message' => $response['error']['description']], 400);
            }
        return $this->bankPayout($response, $request['amount'], $account_details);
    }

}
