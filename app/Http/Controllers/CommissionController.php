<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    /*-------------------------------------AePS Withdrawal Commission-------------------------------------*/
    public function aepsCommssion($amount, $user_id)
    {
        $table = DB::table('a_e_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'a_e_p_s.package_id')
            ->where('package_user.user_id', $user_id)->where('a_e_p_s.from', '<', $amount)->where('a_e_p_s.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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
            $this->parentAepsCommission($amount, $parent_id[0]);
        }

        return $table;
    }

    public function parentAepsCommission($amount, $user_id)
    {
        $table = DB::table('a_e_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'a_e_p_s.package_id')
            ->where('package_user.user_id', $user_id)->where('a_e_p_s.from', '<', $amount)->where('a_e_p_s.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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
            $this->parentAepsCommission($amount, $parent_id[0]);
        }

        return $table;
    }
    /*-------------------------------------AePS Withdrawal Commission-------------------------------------*/


    /*-------------------------------------AePS Mini-statement Commission-------------------------------------*/

    public function aepsMiniCommssion($amount, $user_id)
    {
        $table = DB::table('a_e_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'ae_p_s_mini_statements.package_id')
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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
            $this->parentAepsMiniCommission($amount, $parent_id[0]);
        }

        return $table;
    }

    public function parentAepsMiniCommission($amount, $user_id)
    {
        $table = DB::table('ae_p_s_mini_statements')
            ->join('package_user', 'package_user.package_id', '=', 'ae_p_s_mini_statements.package_id')
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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
            $this->parentAepsCommission($amount, $parent_id[0]);
        }

        return $table;
    }

    /*-------------------------------------AePS Mini-statement Commission-------------------------------------*/


    /*-------------------------------------PAN Commissions-------------------------------------*/

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
            $this->panParentCommission($name, $parent_id[0], $amount);
        }

        return $table;
    }

    public function panParentCommission($name, $user_id, $amount)
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
            $debit = 0;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = 0;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'PAN Commissions', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);


        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->panParentCommission($name, $parent_id[0], $amount);
        }

        return $table;
    }

    /*-------------------------------------PAN Commissions-------------------------------------*/

    /*-------------------------------------DMT Commissions-------------------------------------*/

    public function dmtCommission($user_id, $amount)
    {
        $table = DB::table('d_m_t_s')
            ->join('package_user', 'package_user.package_id', '=', 'd_m_t_s.package_id')
            ->where('package_user.user_id', $user_id)->where('d_m_t_s.from', '<', $amount)->where('d_m_t_s.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "DMT" . strtoupper(Str::random(9));
        $this->transaction($debit, 'DMT Commissions', 'dmt', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->dmtParentCommission($parent_id[0], $amount);
        }

        return $table;
    }

    public function dmtParentCommission($user_id, $amount)
    {
        $table = DB::table('d_m_t_s')
            ->join('package_user', 'package_user.package_id', '=', 'd_m_t_s.package_id')
            ->where('package_user.user_id', $user_id)->where('d_m_t_s.from', '<', $amount)->where('d_m_t_s.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = 0;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "DMT" . strtoupper(Str::random(9));
        $this->transaction($debit, 'DMT Commissions', 'dmt', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->dmtParentCommission($parent_id[0], $amount);
        }

        return $table;
    }


    /*-------------------------------------DMT Commissions-------------------------------------*/

    /*-------------------------------------Payout Commissions-------------------------------------*/

    public function payoutCommission($user_id, $amount)
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
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->payoutParentCommission($parent_id, $amount);
        }

        return $table;
    }

    public function payoutParentCommission($user_id, $amount)
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
            $debit = 0;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = 0;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'PAN Commissions', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

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

    /*-------------------------------------Payout Commissions-------------------------------------*/

    /*-------------------------------------Fund Settlement Commissions-------------------------------------*/

    public function fundSettlementCommission($user_id, $amount)
    {
        $table = DB::table('fund_settlements')
            ->join('package_user', 'package_user.package_id', '=', 'fund_settlements.package_id')
            ->where('package_user.user_id', $user_id)->where('fund_settlements.from', '<', $amount)->where('fund_settlements.to', '>=', $amount)
            ->get()[0];

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        // return $table;

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "FUNDSET" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Fund Settlement Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);
        if (!$table->parents) {
            return response()->json(["message" => "NO further commissions."]);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->fundSettlementParentCommission($parent_id[0], $amount);
        }

        return $table;
    }

    public function fundSettlementParentCommission($user_id, $amount)
    {
        $table = DB::table('fund_settlements')
            ->join('package_user', 'package_user.package_id', '=', 'fund_settlements.package_id')
            ->where('package_user.user_id', $user_id)->where('fund_settlements.from', '<', $amount)->where('fund_settlements.to', '>=', $amount)
            ->get()[0];

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = 0;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "FUNDSET" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Fund Settlement Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if (!$table->parents) {
            return response()->json(["message" => "NO further commissions."]);
        }

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->fundSettlementParentCommission($parent_id[0], $amount);
        }

        return $table;
    }

    /*-------------------------------------Fund Settlement Commissions-------------------------------------*/


    /*-------------------------------------Recharge Commissions-------------------------------------*/

    public function rechargeCommissionPaysprint($user_id, $operator, $amount)
    {
        $table = DB::table('recharges')
            ->join('package_user', 'package_user.package_id', '=', 'recharges.package_id')
            ->where(['package_user.user_id' => $user_id, 'recharges.paysprint_id' => $operator])->where('recharges.from', '<', $amount)->where('recharges.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "RECHARGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->rechargeParentCommissionPaysprint($parent_id[0], $operator, $amount);
        }

        return $table;
    }

    public function rechargeParentCommissionPaysprint($user_id, $operator, $amount)
    {
        $table = DB::table('recharges')
            ->join('package_user', 'package_user.package_id', '=', 'recharges.package_id')
            ->where(['package_user.user_id' => $user_id, 'recharges.paysprint_id' => $operator])->where('recharges.from', '<', $amount)->where('recharges.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = 0;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "RECHRGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);


        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->rechargeParentCommissionPaysprint($parent_id[0], $operator, $amount);
        }

        return $table;
    }

    public function rechargeCommissionEko($user_id, $operator, $amount)
    {
        $table = DB::table('recharges')
            ->join('package_user', 'package_user.package_id', '=', 'recharges.package_id')
            ->where(['package_user.user_id' => $user_id, 'recharges.eko_id' => $operator])->where('recharges.from', '<', $amount)->where('recharges.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = $table->fixed_charge;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "RECHARGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->rechargeParentCommissionEko($parent_id[0], $operator, $amount);
        }

        return $table;
    }

    public function rechargeParentCommissionEko($user_id, $operator, $amount)
    {
        $table = DB::table('recharges')
            ->join('package_user', 'package_user.package_id', '=', 'recharges.package_id')
            ->where(['package_user.user_id' => $user_id, 'recharges.eko_id' => $operator])->where('recharges.from', '<', $amount)->where('recharges.to', '>=', $amount)
            ->get()[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = 0;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->{$role_commission_name};
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

        $transaction_id = "RECHRGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);


        if (empty($table)) {
            return response()->json(['message' => 'No further commission']);
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->rechargeParentCommissionEko($parent_id[0], $operator, $amount);
        }

        return $table;
    }

    /*-------------------------------------Recharge Commissions-------------------------------------*/
}
