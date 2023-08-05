<?php

namespace App\Http\Controllers\Pesa24\Dashboard;

use App\Exports\User\LedgerExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class UserDashboardController extends Controller
{
    public function sunTransaction($service)
    {
        $credit_sum = DB::table('transactions')->where(['user_id' => auth()->user()->id, 'service_type' => $service])->get();
        $debit_sum = DB::table('transactions')->where(['user_id' => auth()->user()->id, 'service_type' => $service])->get();
        return ['credit_amount' => $credit_sum, 'debit_amount' > $debit_sum];
    }

    public function transactionLedger(Request $request, $name = null)
    {
        $id = auth()->user()->id;

        $search = $request['search'];
        if (!empty($search) || !is_null($search)) {
            $data = DB::table('transactions')->where('trigered_by', $id)->where('transaction_id', 'like', '%' . $search . '%')->orWhere('transaction_for', 'like', '%' . $search . '%')->orWhere('metadata->status', 'like', '%' . $search . '%')->latest()->orderByDesc('transactions.id')->get();
            return $data;
        }

        if ($name == 'all') {
            $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where(function ($q) use ($id) {
                $q->where('trigered_by', $id);
                // ->orWhere('user_id', $id);
            })->latest()->orderByDesc('transactions.id')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'search' => $request['search']]);

            return $data;
        }

        $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where('service_type', $name)->where(function ($q) use ($id) {
            $q->where('trigered_by', $id);
            // ->orWhere('user_id', $id);
        })->latest()->orderByDesc('transactions.id')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'search' => $request['search']]);
        return $data;

        // $search = $request['search'];
        // if (!empty($search) || !is_null($search)) {
        //     $data = DB::table('transactions')->where('trigered_by', auth()->user()->id)->where('transaction_for', 'like', '%' . $search . '%')->orWhere('transaction_id', 'like', '%' . $search . '%')->latest()->get();
        //     // ->paginate(200);
        //     return $data;
        // }
        // if (!is_null($name) || !empty($name)) {
        //     if (!is_null($request['status']) || !empty($request['status'])) {
        //         // $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where(['trigered_by' => auth()->user()->id, 'service_type' => $name])->orWhere(['user_id' =>  auth()->user()->id, 'service_type' => $name])->whereJsonContains('metadata->status', $request['status'])->latest()->get();
        //         $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where('service_type', $name)->where(function ($q) {
        //             $q->where('trigered_by', auth()->user()->id);
        //             // ->orWhere('user_id', auth()->user()->id);
        //         })->whereJsonContains('metadata->status', $request['status'])->latest()->get();
        //         // ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'status' => $request['status']]);
        //         return $data;
        //     }
        //     $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where('service_type', $name)->where(function ($q) {
        //         $q->where('trigered_by', auth()->user()->id);
        //         // ->orWhere('user_id', auth()->user()->id);
        //     })->latest()->get();
        //     // $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where(['trigered_by' => auth()->user()->id, 'service_type' => $name])->orWhere(['user_id' =>  auth()->user()->id, 'service_type' => $name])->latest()->get();
        //     // ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'status' => $request['status']]);
        //     return $data;
        // }
        // $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where('trigered_by', auth()->user()->id)->orWhere('user_id', auth()->user()->id)->latest()->get();
        // // ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to']]);
        // return $data;
    }

    public function overView(Request $request)
    {
        $user_id = auth()->user()->id;
        $tenure = $request['tenure'];

        $aeps = $this->userTable($tenure, 'aeps', $request, $user_id);

        $bbps = $this->userTable($tenure, 'bbps', $request, $user_id);

        $dmt = $this->userTable($tenure, 'dmt', $request, $user_id);

        $pan = $this->userTable($tenure, 'pan', $request, $user_id);

        $payout = $this->userTable($tenure, 'payout', $request, $user_id);

        $payout_charge = $this->userTable($tenure, 'payout-charge', $request, $user_id);

        $lic = $this->userTable($tenure, 'lic', $request, $user_id);

        $fastag = $this->userTable($tenure, 'fastag', $request, $user_id);

        $cms = $this->userTable($tenure, 'cms', $request, $user_id);

        $payout_commission = $this->userTable($tenure, 'payout-commission', $request, $user_id);

        $recharge = $this->userTable($tenure, 'recharge', $request, $user_id);

        $funds = $this->fundRequests($tenure, $user_id);

        $array = [
            $aeps,
            $bbps,
            $dmt,
            $pan,
            $payout,
            $lic,
            $fastag,
            $cms,
            $recharge,
            $funds,
            $payout_charge,
            $payout_commission
        ];

        return response($array);
    }

    public function userTable($tenure, $category, $request, $id)
    {
        $tenure;
        switch ($tenure) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;

            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            default:
                $start = $request['from'] ?? Carbon::today();
                $end = $request['to'] ?? Carbon::tomorrow();
                break;
        }
        $table = DB::table('transactions')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where(['transactions.trigered_by' => $id, 'service_type' => $category]);
        return [
            $category => [
                'credit' => $table->sum('credit_amount'),
                'debit' => $table->sum('debit_amount'),
                'count' => $table->count()
            ]
        ];
    }

    public function adminUserTable($tenure, $category, $request, $user_id)
    {
        $tenure;
        switch ($tenure) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;

            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            default:
                $start = $request['from'] ?? Carbon::today();
                $end = $request['to'] ?? Carbon::tomorrow();
                break;
        }
        $table = DB::table('transactions')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where(['transactions.trigered_by' => $user_id, 'service_type' => $category]);
        return [
            $category => [
                'credit' => $table->sum('credit_amount'),
                'debit' => $table->sum('debit_amount'),
                'count' => $table->count()
            ]
        ];
    }

    public function fundRequests($tenure, $user_id)
    {
        switch ($tenure) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;

            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            default:
                $start = Carbon::today();
                $end = Carbon::tomorrow();
                break;
        }

        $not_approved = DB::table('funds')
            ->whereBetween('created_at', [$start, $end])
            ->where(['funds.approved' => 0, 'funds.user_id' => $user_id])->count();

        $all = DB::table('funds')
            ->whereBetween('created_at', [$start, $end])
            ->where('funds.user_id', auth()->user()->id)
            ->count();

        return [
            'funds' => [
                'approved' => $all - $not_approved,
                'not_approved' => $not_approved,
                'all' => $all
            ]
        ];
    }

    public function adminFundRequests($tenure, $user_id)
    {
        switch ($tenure) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;

            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            default:
                $start = Carbon::today();
                $end = Carbon::tomorrow();
                break;
        }

        $not_approved = DB::table('funds')
            ->whereBetween('created_at', [$start, $end])
            ->where(['funds.approved' => 0, 'funds.user_id' => $user_id])->count();

        $all = DB::table('funds')
            ->whereBetween('created_at', [$start, $end])
            ->where('funds.user_id', $user_id)
            ->count();

        return [
            'funds' => [
                'approved' => $all - $not_approved,
                'not_approved' => $not_approved,
                'all' => $all
            ]
        ];
    }

    public function settlementRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer'
        ]);
        $data = DB::table('settlement_request')->insert([
            'user_id' => auth()->user()->id,
            'amount' => $request['amount'],
            'message' => $request['message'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $data;
    }

    public function getSettlementRequest()
    {
        $data = DB::table('settlement_request')->where('user_id', auth()->user()->id)->get();
        return $data;
    }

    public function dailySales(Request $request)
    {
        $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])->where(['trigered_by' => auth()->user()->id])->whereJsonContains('metadata->status', true)->paginate(200);
        return $data;
    }

    public function adminDailySales(Request $request, $id)
    {
        $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])->where(['trigered_by' => $id])->whereJsonContains('metadata->status', true)->paginate(200);
        return $data;
    }

    public function claim(Request $request): int
    {
        $request->validate([
            'transactionId' => 'required|exists:transactions,transaction_id',
            'claim' => 'required'
        ]);
        $data = DB::table('transactions')
            ->where(['user_id' => auth()->user()->id, 'transaction_id' => $request['transactionId']])
            ->update(
                [
                    'claim' => $request['claim'],
                    'updated_at' => now()
                ]
            );

        return $data;
    }

    public function adminOverview(Request $request, $user_id)
    {
        $tenure = $request['tenure'];

        $aeps = $this->adminUserTable($tenure, 'aeps', $request, $user_id);

        $bbps = $this->adminUserTable($tenure, 'bbps', $request, $user_id);

        $dmt = $this->adminUserTable($tenure, 'dmt', $request, $user_id);

        $pan = $this->adminUserTable($tenure, 'pan', $request, $user_id);

        $payout = $this->adminUserTable($tenure, 'payout', $request, $user_id);

        $lic = $this->adminUserTable($tenure, 'lic', $request, $user_id);

        $fastag = $this->adminUserTable($tenure, 'fastag', $request, $user_id);

        $cms = $this->adminUserTable($tenure, 'cms', $request, $user_id);

        $payout_commission = $this->adminUserTable($tenure, 'payout-commission', $request, $user_id);

        $payout_charge = $this->adminUserTable($tenure, 'payout-charge', $request, $user_id);

        $cms = $this->adminUserTable($tenure, 'payout-commission', $request, $user_id);

        $recharge = $this->adminUserTable($tenure, 'recharge', $request, $user_id);

        $funds = $this->adminFundRequests($tenure, $user_id);

        $array = [
            $aeps,
            $bbps,
            $dmt,
            $pan,
            $payout,
            $lic,
            $fastag,
            $cms,
            $recharge,
            $funds,
            $payout_charge,
            $payout_commission
        ];

        return response($array);
    }

    public function printReports(Request $request)
    {
        $type = $request['type'];
        $name = $request['name'];
        switch ($type) {
            case 'fund-requests':
                $data = $this->fundReports($request);
                return $data;
                break;

            case 'ledger':
                $data = $this->printLedger($request, $name);
                return $data;
                break;

            default:
                return 'error';
                break;
        }
    }

    public function fundReports(Request $request)
    {
    }

    public function printLedger(Request $request, $name)
    {
        if ($request['doctype'] == 'excel') {
            $file = "ledger.xlsx";
        } else {
            $file = "ledger.pdf";
        }
        return Excel::download(new LedgerExport($request['from'], $request['to'], $request['search'], $request['status'], $name), $file);
    }
}
