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
        if (is_null($name)) {
            $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where('trigered_by', auth()->user()->id)->latest()->paginate(20);
            return $data;
        }

        $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where(['service_type' => $name, 'trigered_by' => auth()->user()->id])->latest()->paginate(20);
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
        $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where(['trigered_by' => auth()->user()->id])->whereJsonContains('metadata->status', true)->paginate(100);
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
}
