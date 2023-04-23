<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    /*-------------------------------------AePS Withdrawal Commission-------------------------------------*/
    public function aepsComission($amount, $user_id)
    {
        $table = DB::table('a_e_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'a_e_p_s.package_id')
            ->where('package_user.user_id', $user_id)->where('a_e_p_s.from', '<', $amount)->where('a_e_p_s.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No Commissions.");
        }
        $table = $table[0];
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
        $metadata = [
            'status' => true,
        ];
        $this->transaction($debit, 'Comission AePS', 'withdrawal', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);

        if (!$table->parents) {
            return response("No comission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->parentAepsComission($amount, $parent_id[0]);
        }


        return response("Comission Assigned.");
    }

    public function parentAepsComission($amount, $user_id)
    {
        $table = DB::table('a_e_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'a_e_p_s.package_id')
            ->where('package_user.user_id', $user_id)->where('a_e_p_s.from', '<', $amount)->where('a_e_p_s.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No Comissions.");
        }
        $table = $table[0];

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

        $metadata = [
            'status' => true,
        ];
        $this->transaction($debit, 'Comission AePS', 'withdrawal', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);

        if (!$table->parents) {
            return response("No comission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->parentAepsComission($amount, $parent_id[0]);
        }

        return response("Comission Assigned.");
    }
    /*-------------------------------------AePS Withdrawal Commission-------------------------------------*/


    /*-------------------------------------AePS Mini-statement Commission-------------------------------------*/

    public function aepsMiniComission($user_id)
    {
        $table = DB::table('ae_p_s_mini_statements')
            ->join('package_user', 'package_user.package_id', '=', 'ae_p_s_mini_statements.package_id')
            ->where('package_user.user_id', $user_id)
            ->get();

        if ($table->isEmpty()) {
            return response("No Commissions.");
        }

        $table = $table[0];

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
            $debit = $fixed_charge;
            $credit = $role_commission  - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $transaction_id = "COM" . strtoupper(Str::random(9));
        $user->update([
            'wallet' => $closing_balance
        ]);
        $metadata = [
            'status' => true,
        ];
        $this->transaction($debit, 'Commission AePS mini statement', 'mini-statement', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);

        if (!$table->parents) {
            return response("No comission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->parentAepsMiniComission($parent_id[0]);
        }

        return true;
    }

    public function parentAepsMiniComission($user_id)
    {
        $table = DB::table('ae_p_s_mini_statements')
            ->join('package_user', 'package_user.package_id', '=', 'ae_p_s_mini_statements.package_id')
            ->where('package_user.user_id', $user_id)
            ->get();

        if ($table->isEmpty()) {
            return response("No Commissions.");
        }

        $table = $table[0];

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
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $transaction_id = "COM" . strtoupper(Str::random(9));
        $user->update([
            'wallet' => $closing_balance
        ]);

        $metadata = [
            'status' => true,
        ];

        $this->transaction($debit, 'Commission AePS mini statement', 'mini-statement', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);

        if (!$table->parents) {
            return response("No comission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->parentAepsMiniComission($parent_id[0]);
        }

        return true;
    }

    /*-------------------------------------AePS Mini-statement Commission-------------------------------------*/


    /*-------------------------------------PAN Commissions-------------------------------------*/

    public function panCommission($name, $user_id, $amount)
    {
        $table = DB::table('p_a_n_s')
            ->join('package_user', 'package_user.package_id', '=', 'p_a_n_s.package_id')
            ->where('package_user.user_id', $user_id)->where('p_a_n_s.name', $name)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions for this transactions.");
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'PAN Commissions', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);

        if (!$table->parents) {
            return response("No comission for parents");
        }

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
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions for this transactions.");
        }

        $table = $table[0];

        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = 0;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->pluck($role_commission_name);

        $opening_balance = $user->wallet;

        if ($is_flat) {
            $debit = $fixed_charge;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = $fixed_charge;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $metadata = [
            'status' => true
        ];

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'PAN Commission', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);


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
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions.");
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "DMT" . strtoupper(Str::random(9));
        $this->transaction($debit, 'DMT Commission', 'dmt', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);

        if (!$table->parents) {
            return response("No commission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->dmtParentCommission($parent_id[0], $amount);
        }

        return true;
    }

    public function dmtParentCommission($user_id, $amount)
    {
        $table = DB::table('d_m_t_s')
            ->join('package_user', 'package_user.package_id', '=', 'd_m_t_s.package_id')
            ->where('package_user.user_id', $user_id)->where('d_m_t_s.from', '<', $amount)->where('d_m_t_s.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions for this transactions.");
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "DMT" . strtoupper(Str::random(9));
        $this->transaction($debit, 'DMT Commission', 'dmt', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);

        if (!$table->parents) {
            return response("No comission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->dmtParentCommission($parent_id[0], $amount);
        }

        return true;
    }


    /*-------------------------------------DMT Commissions-------------------------------------*/

    /*-------------------------------------Payout Commissions-------------------------------------*/

    public function payoutCommission($user_id, $amount)
    {
        $table = DB::table('payoutcommissions')
            ->join('package_user', 'package_user.package_id', '=', 'payoutcommissions.package_id')
            ->where('package_user.user_id', $user_id)->where('payoutcommissions.from', '<', $amount)
            ->where('payoutcommissions.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions for this transactions.");
        }

        $table = $table[0];
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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "PAN" . strtoupper(Str::random(9));
        $this->transaction($amount, 'Payout Commission', 'money-transfer', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (!$table->parents) {
            return response("No comissions to parent users.");
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
        $table = DB::table('payoutcommissions')
            ->join('package_user', 'package_user.package_id', '=', 'payoutcommissions.package_id')
            ->where('package_user.user_id', $user_id)->where('payoutcommissions.from', '<', $amount)
            ->where('payoutcommissions.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions for this transactions.");
        }
        $table = $table[0];
        $user = User::findOrFail($user_id);
        $role = $user->getRoleNames()[0];

        $fixed_charge = 0;
        $is_flat = $table->is_flat;
        $gst = $table->gst;
        $role_commission_name = $role . "_commission";
        $role_commission = $table->pluck($role_commission_name);
        $opening_balance = $user->wallet;

        if ($is_flat) {
            $debit = $fixed_charge;
            $credit = $role_commission - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        } elseif (!$is_flat) {
            $debit = $fixed_charge;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $debit + $credit;
        }

        $metadata = [
            'status' => true
        ];

        $transaction_id = "PAY" . strtoupper(Str::random(9));
        $this->transaction($amount, 'Payout Comissions', 'money-transfer', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);


        if (!$table->parents) {
            return response("No comissions to parent users.");
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
            ->get();

        if ($table->isEmpty()) {
            return response("No further Comissions");
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "FUNDSET" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Fund Settlement Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);
        if (!$table->parents) {
            return response()->json(["message" => "No further commissions."]);
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
            ->get();

        if ($table->isEmpty()) {
            return response('No further commission');
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "FUNDSET" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Fund Settlement Commissions', 'fund', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
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

    /*-------------------------------------Fund Settlement Commissions-------------------------------------*/


    /*-------------------------------------Recharge Commissions-------------------------------------*/

    public function rechargeCommissionPaysprint($user_id, $operator, $amount)
    {
        $table = DB::table('recharges')
            ->join('package_user', 'package_user.package_id', '=', 'recharges.package_id')
            ->where(['package_user.user_id' => $user_id, 'recharges.paysprint_id' => $operator])->where('recharges.from', '<', $amount)->where('recharges.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions.");
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "RECHARGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commission', 'recharge', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (!$table->parents) {
            return response("No commission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->rechargeParentCommissionPaysprint($parent_id[0], $operator, $amount);
        }

        return true;
    }

    public function rechargeParentCommissionPaysprint($user_id, $operator, $amount)
    {
        $table = DB::table('recharges')
            ->join('package_user', 'package_user.package_id', '=', 'recharges.package_id')
            ->where(['package_user.user_id' => $user_id, 'recharges.paysprint_id' => $operator])->where('recharges.from', '<', $amount)->where('recharges.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No Comissions.");
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "RECHRGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commission', 'recharge', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);


        if (!$table->parents) {
            return response("No comission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->rechargeParentCommissionPaysprint($parent_id[0], $operator, $amount);
        }

        return true;
    }

    public function rechargeCommissionEko($user_id, $operator, $amount)
    {
        $table = DB::table('recharges')
            ->join('package_user', 'package_user.package_id', '=', 'recharges.package_id')
            ->where(['package_user.user_id' => $user_id, 'recharges.eko_id' => $operator])->where('recharges.from', '<', $amount)->where('recharges.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions.");
        }

        $table = $table[0];

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
        $metadata = [
            'status' => true
        ];
        $transaction_id = "RECHARGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commissions', 'recharge', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);
        if (!$table->parents) {
            return response("No comission for parents");
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
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions.");
        }

        $table = $table[0];

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
        $metadata = [
            'status' => true
        ];
        $transaction_id = "RECHRGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'Recharge Commissions', 'recharge', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);
        if (!$table->parents) {
            return response("No comission for parents");
        }
        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->rechargeParentCommissionEko($parent_id[0], $operator, $amount);
        }

        return $table;
    }

    /*-------------------------------------Recharge Commissions-------------------------------------*/


    /*-------------------------------------BBPS Commissions-------------------------------------*/

    public function bbpsPaysprintCommission($user_id, $operator, $amount)
    { 
        $table = DB::table('b_b_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'b_b_p_s.package_id')
            ->where(['package_user.user_id' => $user_id, 'b_b_p_s.paysprint_id' => $operator])->where('b_b_p_s.from', '<', $amount)->where('b_b_p_s.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions.");
        }

        $table = $table[0];

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

        $metadata = [
            'status' => true
        ];

        $transaction_id = "RECHARGE" . strtoupper(Str::random(9));
        $this->transaction($debit, 'BBPS bill Comission', 'bbps', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (!$table->parents) {
            return response("No comission for parents");
        }
        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->bbpsParentPaysprintCommission($parent_id[0], $operator, $amount);
        }

        return true;
    }

    public function bbpsParentPaysprintCommission($user_id, $operator, $amount)
    {
        $table = DB::table('b_b_p_s')
            ->join('package_user', 'package_user.package_id', '=', 'b_b_p_s.package_id')
            ->where(['package_user.user_id' => $user_id, 'b_b_p_s.paysprint_id' => $operator])->where('b_b_p_s.from', '<', $amount)->where('b_b_p_s.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions.");
        }

        $table = $table[0];

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
        $metadata = [
            'status' => true
        ];
        $transaction_id = "BBPSCOM" . strtoupper(Str::random(9));
        $this->transaction($debit, 'BBPS Bill COMISSIONS', 'bbps', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $credit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (!$table->parents) {
            return response("No commission for parents");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->bbpsParentPaysprintCommission($parent_id[0], $operator, $amount);
        }

        return true;
    }

    /*-------------------------------------BBPS Commissions-------------------------------------*/


    public function dmtReversal(float $amount, int $user_id)
    {
        $data = DB::table('d_m_t_s')
            ->join('users', 'package_user.user_id', '=', 'users.id')
            ->select('d_m_t_s.*')
            ->where('package_user.user_id', $user_id)->where('d_m_t_s.from', '<', $amount)->where('d_m_t_s.to', '>=', $amount)
            ->get();

        if (!$data) {
            return response()->json(['message' > 'Transaction was done but no commission was given.']);
        }
        $user = User::findOrFail($user_id);
        $opening_balance = $user->wallet;
        if ($data[0]->is_flat) {
            $commission = $data[0]->commission;
            $debit = $data[0]->fixed_charge + $data[0]->fixed_charge * $data[0]->gst / 100;
            $credit = $commission - $commission * $data[0]->gst / 100;
            $closing_balance = $user->wallet - $credit + $debit;
        } else {
            $commission = $data[0]->commission * $amount / 100;
            $debit = $amount * $data[0]->fixed_charge / 100 + $amount * $data[0]->fixed_charge * $data[0]->gst / 10000;
            $credit = $commission - $commission * $data[0]->gst / 100;
            $closing_balance = $user->wallet - $credit + $debit;
        }

        $user->update([
            'wallet' => $closing_balance
        ]);

        $transaction_id = "REVCOM" . strtoupper(Str::random(5));
        $metadata = [
            'starus' => true,
            'event' => 'refund'
        ];
        $this->transaction($credit, "Commission reversal for DMT", 'dmt', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $debit);

        return response()->json(['message' => 'True']);
    }

    public function razorpayReversal($amount, $user_id)
    {
        $table = DB::table('payoutcommissions')
            ->join('package_user', 'package_user.package_id', '=', 'payoutcommissions.package_id')
            ->where('package_user.user_id', $user_id)->where('payoutcommissions.from', '<', $amount)
            ->where('payoutcommissions.to', '>=', $amount)
            ->get();

        if ($table->isEmpty()) {
            return response("No commissions for this transactions.");
        }

        $table = $table[0];
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
            $closing_balance = $opening_balance - $credit + $debit;
        } elseif (!$is_flat) {
            $debit = $amount + $amount * $fixed_charge / 100;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $credit + $debit;
        }
        $metadata = [
            'status' => true,
            'event' => 'refund',
            'amount' => $amount
        ];
        $transaction_id = "REV" . strtoupper(Str::random(9));
        $this->transaction($credit, 'Commissions Reversal', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $debit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (!$table->parents) {
            return response("No commissions to parent users.");
        }
        $parent = DB::table('user_parent')->where('user_id', $user_id);
        $parent_id = $parent->pluck('parent_id');
        $this->payoutReversalParent($parent_id, $amount);
        return $table;
    }

    public function payoutReversalParent($user_id, $amount)
    {
        $table = DB::table('payoutcommissions')
            ->join('package_user', 'package_user.package_id', '=', 'payoutcommissions.package_id')
            ->where('package_user.user_id', $user_id)->where('payoutcommissions.from', '<', $amount)
            ->where('payoutcommissions.to', '>=', $amount)
            ->get()[0];

        if ($table->isEmpty()) {
            return response("No commissions for this transactions.");
        }
        $table = $table[0];
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
            $closing_balance = $opening_balance - $credit + $debit;
        } elseif (!$is_flat) {
            $debit = $amount + $amount * $fixed_charge / 100;
            $credit = $role_commission * $amount / 100 - $role_commission * $gst / 100;
            $closing_balance = $opening_balance - $credit + $debit;
        }
        $metadata = [
            'status' => true,
            'event' => 'refund',
            'amount' => $amount
        ];
        $transaction_id = "REV" . strtoupper(Str::random(9));
        $this->transaction($credit, 'Commissions Reversal', 'pan', $user_id, $opening_balance, $transaction_id, $closing_balance, json_encode($metadata), $debit);
        $user->update([
            'wallet' => $closing_balance
        ]);

        if (!$table->parents) {
            return response("No comissions to parent users.");
        }

        $parent = DB::table('user_parent')->where('user_id', $user_id);

        if ($parent->exists()) {
            $parent_id = $parent->pluck('parent_id');
            $this->payoutReversalParent($parent_id, $amount);
        }

        return $table;
    }
}
