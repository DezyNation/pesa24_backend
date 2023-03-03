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

        $imgPath = $request->file('receipt')->store('image', 'public');

        DB::table('funds')->insert([
            'user_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'bank_name' => $request['bankName'],
            'transaction_type' => $request['transactionType'],
            'transaction_id	' => $request['transactionId'],
            'transaction_date	' => $request['transactionDate'],
            'receipt' => $imgPath,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response('Request sent', 200);
    }

    public function fetchFund()
    {
        $data = DB::table('funds')->get();
        return $data;
    }

    public function fetchFundId($id)
    {
        $data = DB::table('funds')->where('id', $id)->get();
        return $data;
    }

    public function updateFund(Request $request, $id)
    {

        $request->validate([
            'amount' => 'required|integer',
            'bankName' => 'required|string',
            'transactionType' => 'required|string',
            'transactionId' => 'required|string',
            'transactionDate' => 'required|date',
            'aprroved' => 'required|digit:1',
        ]);

        $data = DB::table('funds')->where('id', $id)->update([
            'user_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'bank_name' => $request['bankName'],
            'transaction_type' => $request['transactionType'],
            'transaction_id	' => $request['transactionId'],
            'transaction_date	' => $request['transactionDate'],
            'approved' => $request['approved'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if ($request['approved']) {
            $money = DB::table('users')->where('id', $request['user_id'])->pluck('wallet');
            $amount = $money + $request['amount'];
            DB::table('users')->where('id', $request['user_id'])->update([
                'wallet' => $amount
            ]);
        }

        return $data;
    }

    public function fetchFundUser()
    {
        $data = DB::table('funds')->where('user_id', auth()->user()->id)->get([
            'amount',
            'bank_name',
            'transaction_id',
            'status',
            'transaction_type',
            'transaction_date',
            'receipt',
            'remarks',
            'admin_remarks'
        ]);

        return $data;
    }
}
