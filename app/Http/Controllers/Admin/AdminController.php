<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Package;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class AdminController extends Controller
{
    public function roleUser($role)
    {
        $role = User::role($role)->paginate(20);

        return $role;
    }

    public function logins($count)
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
                ->paginate(10);
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
            case 'aeps':
                $data = DB::table('a_e_p_s')
                    ->join('packages', 'packages.id', '=', 'a_e_p_s.package_id')
                    ->where('a_e_p_s.package_id', $id)
                    ->select('a_e_p_s.*')
                    ->get();
                break;

            case 'aeps-statement':
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

            default:
                $data = response("Invalid parameter was sent.", 404);
                break;
        }

        return $data;
    }

    public function packages(Request $request)
    {

        if (is_null($request->page)) {
            $data = DB::table('packages')->where('organization_id', auth()->user()->organization_id)->get(['id', 'name']);
        } else {
            $data = DB::table('packages')
                ->join('users', 'users.id', '=', 'packages.user_id')
                ->where('packages.organization_id', auth()->user()->organization_id)->select('packages.name', 'packages.id', 'users.name as user_name')->paginate(20);
        }
        return $data;
    }

    public function packagesId(Request $request, $id)
    {
        $data = DB::table('packages')->where('id', $id)->update([
            'name' => $request['name'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
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
        $data = DB::table('packages')->where('organization_id', auth()->user()->organizaation_id)->update([
            $request['switch'] => $request['value'],
            'updated_at' => now()
        ]);

        return $data;
    }

    public function admins()
    {
        $users = User::role('admin')->get();
        return $users;
    }

    public function assignPermission(Request $request)
    {
        $user = User::role('admin')->findOrFail($request['userId']);
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
        switch ($name) {
            case 'aeps':
                $request->validate([]);
                $data = DB::table('a_e_p_s')
                    ->update([]);
                break;

            case 'dmt':
                $data = DB::table('d_m_t_s')
                    ->updateOrInsert(['from' => $request['from'], 'to' => $request['to'], 'package_id' => $request['package_id']], $request->except('id'));
                break;

            case 'payout':
                $data = DB::table('payoutcommissions')->where('id', $request['id'])
                    ->update([
                        'from' => $request['from'],
                        'to' => $request['to'],
                        'name' => $request['name'],
                        'gst' => $request['gst'],
                        'is_flat' => $request['is_flat'],
                        'fixed_charge' => $request['fixed_charge'],
                        'super_distributor_commission' => $request['super_distributor_commission'],
                        'distributor_commission' => $request['distributor_commission'],
                        'retailer_commission' => $request['retailer_commission'],
                        'updated_at' => now()
                    ]);
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
        $data = DB::table('users')->where('organization_id', auth()->user()->organization_id)->get(['account_number', 'passbook', 'name', 'ifsc', 'bank_name', 'is_verified', 'bank_account_remarks', 'id']);
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
        $this->transaction(0, 'Admin funds added', 'admin-funds', auth()->user()->id, $wallet, $transaction_id, $amount, json_encode($metadata), $request['amount']);
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
}
