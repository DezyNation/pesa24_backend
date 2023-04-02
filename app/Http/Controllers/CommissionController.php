<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function aepsCommssion($amount, $user_id)
    {
        $table = DB::table('a_e_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'a_e_p_s.package_id')
            ->where('package_user.user_id', $user_id)->where('a_e_p_s.from', '<', $amount)->where('a_e_p_s.to', '>=', $amount)
            ->get()[0];

        // return $table->fixed_charge;
        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
        // return $role_commission;
        $opening_balance = $user->wallet;

        if ($is_flat) {
            $debit = $fixed_charge;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = $amount * $fixed_charge / 100;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        // return $opening_balance;

        $transaction_id = "COM" . strtoupper(Str::random(9));
        $user->update([
            'wallet' => $closing_balance
        ]);
        $this->transaction($debit, 'Commission AePS', 'withdrawal', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->parentCommission($amount, $parent_id[0]);
        }

        return $table;
    }

    public function parentCommission($amount, $user_id)
    {
        $table = DB::table('a_e_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'a_e_p_s.package_id')
            ->where('package_user.user_id', $user_id)->where('a_e_p_s.from', '<', $amount)->where('a_e_p_s.to', '>=', $amount)
            ->get()[0];

        // return $table->fixed_charge;
        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
        // return $role_commission;
        $opening_balance = $user->wallet;

        if ($is_flat) {
            $debit = 0;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = 0;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        // return $opening_balance;

        $transaction_id = "COM" . strtoupper(Str::random(9));
        $user->update([
            'wallet' => $closing_balance
        ]);
        $this->transaction($debit, 'Commission AePS', 'withdrawal', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->parentCommission($amount, $parent_id[0]);
        }

        return $table;
    }

    public function panCommission($name, $user_id, $amount)
    {
        $table = DB::table('p_a_n_s')
            ->join('package_user', 'package_user.package_id', '=', 'p_a_n_s.package_id')
            ->where('package_user.user_id', $user_id)->where('p_a_n_s.name', $name)
            ->get()[0];

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->pluck($role_commission_name);

        $opening_balance = $user->wallet;

        if ($is_flat) {
            $debit = $amount + $fixed_charge;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = $amount + $amount * $fixed_charge / 100;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'PAN Commissions', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);


        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->panCommission($name, $parent_id[0], $amount);
        }

        return $table;
    }

    public function dmtCommission($user_id, $name, $amount)
    {
        $table = DB::table('p_a_n_s')
            ->join('package_user', 'package_user.package_id', '=', 'd_m_t_s.package_id')
            ->where('package_user.user_id', $user_id)->where('d_m_t_s.name', $name)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->pluck($role_commission_name);
        $opening_balance = $user->wallet;

        if ($is_flat) {
            $debit = $amount + $fixed_charge;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = $amount + $amount * $fixed_charge / 100;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'PAN Commissions', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->dmtCommission($parent_id, $name, $amount);
        }

        return $table;
    }

    public function payotCommission($user_id, $amount)
    {
        $table = DB::table('p_a_n_s')
            ->join('package_user', 'package_user.package_id', '=', 'payoutcommissions.package_id')
            ->where('package_user.user_id', $user_id)->where('payoutcommissions.from', '<', $amount)
            ->where('payoutcommissions.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->pluck($role_commission_name);
        $opening_balance = $user->wallet;

        if ($is_flat) {
            $debit = $amount + $fixed_charge;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = $amount + $amount * $fixed_charge / 100;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'PAN Commissions', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->payotCommission($parent_id, $amount);
        }

        return $table;
    }
}
