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
    
    
    public function createFundAcc(Request $request, $service_id)
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
            'contact_id' => 'cont_LLryH8Bm2cJaPz',
            'account_type' => 'bank_account',
            'bank_account' => $account_details
        ];

        $response = Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')
            ->post('https://api.razorpay.com/v1/fund_accounts', $data);
    
            if ($response['error']) {
                return response()->json(['message' => $response['error']['description']], 400);
            }
            Log::channel('response')->info($response);
        return $this->bankPayout($response, $request['amount'], $service_id);
    }
    
}
