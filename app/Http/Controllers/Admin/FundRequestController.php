<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FundRequestController extends Controller
{
    public function fundRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer',
            'bankName' => 'required|string',
            'transactionType' => 'required|string',
            'transactionId' => 'required|string|unique:funds,transaction_id',
            'transactionDate' => 'required|date',
            'receipt' => 'mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $imgPath = $request->file('receipt')->store('receipt');

        DB::table('funds')->insert([
            'user_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'bank_name' => $request['bankName'],
            'transaction_type' => $request['transactionType'],
            'transaction_id' => $request['transactionId'],
            'transaction_date' => $request['transactionDate'],
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
            'parent_id' => $request['requestFrom'],
            'transaction_type' => $request['transactionType'],
            'transaction_id' => $request['transactionId'],
            'transaction_date' => $request['transactionDate'],
            'approved' => $request['approved'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if ($request['approved'] == 1) {
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
        $data = DB::table('funds')->where('user_id', auth()->user()->id)->select(
            'amount',
            'bank_name',
            'transaction_id',
            'status',
            'transaction_type',
            'transaction_date',
            'receipt',
            'remarks',
            'admin_remarks',
            'created_at'
        )->paginate(20);

        return $data;
    }

    public function fundTransfer(Request $request)
    {
        $request->validate([
            'mpin' => 'required|digits:4',
            'amount' => 'required|integer',
            'to' => 'required|integer',
            'remark' => 'string', 
        ]);
        if (!Hash::check($request['mpin'], auth()->user()->mpin)) {
            return response()->json(['message' => 'Wrong MPIN']);
        }

        $user = DB::table('users')->where('phone_number', $request['to']);
        $wallet = $user->pluck('wallet');
        $total_amount = $wallet + $request['amount'];
        $user->update([
            'wallet' => $total_amount,
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Fund transfer Successful']);


    }
}
