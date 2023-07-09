<?php

namespace App\Http\Controllers\Pesa24\Dashboard;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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
        $search = $request['search'];
        if (!empty($search) || !is_null($search)) {
            $data = DB::table('transactions')->where('trigered_by', auth()->user()->id)->where('transaction_for', 'like', '%' . $search . '%')->orWhere('transaction_id', 'like', '%' . $search . '%')->latest()->paginate(200);
            return $data;
        }
        if (is_null($name)) {
            $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])->where('trigered_by', auth()->user()->id)->latest()->paginate(200);
            return $data;
        }

        $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])->where(['service_type' => $name, 'trigered_by' => auth()->user()->id])->latest()->paginate(200);
        return $data;
    }

    public function overView(Request $request)
    {
        $tenure = $request['tenure'];

        $aeps = $this->userTable($tenure, 'aeps', $request);

        $bbps = $this->userTable($tenure, 'bbps', $request);

        $dmt = $this->userTable($tenure, 'dmt', $request);

        $pan = $this->userTable($tenure, 'pan', $request);

        $payout = $this->userTable($tenure, 'payout', $request);

        $lic = $this->userTable($tenure, 'lic', $request);

        $fastag = $this->userTable($tenure, 'fastag', $request);

        $cms = $this->userTable($tenure, 'cms', $request);

        $cms = $this->userTable($tenure, 'payout-commission', $request);

        $recharge = $this->userTable($tenure, 'recharge', $request);

        $funds = $this->fundRequests($tenure);

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
        ];

        return response($array);
    }

    public function userTable($tenure, $category, $request)
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
            ->where(['transactions.trigered_by' => auth()->user()->id, 'service_type' => $category]);
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

    public function fundRequests($tenure)
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
            ->where(['funds.approved' => 0, 'funds.user_id' => auth()->user()->id])->count();

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
        ];

        return response($array);
    }
}
