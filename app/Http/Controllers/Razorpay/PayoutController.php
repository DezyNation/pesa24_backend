<?php

namespace App\Http\Controllers\Razorpay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayoutController extends Controller
{
    public function bankPayout(Request $request)
    {
        $data = [
            'account_number' => '7878780080316316',
            'fund_account_id' => 'fa_00000000000001',
            'amount' => 1000000,
            'currency' => 'INR',
            'mode' => 'IMPS',
            'purpose' => 'refund',
            'queue_if_low_balance' => true,
            'reference_id' => uniqid(),
            'narration' => 'Pesa24 fund transfer',
        ];

        $response =  Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')->withHeaders([
            'Content-Type' => 'application/json'
        ])->post('https://api.razorpay.com/v1/payouts', $data);

        return $response;
    }

    public function fetchPayouts()
    {
        $response = Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')->get('https://api.razorpay.com/v1/payouts?account_number=7878780080316316');

        return $response;
    }

    public function cancelQueuedPayments()
    {
        $response =  Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')
            ->post('https://api.razorpay.com/v1/payouts/pout_00000000000001/cancel', []);

        return $response;
    }
}
