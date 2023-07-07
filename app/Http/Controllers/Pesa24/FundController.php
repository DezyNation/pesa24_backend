<?php

namespace App\Http\Controllers\Pesa24;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class FundController extends Controller
{
    public function parents()
    {
        $user = User::with(['parentsRoles.parentsRoles.parentsRoles'])->select('id', 'name')->where('id', auth()->user()->id)->get();
        return $user;
    }

    public function fetchFund()
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
            ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
            ->where(['users.organization_id' => auth()->user()->organization_id])->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest()->paginate(100);
        return $data;
    }

    public function pendingfetchFund($type)
    {
        if ($type == 'pending') {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->where(['users.organization_id' => auth()->user()->organization_id, 'funds.status' => 'pending'])->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest()->paginate(100);
            return $data;
        }
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
            ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
            ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest()->paginate(100);
        return $data;
    }

    public function fetchFundId($id)
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['funds.id' => $id, 'users.organization_id' => auth()->user()->organization_id])->paginate(100);
        return $data;
    }

    public function reversalAndTransferFunds()
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
            ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
            ->where('transaction_type', 'transfer')->orWhere('transaction_type', 'reversal')
            ->select('users.name', 'users.phone_number', 'funds.transaction_id', 'funds.user_id', 'funds.amount', 'funds.remarks', 'funds.transaction_type', 'funds.created_at', 'admin.name as admin_name', 'admin.id as admin_id', 'funds.id')
            ->paginate(100);
        return $data;
    }

    public function updateFund(Request $request)
    {

        $request->validate([
            'amount' => 'required|integer',
            'status' => 'required',
            'beneficiaryId' => 'required|exists:users,id'
        ]);

        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['funds.id' => $request['id'], 'users.organization_id' => auth()->user()->organization_id])->update([
            'funds.admin_remarks' => $request['remarks'] ?? null,
            'funds.status' => $request['status'],
            'funds.approved' => $request['approved'],
            'funds.declined' => $request['declined'],
            'funds.parent_id' => auth()->user()->id,
            'funds.updated_at' => now()
        ]);

        $user = User::find($request['beneficiaryId']);
        $name = $user->name;
        $phone = $user->phone_number;

        $transaction_id = "FUND" . strtoupper(Str::random(5));
        if ($request['status'] == 'approved') {
            $amount = auth()->user()->wallet - $request['amount'];
            $metadata = [
                'status' => true,
                'amount_transfered' => $request['amount'],
                'reference_id' => $transaction_id,
                'transaction_from' => auth()->user()->name
            ];
            $this->transaction($request['amount'], "Fund request approved for $name - $phone", 'fund-request', auth()->user()->id, auth()->user()->wallet, $transaction_id, $amount, json_encode($metadata));

            $wallet = DB::table('users')->where('id', $request['beneficiaryId'])->pluck('wallet');
            $amount = $wallet[0] + $request['amount'];
            $metadata = [
                'status' => true,
                'amount_added' => $request['amount'],
                'reference_id' => $transaction_id,
                'transaction_from' => auth()->user()->name,
                'phone_number' => auth()->user()->phone_number
            ];
            $this->notAdmintransaction(0, "Fund request approved by {$metadata['transaction_from']} - {$metadata['phone_number']}", 'fund-request', $request['beneficiaryId'], $wallet[0], $transaction_id, $amount, json_encode($metadata), $request['amount']);
        }

        $name = $user->name;
        $phone = $user->phone_number;
        $wallet = $user->wallet;
        $status = $request['status'];
        $time = date('d-m-Y h:i:s A');
        $newmsg = "Hello $name, Your fund request has been $status and Now Your Bal $wallet on the date of $time. '-From P24 Technology Pvt. Ltd";
        $sms = Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$newmsg", []);

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
            ->paginate(100);

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

        $request->validate([
            'beneficiaryId' => 'required|integer',
            'amount' => 'required|min:1|numeric',
            'transactionType' => 'required|string',
        ]);

        if ($request['beneficiaryId'] == auth()->user()->id) {
            return response("You can not send to money to yourself.", 403);
        }

        $transaction_id = "FUND" . strtoupper(Str::random(5));

        $data = DB::table('funds')->insert([
            'user_id' => $request['beneficiaryId'],
            'parent_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'transaction_type' => $request['transactionType'],
            'transaction_id' => $transaction_id,
            'transaction_date' => date('Y-m-d H:i:s'),
            'approved' => 1,
            'status' => 'approved',
            'remarks' => $request['remarks'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = User::find($request['beneficiaryId']);
        if ($request['transactionType'] == 'transfer') {
            $amount = auth()->user()->wallet - $request['amount'];
            $user = User::find($request['beneficiaryId']);
            $metadata = [
                'status' => true,
                'amount_transfered' => $request['amount'],
                'reference_id' => $transaction_id,
                'transaction_from' => auth()->user()->name
            ];

            DB::table('users')->where('id', auth()->user()->id)->update([
                'wallet' => $amount,
                'updated_at' => now()
            ]);

            $this->transaction($request['amount'], "Fund transfer initiated for user {$user->name} - {$user->phone_number}", 'funds', auth()->user()->id, auth()->user()->wallet, $transaction_id, $amount, json_encode($metadata));
            $wallet = DB::table('users')->where('id', $request['beneficiaryId'])->pluck('wallet');
            $amount = $wallet[0] + $request['amount'];
            $metadata = [
                'status' => true,
                'amount_added' => $request['amount'],
                'reference_id' => $transaction_id,
                'transaction_from' => auth()->user()->name,
                'phone_number' => auth()->user()->phone_number
            ];
            $this->notAdmintransaction(0, "Fund transfered initiated by admin {$metadata['transaction_from']} - {$metadata['phone_number']}", 'funds', $request['beneficiaryId'], $wallet[0], $transaction_id, $amount, json_encode($metadata), $request['amount']);
        } else {
            $wallet = DB::table('users')->where('id', $request['beneficiaryId'])->pluck('wallet');
            $amount = $wallet[0] - $request['amount'];

            $metadata = [
                'status' => true,
                'amount_reversed' => $request['amount'],
                'reference_id' => $transaction_id,
                'transaction_from' => auth()->user()->name
            ];

            $transaction_id = "FUND" . strtoupper(Str::random(5));
            $this->transaction($request['amount'], "Fund reversed from {$user->name} {$user->phone_number} wallet", 'funds', $request['beneficiaryId'], $wallet[0], $transaction_id, $amount, json_encode($metadata));
        }

        return $data;
    }

    public function deleteFund(Request $request)
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['users.organization_id' => auth()->user()->organization_id, 'funds.id' => $request['fundId']])->delete();
        return $data;
    }
}
