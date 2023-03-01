<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FundRequestController extends Controller
{
    public function fundRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer',
            'bankName' => 'required|string',
            'transactionType' => 'required|string',
            'transactionId' => 'required|string',
            'transactionDate' => 'required|date',
            'receipt' => 'mimes:jpg,jpeg,png,pdf'
        ]);

        $imgPath = $request->file('receipt')->store('image', 'receipt');

        DB::table('funds')->insert([
            'user_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'bank_name' => $request['bankName'],
            'transaction_type' => $request['transactionType'],
            'transaction_id	' => $request['transactionId'],
            'transaction_date	' => $request['transactionDate'],
            'receipt' => $imgPath
        ]);

        return response('Request sent', 200);
    }
}
