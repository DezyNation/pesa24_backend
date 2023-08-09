<?php

namespace App\Http\Controllers\IncomeWallet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RechargeController extends Controller
{
    public function recharge(Request $request)
    {
        $request->validate([
            'incomeWalletOperatorCode' => 'required',
            'canumber' => 'required',
            'amount' => 'required|numeric'
        ]);

        $data = [
            'apiToken' => env('INCOME_WALLET_API_TOKEN'),
            'mn' => $request['canumber'],
            'reqid' => substr(uniqid('JND', true), 0, 20),
            'op' => $request['incomeWalletOperatorCode'],
            'amt' => $request['amount'],
            'filed1' => $request['field1'] ?? '',
            'filed2' => $request['field2'] ?? '',
        ];

        $response = Http::post('https://www.incomewallet.in/apiservice.asmx/Recharge', $data);

        Log::channel('response')->info($response);
        return $response;
    }
}
