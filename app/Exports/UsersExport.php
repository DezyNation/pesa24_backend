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
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection($search = null, $from = null, $to = null)
    {
        $request['search'] = $search;
        $request['from'] = $from;
        $request['to'] = $to;
        if (!empty($search) || !is_null($search)) {
            $data = DB::table('transactions')
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
                ->where('transactions.transaction_for', 'LIKE', '%' . $search . '%')->orWhere('transactions.transaction_id', 'LIKE', '%' . $search . '%')
                ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at')
                ->latest('transactions.created_at')->orderByDesc('transactions.id')
                ->get();

            return $data;
        }
        if (!is_null($request['userId']) || !empty($request['userId'])) {
            $data = DB::table('transactions')
                ->join('users', 'users.id', '=', 'transactions.user_id')
                ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
                ->where('transactions.trigered_by', $request['userId'])
                ->orWhere('transactions.user_id', $request['userId'])
                ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
                ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at')
                ->latest('transactions.created_at')->orderByDesc('transactions.id')
                ->get();


            return $data;
        }

        $data = DB::table('transactions')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('users as admin', 'admin.id', '=', 'transactions.trigered_by')
            ->whereBetween('transactions.created_at', [$request['from'] ?? Carbon::today(), $request['to'] ?? Carbon::tomorrow()])
            ->select('users.name as transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.trigered_by', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.metadata', 'transactions.service_type', 'transactions.transaction_id',  'admin.first_name as transaction_by', 'admin.phone_number as transaction_by_phone', 'transactions.transaction_for as description', 'transactions.created_at', 'transactions.updated_at')
            ->latest('transactions.created_at')->orderByDesc('transactions.id')
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return ["Name", "Credit Amount", "Debit Amount", "Opening Balance", "Closing balance", "Service Type", "Trxn ID", "Trxn By", "Phone", "Description", "Created At", "Updated At"];
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
