<?php

namespace App\Exports\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FundReport implements FromCollection, WithHeadings, WithStyles, WithChunkReading
{

    protected $from;
    protected $to;
    protected $search;
    protected $user_id;
    protected $status;

    public function __construct($from, $to, $search, $user_id, $status)
    {
        $this->from = $from;
        $this->to = $to;
        $this->search = $search;
        $this->user_id = $user_id;
        $this->status = $status;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if (!empty($this->search) || !is_null($this->search)) {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->where('funds.transaction_id', 'like', '%' . $this->search . '%')
                ->whereBetween('funds.created_at', [$this->from ?? Carbon::now()->startOfDecade(), $this->to ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.id as fund_id', 'funds.status', 'funds.transaction_date', 'funds.created_at', 'funds.transaction_id', 'funds.amount', 'funds.opening_balance', 'funds.closing_balance', 'funds.bank_name', 'funds.transaction_type', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id', 'funds.remarks', 'funds.admin_remarks')->latest('funds.created_at')->get();
            return $data;
        }


        if (!empty($this->user_id) || !is_null($this->user_id)) {
            if ($this->status == 'all') {
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->whereBetween('funds.created_at', [$this->from ?? Carbon::now()->startOfDecade(), $this->to ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.user_id', $this->user_id)->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.id as fund_id', 'funds.status', 'funds.transaction_date', 'funds.created_at', 'funds.transaction_id', 'funds.amount', 'funds.opening_balance', 'funds.closing_balance', 'funds.bank_name', 'funds.transaction_type', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id', 'funds.remarks', 'funds.admin_remarks')->latest('funds.created_at')->get();
                return $data;
            } else {
                $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                    ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                    ->whereBetween('funds.created_at', [$this->from ?? Carbon::now()->startOfDecade(), $this->to ?? Carbon::now()->endOfDecade()])
                    ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.user_id', $this->user_id)->where('funds.status', $this->status)->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.id as fund_id', 'funds.status', 'funds.transaction_date', 'funds.created_at', 'funds.transaction_id', 'funds.amount', 'funds.opening_balance', 'funds.closing_balance', 'funds.bank_name', 'funds.transaction_type', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id', 'funds.remarks', 'funds.admin_remarks')->latest('funds.created_at')->get();
                return $data;
            }
        }

        if ($this->status == 'all') {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->whereBetween('funds.created_at', [$this->from ?? Carbon::now()->startOfDecade(), $this->to ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', '!=', 'pending')->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.id as fund_id', 'funds.status', 'funds.transaction_date', 'funds.created_at', 'funds.transaction_id', 'funds.amount', 'funds.opening_balance', 'funds.closing_balance', 'funds.bank_name', 'funds.transaction_type', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id', 'funds.remarks', 'funds.admin_remarks')->latest('funds.created_at')->get();
            return $data;
        } else {
            $data = DB::table('funds')->join('users', 'users.id', '=', 'funds.user_id')
                ->join('users as admin', 'admin.id', '=', 'funds.parent_id')
                ->whereBetween('funds.created_at', [$this->from ?? Carbon::now()->startOfDecade(), $this->to ?? Carbon::now()->endOfDecade()])
                ->where(['users.organization_id' => auth()->user()->organization_id])->where('funds.status', $this->status)->where('funds.transaction_type', '!=', 'transfer')->where('funds.transaction_type', '!=', 'reversal')->select('funds.id as fund_id', 'funds.status', 'funds.transaction_date', 'funds.created_at', 'funds.transaction_id', 'funds.amount', 'funds.opening_balance', 'funds.closing_balance', 'funds.bank_name', 'funds.transaction_type', 'users.name', 'users.phone_number', 'admin.name as admin_name', 'admin.id as admin_id', 'funds.remarks', 'funds.admin_remarks')->latest('funds.created_at')->get();
            return $data;
        }
    }

    public function headings(): array
    {
        return ["Req ID", "Status", "Transfer Date", "Request Timestamp", "Trnxn ID", "Amount", "Opening Balance", "Closing Balance", "Requested Bank", "Trnxn Type", "User Name", "Phone Number", "Updated By", "Admin ID",  "Remarks", "Admin Remarks"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function chunkSize(): int
    {
        return 5000;
    }
}
