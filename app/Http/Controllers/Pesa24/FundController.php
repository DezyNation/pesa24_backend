<?php

namespace App\Http\Controllers\Pesa24;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class FundController extends Controller
{
    public function parents()
    {
        $user = User::with(['parentsRoles.parentsRoles.parentsRoles'])->select('id', 'name')->where('id', auth()->user()->id)->get();
        return $user;
    }

    public function fetchFund()
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['users.organization_id'=> auth()->user()->organization_id])->select('funds.*', 'users.name', 'users.phone_number')->paginate(20);
        return $data;
    }

    public function fetchFundId($id)
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['funds.id'=> $id, 'users.organization_id'=> auth()->user()->organization_id])->paginate(20);
        return $data;
    }

    public function reversalAndTransferFunds()
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where('transaction_type', 'transfer')->orWhere('transaction_type', 'reversal')
            ->select('users.name', 'users.phone_number', 'funds.transaction_id', 'funds.user_id', 'funds.amount', 'funds.remarks', 'funds.transaction_type', 'funds.created_at')
            ->paginate(20);
        return $data;
    }

    public function updateFund(Request $request)
    {

        $request->validate([
            'amount' => 'required|integer',
            'status' => 'required',
        ]);

        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['funds.id' => $request['id'], 'users.organization_id' => auth()->user()->organization_id])->update([
            'funds.amount' => $request['amount'],
            'funds.admin_remarks' => $request['admin_remarks'] ?? null,
            'funds.status' => $request['status'],
            'funds.updated_at' => now()
        ]);

        if ($request['status'] == 'processed') {
            $wallet = DB::table('users')->where('id', $request['user_id'])->pluck('wallet');
            $amount = $wallet[0] + $request['amount'];
            $transaction_id = "FUND" . strtoupper(Str::random(5));
            $this->transaction(0, 'Fund added to user`s wallet', 'funds', $request['userId'], $wallet[0], $transaction_id, $amount, $request['amount'], auth()->user()->id);
            DB::table('users')->where('id', $request['user_id'])->update([
                'wallet' => $amount
            ]);
        }

        return $data;
    }

    public function fetchFundUser()
    {
        $data = DB::table('funds')->where('user_id', auth()->user()->id)->select(
            'user_id',
            'amount',
            'bank_name',
            'transaction_id',
            'status',
            'transaction_type',
            'transaction_date',
            'receipt',
            'remarks',
            'admin_remarks'
        )
            ->latest()
            ->paginate(20);

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

    public function newFund(Request $request)
    {

        if (!Hash::check($request['mpin'], auth()->user()->mpin)) {
            return response("MPIN did not match", 403);
        }
        $request->validate([
            'beneficiaryId' => 'required|integer',
            'amount' => 'required|integer',
            'transactionType' => 'required|string',
        ]);

        $transaction_id = "FUND" . strtoupper(Str::random(5));

        $data = DB::table('funds')->insert([
            'user_id' => $request['beneficiaryId'],
            'parent_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'transaction_type' => $request['transactionType'],
            'transaction_id' => $transaction_id,
            'transaction_date' => date('Y-m-d H:i:s'),
            'approved' => 1,
            'status' => 'done',
            'remarks' => $request['remarks'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if ($request['transactionType'] == 'transfer') {
            $wallet = DB::table('users')->where('id', $request['beneficiaryId'])->pluck('wallet');
            $amount = $wallet[0] + $request['amount'];

            $transaction_id = "FUND" . strtoupper(Str::random(5));
            $this->transaction(0, 'Fund added to user`s wallet', 'funds', $request['beneficiaryId'], $wallet[0], $transaction_id, $amount, $request['amount'], auth()->user()->id);
            DB::table('users')->where('id', $request['beneficiaryId'])->update([
                'wallet' => $amount
            ]);
        } else {
            $wallet = DB::table('users')->where('id', $request['beneficiaryId'])->pluck('wallet');
            $amount = $wallet[0] - $request['amount'];


            $transaction_id = "FUND" . strtoupper(Str::random(5));
            $this->transaction($request['amount'], 'Fund reversed from user`s wallet', 'funds', $request['beneficiaryId'], $wallet[0], $transaction_id, $amount, 0, auth()->user()->id);
            DB::table('users')->where('id', $request['beneficiaryId'])->update([
                'wallet' => $amount
            ]);
        }

        return $data;
    }
}
