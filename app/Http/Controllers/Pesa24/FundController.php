<?php

namespace App\Http\Controllers\Pesa24;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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
            ->where(['users.organization_id' => auth()->user()->organization_id])->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest()->paginate(200);
        return $data;
    }

    public function pendingfetchFund(Request $request, $type, $id = null)
    {

        if (!empty($request['search']) || !is_null($request['search'])) {
            if (empty($request['pageSize'])) {
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->where('funds.transaction_id', 'like', '%' . $request['search'] . '%')
                    ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
            } else {
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->where('funds.transaction_id', 'like', '%' . $request['search'] . '%')
                    ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'pageSize' => $request['pageSize'], 'status' => $request['status'], 'search' => $request['search']]);
            }

            return $data;
        }

        if (!empty($request['userId']) || !is_null($request['userId'])) {
            if (!empty($request['status']) || !is_null($request['status'])) {
                if (empty($request['pageSize'])) {
                    $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                        ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                        ->where(['funds.user_id' => $request['userId'], 'funds.status' => $request['status']])
                        ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                        ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
                    return $data;
                } else {
                    $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                        ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                        ->where(['funds.user_id' => $request['userId'], 'funds.status' => $request['status']])
                        ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                        ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'userId' => $request['userId'], 'status' => $request['status'], 'search' => $request['search']]);
                    return $data;
                }
            }

            if (empty($request['pageSize'])) {
                if (!empty($request['status']) || !is_null($request['status'])) {

                    $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                        ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                        ->where('funds.user_id', $request['userId'])
                        ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                        ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', $request['status'])->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
                    return $data;
                }
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->where('funds.user_id', $request['userId'])
                    ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
                return $data;
            } else {
                if (!empty($request['status']) || !is_null($request['status'])) {
                    $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                        ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                        ->where('funds.user_id', $request['userId'])
                        ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                        ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', $request['status'])->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'userId' => $request['userId'], 'pageSize' => $request['pageSize'], 'search' => $request['search'], 'status' => $request['status']]);
                    return $data;
                }
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->where('funds.user_id', $request['userId'])
                    ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'userId' => $request['userId'], 'pageSize' => $request['pageSize'], 'search' => $request['search'], 'status' => $request['status']]);
                return $data;
            }
        }

        if (empty($request['pageSize'])) {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
            return $data;
        }

        if (!empty($request['status']) || !is_null($request['status'])) {

            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->where('funds.status', $request['status'])
                ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'pageSize' => $request['pageSize'], 'status' => $request['status'], 'search' => $request['search']]);

            return $data;
        }
        if ($type == 'pending') {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                // ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                ->where(['users.organization_id' => auth()->user()->organization_id, 'funds.status' => 'pending'])->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'pageSize' => $request['pageSize'], 'status' => $request['status'], 'search' => $request['search']]);
            return $data;
        }
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
            ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
            ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
            ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'pageSize' => $request['pageSize'], 'status' => $request['status'], 'search' => $request['search']]);
        return $data;
    }

    public function fetchFundId($id)
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['funds.id' => $id, 'users.organization_id' => auth()->user()->organization_id])->paginate(200);
        return $data;
    }

    public function reversalAndTransferFunds(Request $request, $id = null)
    {

        $search = $request['search'];
        // if (!is_null($search) || !empty($search)) {
        //     $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
        //         ->where('funds.transaction_id', 'like', '%' . $search . '%')
        //         ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
        //         ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
        //         ->select('users.name', 'users.phone_number', 'funds.transaction_id', 'funds.user_id', 'funds.amount', 'funds.remarks', 'funds.transaction_type', 'funds.created_at', 'admin.name as admin_name', 'admin.id as admin_id', 'admin.phone_number as admin_phone', 'funds.id')
        //         ->latest('funds.updated_at')
        //         ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'userId' => $request['userId'], 'search' => $request['search']]);
        //     return $data;
        // }

        if (!is_null($request['userId']) || !empty($request['userId'])) {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->where('transaction_type', 'transfer')->orWhere('transaction_type', 'reversal')
                ->where('funds.user_id', $request['userId'])
                ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->select('users.name', 'users.phone_number', 'funds.transaction_id', 'funds.user_id', 'funds.amount', 'funds.remarks', 'funds.transaction_type', 'funds.created_at', 'admin.name as admin_name', 'admin.id as admin_id', 'admin.phone_number as admin_phone', 'funds.id')
                ->latest('funds.updated_at')
                ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'userId' => $request['userId'], 'search' => $request['search']]);
            return $data;
        }
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
            ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
            ->where('transaction_type', 'transfer')->orWhere('transaction_type', 'reversal')
            ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
            ->select('users.name', 'users.phone_number', 'funds.transaction_id', 'funds.user_id', 'funds.amount', 'funds.remarks', 'funds.transaction_type', 'funds.created_at', 'admin.name as admin_name', 'admin.id as admin_id', 'admin.phone_number as admin_phone', 'funds.id')
            ->latest('funds.updated_at')
            ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'search' => $request['search']]);
        return $data;
    }

    public function updateFund(Request $request)
    {

        $request->validate([
            'amount' => 'required|integer',
            'status' => 'required',
            'beneficiaryId' => 'required|exists:users,id'
        ]);
        $transaction = DB::transaction(function () use ($request) {


            $status = $request['status'];

            $user = User::find($request['beneficiaryId']);
            $name = $user->name;
            $wallet = $user->wallet;
            $phone = $user->phone_number;

            if ($status == 'approved') {
                $closing_balance = $wallet + $request['amount'];
            } else {
                $closing_balance = $wallet;
            }

            Cache::put(time() . $request['beneficiaryId'], time() . $request['beneficiaryId'], 3);

            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['funds.id' => $request['id'], 'users.organization_id' => auth()->user()->organization_id, 'funds.status' => 'pending'])->update([
                'funds.admin_remarks' => $request['remarks'] ?? null,
                'funds.status' => $status,
                'funds.approved' => $request['approved'],
                'funds.declined' => $request['declined'],
                'funds.closing_balance' => $closing_balance,
                'funds.opening_balance' => $wallet,
                'funds.parent_id' => auth()->user()->id,
                'funds.updated_at' => now()
            ]);

            $transaction_id = "FUND" . strtoupper(Str::random(5));
            if ($status == 'approved') {
                // $amount = auth()->user()->wallet - $request['amount'];
                $metadata = [
                    'status' => true,
                    'amount_transfered' => $request['amount'],
                    'fund_id' => $request['id'],
                    'remarks' => $request['remarks'] ?? null,
                    'reference_id' => $transaction_id,
                    'transaction_from' => auth()->user()->name
                ];
                $this->generalTransaction($request['amount'], "Fund request approved for $name - $phone", 'fund-request', auth()->user()->id, auth()->user()->wallet, $transaction_id, auth()->user()->wallet - $request['amount'], json_encode($metadata));

                // $wallet = $user->wallet;
                // $amount = $wallet + $request['amount'];
                $metadata = [
                    'status' => true,
                    'amount_added' => $request['amount'],
                    'remarks' => $request['remarks'] ?? null,
                    'fund_id' => $request['id'],
                    'reference_id' => $transaction_id,
                    'transaction_from' => auth()->user()->name,
                    'phone_number' => auth()->user()->phone_number
                ];
                $this->generalTransaction(0, "Fund request approved by {$metadata['transaction_from']} - {$metadata['phone_number']}", 'fund-request', $request['beneficiaryId'], $user->wallet, $transaction_id, $user->wallet + $request['amount'], json_encode($metadata), $request['amount']);
            }

            $name = $user->name;
            $phone = $user->phone_number;
            $wallet = $user->wallet;
            $status = $request['status'];
            $time = date('d-m-Y h:i:s A');
            $newmsg = "Hello $name, Your fund request has been $status and Now Your Bal $closing_balance on the date of $time. '-From P24 Technology Pvt. Ltd";
            $sms = Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$newmsg", []);
            return $data;
        });

        return $transaction;
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
            ->paginate(200);

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

        Cache::put(time().$request['beneficiaryId'], time().$request['beneficiaryId'], 3);

        if ($request['beneficiaryId'] == auth()->user()->id) {
            return response("You can not send to money to yourself.", 403);
        }

        $user = User::find($request['beneficiaryId']);
        if ($request['transactionType'] == 'transfer') {
            $opening_balance = $user->wallet;
            $closing_balance = $user->wallet + $request['amount'];
        } else {
            $opening_balance = $user->wallet;
            $closing_balance = $user->wallet - $request['amount'];
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
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'remarks' => $request['remarks'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if ($request['transactionType'] == 'transfer') {
            $amount = auth()->user()->wallet - $request['amount'];
            $user = User::find($request['beneficiaryId']);
            $metadata = [
                'status' => true,
                'amount_transfered' => $request['amount'],
                'remarks' => $request['remarks'] ?? null,
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
                'remarks' => $request['remarks'] ?? null,
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
                'remarks' => $request['remarks'] ?? null,
                'reference_id' => $transaction_id,
                'transaction_from' => auth()->user()->name,
                'phone_number' => auth()->user()->phone_number
            ];

            $this->notAdmintransaction($request['amount'], "Fund reversed from {$user->name} {$user->phone_number} wallet", 'funds', $request['beneficiaryId'], $wallet[0], $transaction_id, $amount, json_encode($metadata));
        }

        return $data;
    }

    public function deleteFund(Request $request)
    {
        $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')->where(['users.organization_id' => auth()->user()->organization_id, 'funds.id' => $request['fundId']])->delete();
        return $data;
    }
}
