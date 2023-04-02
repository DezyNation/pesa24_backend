<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Package;
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

    public function commissionsPackage($name)
    {

        switch ($name) {
            case 'aeps':
                $data = DB::table('a_e_p_s')
                    ->join('packages', 'packages.id', '=', 'a_e_p_s.package_id')
                    ->select('a_e_p_s.*')
                    ->latest('a_e_p_s.created_at')
                    ->get();
                break;

            case 'dmt':
                $data = DB::table('d_m_t_s')
                    ->join('packages', 'packages.id', '=', 'd_m_t_s.package_id')
                    ->select('d_m_t_s.*')
                    ->latest('d_m_t_s.created_at')
                    ->get();
                break;

            case 'payout':
                $data = DB::table('payoutcommissions')
                    ->join('packages', 'packages.id', '=', 'payoutcommissions.package_id')
                    ->select('payoutcommissions.*')
                    ->latest('payoutcommissions.created_at')
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
            $data = DB::table('packages')->where('organization_id', auth()->user()->organization_id)->get();
        } else {
            $data = DB::table('packages')->where('organization_id', auth()->user()->organization_id)->paginate(20);
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
            'role_id' => $request['roleId']
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
                $data = DB::table('d_m_t_s')->where('id', $request['id'])
                    ->updateOrInsert(['from' => $request['from'], 'to' => $request['to']], $request->all());
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
}
