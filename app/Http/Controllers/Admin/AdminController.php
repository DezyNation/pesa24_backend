<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Exports\UsersExport;
use Illuminate\Http\Request;
use App\Exports\Admin\FundReport;
use Illuminate\Support\Facades\DB;
use App\Exports\Admin\PayoutExport;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class AdminController extends Controller
{
    public function roleUser(Request $request, $role)
    {
        $search = $request['search'];
        $org_id = auth()->user()->organization_id;
        if (!empty($search) || !is_null($search)) {
            $user = User::role($role)->with('packages:name')->where(['organization_id' => $org_id])->where('users.phone_number', 'like', '%' . $search . '%')->paginate(200);
            return $user;
        }
        $role = User::role($role)->paginate(200);

        return $role;
    }

    public function logins($count = null)
    {
        if (!is_null($count)) {
            $data = DB::table('logins')
                ->join('users', 'users.id', '=', 'logins.user_id')
                ->select('users.name', 'users.phone_number', 'logins.*')
                ->latest()->take($count)->get();
        } else {
            $data = DB::table('logins')
                ->join('users', 'users.id', '=', 'logins.user_id')
                ->select('users.name', 'users.phone_number', 'logins.*')
                ->latest()
                ->get();
        }

        return $data;
    }

    public function active($id, $bool)
    {
        User::role('retailer')->where('id', $id)->update([
            'is_active' => $bool
        ]);

        return response()->noContent();
    }


    public function blockAdmin($id, $bool)
    {
        User::role('admin')->where('id', $id)->update([
            'is_active' => $bool
        ]);

        return response()->noContent();
    }

    public function commissions()
    {
        $data = DB::table('commissions')
            ->join('packages', 'packages.id', '=', 'commissions.package_id')
            ->latest('commissions.created_at')
            ->get();

        return $data;
    }

    public function commissionsPackage($name, $id)
    {

        switch ($name) {
            case 'aeps-cash-withdrawal':
                $data = DB::table('a_e_p_s')
                    ->join('packages', 'packages.id', '=', 'a_e_p_s.package_id')
                    ->where('a_e_p_s.package_id', $id)
                    ->select('a_e_p_s.*')
                    ->get();
                break;


            case 'aeps-aadhaar-pay':
                $data = DB::table('aadhaar_pays')
                    ->join('packages', 'packages.id', '=', 'aadhaar_pays.package_id')
                    ->where('aadhaar_pays.package_id', $id)
                    ->select('aadhaar_pays.*')
                    ->get();
                break;

            case 'aeps-mini-statement':
                $data = DB::table('ae_p_s_mini_statements')
                    ->join('packages', 'packages.id', '=', 'ae_p_s_mini_statements.package_id')
                    ->where('ae_p_s_mini_statements.package_id', $id)
                    ->select('ae_p_s_mini_statements.*')
                    ->get();
                break;

            case 'dmt':
                $data = DB::table('d_m_t_s')
                    ->join('packages', 'packages.id', '=', 'd_m_t_s.package_id')
                    ->where('d_m_t_s.package_id', $id)
                    ->select('d_m_t_s.*')
                    ->get();
                break;

            case 'payout':
                $data = DB::table('payoutcommissions')
                    ->join('packages', 'packages.id', '=', 'payoutcommissions.package_id')
                    ->where('payoutcommissions.package_id', $id)
                    ->select('payoutcommissions.*')
                    ->get();
                break;

            case 'recharge':
                $data = DB::table('recharges')
                    ->join('packages', 'packages.id', '=', 'recharges.package_id')
                    ->where('recharges.package_id', $id)
                    ->select('recharges.*')
                    ->get();
                break;

            case 'bbps':
                $data = DB::table('b_b_p_s')
                    ->join('packages', 'packages.id', '=', 'b_b_p_s.package_id')
                    ->where('b_b_p_s.package_id', $id)
                    ->select('b_b_p_s.parents', 'b_b_p_s.category', 'b_b_p_s.operator_name', 'b_b_p_s.from', 'b_b_p_s.to', 'b_b_p_s.fixed_charge', 'b_b_p_s.is_flat', 'b_b_p_s.gst', 'b_b_p_s.retailer_commission', 'b_b_p_s.distributor_commission', 'b_b_p_s.super_distributor_commission', 'b_b_p_s.admin_commission', 'b_b_p_s.id')
                    ->get();
                break;

            case 'fastag':
                $data = DB::table('fasttag_commissions')
                    ->join('packages', 'packages.id', '=', 'fasttag_commissions.package_id')
                    ->where('fasttag_commissions.package_id', $id)
                    ->select('fasttag_commissions.*')
                    ->get();
                break;

            case 'lic':
                $data = DB::table('lic_commissions')
                    ->join('packages', 'packages.id', '=', 'lic_commissions.package_id')
                    ->where('lic_commissions.package_id', $id)
                    ->select('lic_commissions.*')
                    ->get();
                break;

            case 'cms':
                $data = DB::table('cms_commissions')
                    ->join('packages', 'packages.id', '=', 'cms_commissions.package_id')
                    ->where('cms_commissions.package_id', $id)
                    ->select('cms_commissions.*')
                    ->get();
                break;
            default:
                $data = response("Invalid parameter was sent.", 404);
                break;
        }

        return $data;
    }

    public function packages(Request $request)
    {

        if (is_null($request->page)) {
            $data = DB::table('packages AS p')
                ->leftJoin('package_user AS pt', 'p.id', '=', 'pt.package_id')
                ->leftJoin('users AS u', 'pt.user_id', '=', 'u.id')
                ->join('users AS a', 'a.id', '=', 'p.user_id')
                ->where('p.organization_id', auth()->user()->organization_id)
                ->select('p.id', 'p.name', 'p.status', 'p.is_default', 'a.name AS user_name', DB::raw('COUNT(u.id) AS assigned_users_count'))
                ->groupBy('p.id', 'p.name')
                ->get();
        } else {
            $data = DB::table('packages AS p')
                ->leftJoin('package_user AS pt', 'p.id', '=', 'pt.package_id')
                ->leftJoin('users AS u', 'pt.user_id', '=', 'u.id')
                ->join('users AS a', 'a.id', '=', 'p.user_id')
                ->where('p.organization_id', auth()->user()->organization_id)
                ->select('p.id', 'p.name', 'p.status', 'p.is_default', 'a.name AS user_name', DB::raw('COUNT(u.id) AS assigned_users_count'))
                ->groupBy('p.id', 'p.name')
                ->paginate(200);
        }
        return $data;
    }

    public function packagesId($id)
    {
        $data = DB::table('packages')->where(['id' => $id, 'organization_id' => auth()->user()->organization_id])->delete();
        return $data;
    }

    public function packageCreate(Request $request)
    {
        $data = Package::create([
            'name' => $request['package_name'],
            'organization_id' => auth()->user()->organization_id,
            'is_default' => $request['is_default'],
            'role_id' => $request['roleId'],
            'user_id' => auth()->user()->id
        ]);

        return $data;
    }

    public function packageSwitch(Request $request)
    {
        $data = DB::table('packages')->where(['organization_id' => auth()->user()->organization_id, 'id' => $request['packageId']])->update([
            $request['column'] => $request['value'],
            'updated_at' => now()
        ]);

        return $data;
    }

    public function defaultPackage(Request $request)
    {
        $query = DB::table('packages')->where(['organization_id' => auth()->user()->organization_id]);
        $query->update(['is_default' => 0]);
        $data = $query->where('id', $request['packageId'])->update(['is_default' => 1]);

        return $data;
    }

    public function admins()
    {
        $users = User::role('admin')->get();
        return $users;
    }

    public function assignPermission(Request $request)
    {
        $user = User::find($request['userId']);
        $user->givePermissionTo($request['permission']);

        return response()->json(['message' => "Permission Assigned"]);
    }

    public function permissions()
    {
        $permission = Permission::get();
        return $permission;
    }

    public function newAdmin(Request $request)
    {
        $user = User::findOrFail($request['userId']);
        $user->syncRoles([$request['role']]);

        return response()->noContent();
    }

    public function updateCommission(Request $request, $name)
    {
        $package_id = DB::table('packages')->where('id', $request['package_id'])->get();
        if ($package_id[0]->organization_id !== auth()->user()->organization_id) {
            return response("Unauthorized action.", 403);
        }
        switch ($name) {
            case 'aeps-cash-withdrawal':
                $request->validate([
                    'from' => 'required',
                    'to' => 'required',
                    'package_id' => 'required',
                ]);
                $data = DB::table('a_e_p_s')
                    ->updateOrInsert(['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']], $request->only(['distributor_commission', 'super_distributor_commission', 'retailer_commission', 'gst', 'is_flat', 'fixed_charge']));
                break;

            case 'aeps-aadhaar-pay':
                $request->validate([
                    'from' => 'required',
                    'to' => 'required',
                    'package_id' => 'required',
                ]);
                $data = DB::table('aadhaar_pays')
                    ->updateOrInsert(
                        ['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']],
                        $request->only(['distributor_commission', 'super_distributor_commission', 'retailer_commission', 'gst', 'is_flat', 'fixed_charge'])
                    );
                break;

            case 'aeps-mini-statement':
                $request->validate([
                    'fixed_charge' => 'required',
                    'package_id' => 'required',
                ]);
                $data = DB::table('ae_p_s_mini_statements')
                    ->updateOrInsert(['fixed_charge' => $request['fixed_charge'], 'package_id' => $request['package_id']], $request->only(['distributor_commission', 'super_distributor_commission', 'retailer_commission', 'gst', 'is_flat']));
                break;

            case 'aeps-aadhaar-pay':
                $request->validate([
                    'from' => 'required',
                    'to' => 'required',
                    'package_id' => 'required',
                ]);
                $data = DB::table('aadhaar_pays')
                    ->updateOrInsert(
                        ['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']],
                        $request->only(['distributor_commission', 'super_distributor_commission', 'retailer_commission', 'gst', 'is_flat', 'fixed_charge'])
                    );
                break;

            case 'dmt':
                $request->validate([
                    'from' => 'required',
                    'to' => 'required',
                    'package_id' => 'required',
                ]);
                $data = DB::table('d_m_t_s')
                    ->updateOrInsert(['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']], $request->only(['from', 'to', 'gst', 'retailer_commission', 'distributor_commission', 'super_distributor_commission', 'fixed_charge', 'is_flat']));
                break;

            case 'payout':
                $data = DB::table('payoutcommissions')
                    ->updateOrInsert(
                        [
                            'from' => $request['from'],
                            'to' => $request['to'],
                            'package_id' => $request['package_id']
                        ],
                        [
                            'name' => $request['name'],
                            'gst' => $request['gst'] ?? 0,
                            'is_flat' => $request['is_flat'] ?? 0,
                            'fixed_charge' => $request['fixed_charge'] ?? 0,
                            'super_distributor_commission' => $request['super_distributor_commission'] ?? 0,
                            'distributor_commission' => $request['distributor_commission'] ?? 0,
                            'retailer_commission' => $request['retailer_commission'] ?? 0,
                            'updated_at' => now()
                        ]
                    );
                break;

            case 'recharge':
                $data = DB::table('recharges')
                    ->updateOrInsert(
                        ['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']],
                        [
                            'operator' => $request['name'],
                            'gst' => $request['gst'],
                            'is_flat' => $request['is_flat'],
                            'eko_id' => 1,
                            'paysprint_id' => 1,
                            'type' => 'prepaid',
                            'fixed_charge' => $request['fixed_charge'],
                            'super_distributor_commission' => $request['super_distributor_commission'],
                            'distributor_commission' => $request['distributor_commission'],
                            'retailer_commission' => $request['retailer_commission'],
                            'updated_at' => now()
                        ]
                    );
                break;

            case 'bbps':
                $data = DB::table('b_b_p_s')->updateOrInsert(
                    ['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']],
                    $request->except('id', 'actions')
                );

                break;

            case 'fastag':
                $data = DB::table('fasttag_commissions')->updateOrInsert(
                    ['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']],
                    $request->only(['distributor_commission', 'super_distributor_commission', 'retailer_commission', 'gst', 'is_flat', 'fixed_charge'])
                );
                break;

            case 'lic':
                $data = DB::table('lic_commissions')->updateOrInsert(
                    ['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']],
                    $request->only(['distributor_commission', 'super_distributor_commission', 'retailer_commission', 'gst', 'is_flat', 'fixed_charge'])
                );
                break;

            case 'cms':
                $data = DB::table('cms_commissions')->updateOrInsert(
                    ['biller_id' => $request['biller_id'], 'package_id' => $request['package_id']],
                    $request->only(['distributor_commission', 'super_distributor_commission', 'retailer_commission', 'gst', 'is_flat', 'fixed_charge', 'provider', 'parents'])
                );
                break;

            default:
                $data = response("Invalid parameter was sent.", 404);
                break;
        }

        return $data;
    }

    public function packageDefault($id, $bool)
    {
        $data = DB::table('packages')->where('id', $id)->update(['is_default' => $bool]);
        return $data;
    }

    public function settlementAccount()
    {
        $data = DB::table('users')->where('organization_id', auth()->user()->organization_id)->get(['account_number', 'passbook', 'name', 'ifsc', 'bank_name', 'is_verified', 'bank_account_remarks', 'id', 'paysprint_bank_code']);
        return $data;
    }

    public function updateSettlementAccount(Request $request)
    {
        $request->only([
            'id',
            'is_verified',
            'bank_account_remarks'
        ]);
        $data = DB::table('users')->where(['organization_id' => auth()->user()->organization_id, 'id' => $request['id']])
            ->update([
                'is_verified' => $request['is_verified'],
                'bank_account_remarks' => $request['bank_account_remarks']
            ]);
        return $data;
    }

    public function addAdminFunds(Request $request)
    {
        $wallet = auth()->user()->wallet;
        $amount = $wallet + $request['amount'];

        $transaction_id = "FUND" . strtoupper(Str::random(5) . uniqid());

        $metadata = [
            'status' => true,
            'transaction_for' => 'Fund add in Admin account.',
            'refernce_id' => $transaction_id,
            'remarks' => $request['remarks']
        ];
        $this->transaction(0, 'Admin funds added', 'ADMIN-FUNDS', auth()->user()->id, $wallet, $transaction_id, $amount, json_encode($metadata), $request['amount']);
        DB::table('users')->where('id', auth()->user()->id)->update([
            'wallet' => $amount,
            'updated_at' => now()
        ]);

        return true;
    }

    public function adminFundsRecords()
    {
        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->where(['users.organization_id' => 5, 'transactions.service_type' => 'admin-funds'])->select('transactions.*', 'users.name', 'admin.name as done_by')->get();
        return $data;
    }

    public function assignPackage(Request $request)
    {
        $data = DB::table('package_user')->updateOrInsert(
            [
                'user_id' => $request['user_id'],
            ],
            [
                'package_id' => $request['package_id'],
                'updated_at' => now()
            ]
        );

        return $data;
    }

    public function packageCount($id)
    {
        $data = DB::table('package_user')
            ->join('packages', 'packages.id', '=', 'package_user.package_id')
            ->where(['package_user.package_id' => $id, 'packages.organization_id' => auth()->user()->id])->count();
        return $data;
    }

    public function userRemarks(Request $request)
    {
        $request->validate([
            'userId' => 'required', 'exists:users,id',
            // 'remarks' => 'required'
        ]);

        $data = DB::table('users')->where('id', $request['userId'])->update([
            'delete_remarks' => $request['remarks'],
            'updated_at' => now()
        ]);

        return $data;
    }

    public function parentUser(Request $request)
    {
        $request->validate([
            'userId' => 'required', 'exists:users,id',
            'role' => 'required', 'exists:roles,name'
            // 'remarks' => 'required'
        ]);
        $data = DB::table('user_parent')->updateOrInsert(
            ['user_id' => $request['userId']],
            ['parent_id' => $request['parent']]
        );

        User::find($request['userId'])->syncRoles($request['role']);

        return true;
    }

    public function getRoleParent(Request $request)
    {
        $role = User::find($request['userId'])->getRoleNames();
        $parent = DB::table('user_parent')->where('user_parent.user_id', $request['userId'])
            ->join('users as parents', 'parents.id', '=', 'user_parent.parent_id')
            ->select('parents.name', 'user_parent.parent_id')
            ->get();
        return ['parent' => $parent, 'role' => $role];
    }

    public function removeParent(Request $request)
    {
        $data = DB::table('user_parent')->where('user_id', $request['userId'])->delete();
        return $data;
    }

    public function deleteCommission($name, $id)
    {
        switch ($name) {
            case 'aeps-cash-withdrawal':
                $data = DB::table('a_e_p_s')
                    ->where('id', $id)
                    ->delete();
                break;


            case 'aeps-aadhaar-pay':
                $data = DB::table('aadhaar_pays')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'aeps-mini-statement':
                $data = DB::table('ae_p_s_mini_statements')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'dmt':
                $data = DB::table('d_m_t_s')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'payout':
                $data = DB::table('payoutcommissions')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'recharge':
                $data = DB::table('recharges')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'bbps':
                $data = DB::table('b_b_p_s')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'fastag':
                $data = DB::table('fasttag_commissions')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'lic':
                $data = DB::table('lic_commissions')
                    ->where('id', $id)
                    ->delete();
                break;

            case 'cms':
                $data = DB::table('cms_commissions')
                    ->where('id', $id)
                    ->delete();
                break;
            default:
                $data = response("Invalid parameter was sent.", 404);
                break;
        }

        return $data;
    }

    public function roleCount($role)
    {
        $data = User::where('organization_id', auth()->user()->organization_id)->role($role)->count();
        return $data;
    }

    public function sumAmounts()
    {
        $wallet_sum = User::role(['retailer', 'distributor', 'super_distributor'])->sum('wallet');
        $capped_sum = User::role(['retailer', 'distributor', 'super_distributor'])->sum('minimum_balance');
        // $wallet_sum = $table->sum('wallet');
        return ['capping_sum' => $capped_sum, 'wallet_sum' => $wallet_sum];
    }

    public function sumCategory(Request $request)
    {
        $tennure = $request['tennure'];

        // $aeps = $this->table($tennure, 'aeps');

        // $bbps = $this->table($tennure, 'bbps');;

        // $dmt = $this->table($tennure, 'dmt');;

        // $pan = $this->table($tennure, 'pan');;

        $payout = $this->table($tennure, 'payout', $request);

        $payout_commission = $this->table($tennure, 'payout-commission', $request);

        $recharge = $this->table($tennure, 'recharge', $request);

        $recharge_commission = $this->table($tennure, 'recharge-commission', $request);

        $wallet = $this->roleWalletSum();

        $payout_transaction = $this->payoutTransactions();

        // $lic = $this->table($tennure, 'lic');;

        // $fastag = $this->table($tennure, 'fastag');

        // $cms = $this->table($tennure, 'cms');

        // $recharge = $this->table($tennure, 'recharge');

        $funds = $this->fundRequestCount();

        $users = $this->countLogins($tennure);



        $array = [
            // $aeps,
            // $bbps,
            // $dmt,
            // $pan,
            $payout,
            // $lic,
            // $fastag,
            // $cms,
            // $recharge,
            $wallet,
            $funds,
            $users,
            $payout_commission,
            $payout_transaction,
            $recharge,
            $recharge_commission
        ];

        return response($array);
    }

    public function cmsBiller(Request $request)
    {
        $data = DB::table('cms_billers')->insert([
            'name' => $request['name'],
            'biller_id' => $request['billerId']
        ]);

        return $data;
    }

    public function getCmsBiller()
    {
        $data = DB::table('cms_billers')->get();
        return $data;
    }

    public function deleteCmsBiller($id)
    {
        $data = DB::table('cms_billers')->where('id', $id)->delete();
        return $data;
    }

    public function table($tennure, $category, $request)
    {
        $tennure;
        switch ($tennure) {
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
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where(['users.organization_id' => auth()->user()->organization_id, 'service_type' => $category]);
        return [
            $category => [
                'credit' => $table->sum('credit_amount'),
                'debit' => $table->sum('debit_amount'),
                'count' => $table->count()
            ]
        ];
    }

    public function fundRequestCount()
    {
        $not_approved = DB::table('funds')
            ->join('users', 'users.id', '=', 'funds.user_id')
            ->where(['users.organization_id' => auth()->user()->id])->where('funds.status', '!==', 'approved')->count();

        $all = DB::table('funds')
            ->join('users', 'users.id', '=', 'funds.user_id')
            ->where('users.organization_id', auth()->user()->organization_id)
            ->count();

        return [
            'funds' => [
                'approved' => $all - $not_approved,
                'not_approved' => $not_approved,
                'all' => $all
            ]
        ];
    }

    public function countLogins($tennure)
    {
        switch ($tennure) {
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

        $logins = DB::table('logins')->whereBetween('logins.created_at', [$start, $end])
            ->join('users', 'users.id', '=', 'logins.user_id')
            ->where('users.organization_id', auth()->user()->organization_id)
            ->count();
        $registration = DB::table('users')->whereBetween('created_at', [$start, $end])->where('organization_id', auth()->user()->organization_id)->count();
        $support_tickets = DB::table('tickets')->whereBetween('created_at', [$start, $end])->where('organization_id', auth()->user()->organization_id)->count();
        return [
            'users' => [
                'login' => $logins,
                'registration' => $registration,
                'tickets' => $support_tickets
            ]
        ];
    }

    public function userPermission($id = null)
    {

        $user = User::find($id ?? auth()->user()->id);
        $permissions = $user->getAllPermissions();
        return $permissions;
    }

    public function settlementRequest()
    {
        $data = DB::table('settlement_request')
            ->join('users', 'users.id', '=', 'settlement_request.user_id')
            ->where('users.organization_id', auth()->user()->organization_id)
            ->select('users.name', 'users.email', 'users.phone_number', 'users.id as user_id', 'users.account_number', 'users.ifsc', 'users.bank_name', 'users.paysprint_bank_code', 'users.wallet', 'settlement_request.*')
            ->get();

        return $data;
    }

    public function updateSettlementRequest(Request $request)
    {
        $request->validate([
            'adminRemarks' => 'required',
            'status' => 'required',
            'approved' => 'required'
        ]);

        $data = DB::table('settlement_request')->where('id', $request['id'])->update([
            'admin_remarks' => $request['adminRemarks'],
            'status' => $request['status'],
            'approved' => $request['approved'],
            'updated_at' => now()
        ]);

        return $data;
    }

    public function pendingRequest(): array
    {
        $accounts = DB::table('users')->where(['organization_id' => auth()->user()->organization_id, 'paysprint_bene_id' => null])->count();
        $kyc = DB::table('users')->where(['organization_id' => auth()->user()->organization_id, 'profile' => 0])->count();
        $tickets = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.user_id')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'tickets.status' => 'created'])->count();
        $funds = DB::table('funds')
            ->join('users', 'users.id', '=', 'funds.user_id')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'funds.status' => 'pending'])->count();
        $bbps = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'transactions.service_type' => 'bbps'])->whereJsonContains('transactions.metadata->status', 'pending')->count();
        $recharge = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'transactions.service_type' => 'recharge'])->whereJsonContains('transactions.metadata->status', 'pending')->count();
        $dmt = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'transactions.service_type' => 'dmt'])->whereJsonContains('transactions.metadata->status', 'pending')->count();
        return [
            'accounts' => $accounts,
            'tickets' => $tickets,
            'profile' => $kyc,
            'bbps' => $bbps,
            'dmt' => $dmt,
            'rcharge' => $recharge,
            'funds' => $funds
        ];
    }

    public function roleWalletSum(): array
    {
        $retailer = User::role('retailer')->sum('wallet');
        $distributor = User::role('distributor')->sum('wallet');
        $super_distributor = User::role('super_distributor')->sum('wallet');

        return [
            'retailer' => $retailer,
            'distributor' => $distributor,
            'super_distributor' => $super_distributor
        ];
    }

    public function payoutTransactions()
    {
        $processing = DB::table('payouts')
            ->join('users', 'users.id', '=', 'payouts.user_id')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'payouts.status' => 'processing']);

        $processing = collect($processing);

        $processed = DB::table('payouts')
            ->join('users', 'users.id', '=', 'payouts.user_id')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'payouts.status' => 'processed'])->count();

        $processed = collect($processed);

        $reversed = DB::table('payouts')
            ->join('users', 'users.id', '=', 'payouts.user_id')
            ->where(['users.organization_id' => auth()->user()->organization_id, 'payouts.status' => 'reversed'])->count();

        $reversed = collect($reversed);

        $payout_status = [
            'processing_payouts' => [
                'count' => $processing->count(),
                'sum' => $processing->sum('amount')
            ],
            'processed_payouts' => [
                'count' => $processed->count(),
                'sum' => $processed->sum('amount')
            ],
            'reversed_payouts' => [
                'count' => $reversed->count(),
                'sum' => $reversed->sum('amount')
            ]
        ];

        return $payout_status;
    }

    public function userReports(Request $request, $name, $id,)
    {
        $search = $request['search'];
        if (!empty($search) || !is_null($search)) {
            $data = DB::table('transactions')->where('trigered_by', $id)->orWhere('user_id', $id)->where('transaction_for', 'like', '%' . $search . '%')->orWhere('transaction_id', 'like', '%' . $search . '%')->latest()->orderByDesc('transactions.id')->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'search' => $request['search']]);
            return $data;
        }

        if ($name == 'all') {
            $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where(function ($q) use ($id) {
                $q->where('trigered_by', $id);
                // ->orWhere('user_id', $id);
            })->latest()->orderByDesc('transactions.id')->get();

            return $data;
        }

        $data = DB::table('transactions')->whereBetween('created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])->where('service_type', $name)->where(function ($q) use ($id) {
            $q->where('trigered_by', $id);
            // ->orWhere('user_id', $id);
        })->latest()->orderByDesc('transactions.id')->get();
        return $data;
    }

    public function walletTransfers(Request $request, $id = null)
    {
        if (!is_null($request['userId']) || !empty($request['userId'])) {
            $request->validate([
                'userType' => 'required'
            ]);
            if ($request['userType'] == 'sender') {
                $query_for = "sender_id";
            } else {
                $query_for = "reciever_id";
            }
            $data = DB::table('money_transfers')
                ->join('users as recievers', 'recievers.id', '=', 'money_transfers.reciever_id')
                ->join('users as senders', 'senders.id', '=', 'money_transfers.sender_id')
                ->where("money_transfers.$query_for", $request['userId'])
                ->whereBetween('money_transfers.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->select('recievers.name as reciever_name', 'recievers.phone_number as reciever_phone', 'recievers.id as reciever_id', 'money_transfers.*', 'senders.name as sender_name', 'senders.id as sender_id', 'senders.phone_number as sender_phone')
                ->latest()
                ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to'], 'userType' => $request['userType'], 'userId' => $request['userId']]);
        } else {
            $data = DB::table('money_transfers')
                ->join('users as recievers', 'recievers.id', '=', 'money_transfers.reciever_id')
                ->join('users as senders', 'senders.id', '=', 'money_transfers.sender_id')
                ->whereBetween('money_transfers.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->select('recievers.name as reciever_name', 'recievers.phone_number as reciever_phone', 'recievers.id as reciever_id', 'money_transfers.*', 'senders.name as sender_name', 'senders.id as sender_id', 'senders.phone_number as sender_phone')
                ->latest()
                ->paginate(200)->appends(['from' => $request['from'], 'to' => $request['to']]);
        }

        return $data;
    }


    public function printUserReports(Request $request, $id)
    {
        $type = $request['type'];
        switch ($type) {
            case 'payouts':

                $processing = $request['report'];
                $payout = $this->payoutReports($request, $processing);
                return $payout;
                break;

            case 'fund-requests':
                $data = $this->fundUserReports($request);
                return $data;
                break;

            case 'ledger':
                $data = $this->printLedger($request);
                return $data;
                break;

            default:
                return 'error';
                break;
        }
    }
    public function printReports(Request $request)
    {
        $type = $request['type'];
        switch ($type) {
            case 'payouts':

                $processing = $request['report'];
                $payout = $this->payoutReports($request, $processing);
                return $payout;
                break;

            case 'fund-requests':
                $data = $this->fundReports($request);
                return $data;
                break;

            case 'ledger':
                $data = $this->printLedger($request);
                return $data;
                break;

            default:
                return 'error';
                break;
        }
    }

    public function fundReports(Request $request)
    {

        return Excel::download(new FundReport($request['from'], $request['to'], $request['search'], $request['userId'], $request['status']), 'fundreport.xlsx');

        if (!empty($request['search']) || !is_null($request['search'])) {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->where('funds.transaction_id', 'like', '%' . $request['search'] . '%')
                ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
            return $data;
        }


        if (!empty($request['userId']) || !is_null($request['userId'])) {
            if ($request['status'] == 'all') {
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.user_id', $request['userId'])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
                return $data;
            } else {
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.user_id', $request['userId'])->where('funds.status', $request['status'])->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
                return $data;
            }
        }

        if ($request['status'] == 'all') {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
            return $data;
        } else {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->whereBetween('funds.created_at', [$request['from'] ?? Carbon::now()->startOfDecade(), $request['to'] ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', $request['status'])->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.*', 'funds.id as fund_id', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id')->latest('funds.created_at')->get();
            return $data;
        }
    }


    public function payoutReports(Request $request, $processing)
    {

        return Excel::download(new PayoutExport($request['from'], $request['to'], $request['search'], $request['userId'], $request['status'], $processing), 'payout.xlsx');

        if (!empty($request['userId']) || !is_null($request['userId'])) {
            if (!empty($request['status']) || !is_null($request['status'])) {
                $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                    ->where([
                        'users.organization_id' => auth()->user()->organization_id,
                        'payouts.user_id' => $request['userId']
                    ])
                    ->where('payouts.status', $request['status'])
                    ->whereBetween('payouts.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                    ->select('payouts.*', 'users.name')->latest()->get();

                return $payout;
            } else {
                $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                    ->where([
                        'users.organization_id' => auth()->user()->organization_id,
                        'payouts.user_id' => $request['userId']
                    ])
                    ->whereBetween('payouts.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                    ->select('payouts.*', 'users.name')->latest()->get();

                return $payout;
            }
        }
        $search = $request['search'];
        if (!empty($search)) {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->where("payouts.account_number", 'LIKE', '%' . $search . '%')->orWhere("payouts.reference_id", 'LIKE', '%' . $search . '%')->orWhere("payouts.utr", 'LIKE', '%' . $search . '%')
                ->select('payouts.*', 'users.name')->latest()->get();

            return $payout;
        }
        if ($processing == 'all') {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->where('payouts.status', '!=', 'processing')
                ->whereBetween('payouts.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                ->select('payouts.*', 'users.name')->latest()->get();

            return $payout;
        } elseif ($processing == 'processing') {

            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->whereBetween('payouts.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                ->where('payouts.status', 'processing')->orWhere('payouts.status', 'pending')->orWhere('payouts.status', 'queued')
                ->select('payouts.*', 'users.name')->latest()->get();

            return $payout;
        } else {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->whereBetween('payouts.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                ->where('payouts.status', $processing)
                ->select('payouts.*', 'users.name')->latest()->get();

            return $payout;
        }
    }

    public function printLedger(Request $request)
    {
        return Excel::download(new UsersExport($request['from'], $request['to'], $request['search'], $request['userId']), 'ledger.xlsx');
    }

    public function marketOverview(Request $request)
    {
        $date = $request['date'] ?? Carbon::today();

        $lastTransactions = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', '!=', 'admin')
            ->select(DB::raw('MAX(transactions.id) as id'))
            ->whereDate('transactions.created_at', '<=', $date)
            ->groupBy('transactions.trigered_by')
            ->get();

        $transactions = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->whereIn('transactions.id', $lastTransactions->pluck('id'))
            ->select('transactions.*', 'users.name as user_name', 'users.phone_number as user_phone')
            ->get();

        $firstTransactions = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', '!=', 'admin')
            ->select(DB::raw('MIN(transactions.id) as id'))
            ->whereDate('transactions.created_at', '<=', $date)
            ->groupBy('transactions.trigered_by')
            ->get();

        $initialTransactions = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.trigered_by')
            ->whereIn('transactions.id', $firstTransactions->pluck('id'))
            ->select('transactions.*', 'users.name as user_name', 'users.phone_number as user_phone')
            ->get();

        return [
            'opening_transactions' => $initialTransactions,
            'opening_balance' => $initialTransactions->sum('opening_balance'),
            'closing_transactions' => $transactions,
            'closing_balance' => $transactions->sum('closing_balance')
        ];
        return $transactions;
    }

    public function adminOtp($option)
    {

        $otp = rand(1000, 9999);
        $hash = Hash::make($otp);
        User::where('id', auth()->user()->id)->update(['otp' => $hash, 'otp_generated_at' => now()]);
        if ($option == 'profile') {
            $phone = 7838074742;
            $text = "$otp is your verification OTP for change your Mpin/Password. '-From P24 Technology Pvt. Ltd";
            $otp =  Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
        } else {
            $phone = 8982466893;
            $to = 'vaslibhai646@gmail.com';
            $name = 'Vasli';
            $text = "$otp is your verification OTP for change your Mpin/Password. '-From P24 Technology Pvt. Ltd";
            Http::post("http://alerts.prioritysms.com/api/web2sms.php?workingkey=Ab6a47904876c763b307982047f84bb80&to=$phone&sender=PTECHP&message=$text", []);
            Mail::raw("Hello Your one time password is $otp for transaction", function ($message) use ($to, $name) {
                $message->from('info@pesa24.co.in', 'Janpay');
                $message->to($to, $name);
                $message->subject('Authorize Transaction');
                $message->priority(1);
            });
        }

        return response()->noContent();
    }

    public function fetchRecharge(Request $request, $status)
    {
        $search = $request['search'];
        $user_id = $request['userId'];
        $from = $request['from'] ?? Carbon::today();
        $to = $request['to'] ?? Carbon::tomorrow();

        if (!is_null($search) || !empty($search)) {
            $data = DB::table('recharge_requests')
                ->join('users', 'users.id', '=', 'recharge_requests.user_id')
                ->select('recharge_requests.*', 'users.name', 'users.phone_number')
                ->where('reference_id', 'like', "%" . $search . "%")->orWhere('ca_number', 'like', "%" . $search . "%")
                ->latest('recharge_requests.created_at')->paginate(200);
            return $data;
        }

        if (!is_null($user_id) || !empty($user_id)) {
            $data = DB::table('recharge_requests')
                ->join('users', 'users.id', '=', 'recharge_requests.user_id')
                ->select('recharge_requests.*', 'users.name', 'users.phone_number')
                ->where('user_id', $user_id)->whereBetween('created_at', [$from, $to])
                ->latest('recharge_requests.created_at')->paginate(200)->appends(['userId' => $user_id, 'from' => $from, 'to' => $to, 'search' => $search]);
            return $data;
        }

        if ($status == 'pending') {
            $data = DB::table('recharge_requests')
                ->join('users', 'users.id', '=', 'recharge_requests.user_id')
                ->select('recharge_requests.*', 'users.name', 'users.phone_number')
                ->where('status', $status)
                ->latest('recharge_requests.created_at')
                ->get();
            // ->paginate(200)->appends(['userId' => $user_id, 'from' => $from, 'to' => $to, 'search'=> $search]);
            return $data;
        } else if ($status == 'all') {
            $data = DB::table('recharge_requests')
                ->join('users', 'users.id', '=', 'recharge_requests.user_id')
                ->select('recharge_requests.*', 'users.name', 'users.phone_number')
                ->latest('recharge_requests.created_at')
                ->paginate(200)->appends(['userId' => $user_id, 'from' => $from, 'to' => $to, 'search' => $search]);
            return $data;
        }

        $data = DB::table('recharge_requests')
            ->join('users', 'users.id', '=', 'recharge_requests.user_id')
            ->select('recharge_requests.*', 'users.name', 'users.phone_number')
            ->where('status', $status)
            ->latest('recharge_requests.created_at')->paginate(200)->appends(['userId' => $user_id, 'from' => $from, 'to' => $to, 'search' => $search]);
        return $data;
    }
}
