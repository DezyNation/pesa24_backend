<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Package;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class AdminController extends Controller
{
    public function roleUser($role)
    {
        $role = User::role($role)->paginate(20);

        return $role;
    }

    public function logins($count = null)
    {
        if (!is_null($count)) {
            $data = DB::table('logins')
                ->join('users', 'users.id', '=', 'logins.user_id')
                ->select('users.name', 'users.phone_number', 'logins.*')
                ->paginate($count);
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
        User::where('id', $id)->update([
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
                ->paginate(20);
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
        $table = DB::table('users')->where('organization_id', auth()->user()->organization_id);
        $capped_sum = $table->sum('minimum_balance');
        $wallet_sum = $table->sum('wallet');
        return ['capping_sum' => $capped_sum, 'wallet_sum' => $wallet_sum];
    }

    public function sumCategory(Request $request)
    {
        $tennure = $request['tennure'];

        $aeps = $this->table($tennure, 'aeps');

        $bbps = $this->table($tennure, 'bbps');;

        $dmt = $this->table($tennure, 'dmt');;

        $pan = $this->table($tennure, 'pan');;

        $payout = $this->table($tennure, 'payout');;

        $lic = $this->table($tennure, 'lic');;

        $fastag = $this->table($tennure, 'fastag');

        $cms = $this->table($tennure, 'cms');

        $recharge = $this->table($tennure, 'recharge');

        $funds = $this->fundRequestCount($tennure);

        $users = $this->countLogins($tennure);



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
            $users
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

    public function table($tennure, $category)
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
                $start = Carbon::today();
                $end = Carbon::tomorrow();
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

    public function fundRequestCount($tennure)
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

        $not_approved = DB::table('funds')
            ->join('users', 'users.id', '=', 'funds.user_id')
            ->whereBetween('funds.created_at', [$start, $end])
            ->where(['funds.approved' => 0, 'users.organization_id' => auth()->user()->id])->count();

        $all = DB::table('funds')
            ->join('users', 'users.id', '=', 'funds.user_id')
            ->whereBetween('funds.created_at', [$start, $end])
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
}
