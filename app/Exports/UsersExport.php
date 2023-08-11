<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithStyles, WithChunkReading
{

    protected $from;
    protected $to;
    protected $search;
    protected $user_id;

    public function __construct($from, $to, $search, $user_id)
    {
        $this->from = $from;
        $this->to = $to;
        $this->search = $search;
        $this->user_id = $user_id;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if (!empty($this->search) || !is_null($this->search)) {
            $data = DB::table('transactions')
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
                ->where('transactions.transaction_for', 'LIKE', '%' . $this->search . '%')->orWhere('transactions.transaction_id', 'LIKE', '%' . $this->search . '%')
                ->whereBetween('transactions.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
                ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at', 'transactions.metadata')
                ->latest('transactions.created_at')->orderByDesc('transactions.id')
                ->get();

            return $data;
        }
        if (!is_null($this->user_id) || !empty($this->user_id)) {
            $data = DB::table('transactions')
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
                ->where('transactions.trigered_by', $this->user_id)
                ->orWhere('transactions.user_id', $this->user_id)
                ->whereBetween('transactions.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
                ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at', 'transactions.metadata')
                ->latest('transactions.created_at')->orderByDesc('transactions.id')
                ->get();


            return $data;
        }

        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->whereBetween('transactions.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
            ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at', 'transactions.metadata')
            ->latest('transactions.created_at')->orderByDesc('transactions.id')
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return ["Name", "Credit Amount", "Debit Amount", "Trxn By", "Opening Balance", "Closing balance", "Service Type", "Trxn ID", "User name", "Phone", "Description", "Created At", "Updated At", "Metadata"];
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
