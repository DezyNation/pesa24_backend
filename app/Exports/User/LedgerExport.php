<?php

namespace App\Exports\User;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExport implements FromCollection, WithHeadings, WithStyles, WithChunkReading
{

    protected $from;
    protected $to;
    protected $search;
    protected $status;
    protected $name;

    public function __construct($from, $to, $search, $status, $name)
    {
        $this->from = $from;
        $this->to = $to;
        $this->search = $search;
        $this->status = $status;
        $this->name = $name;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $search = $this->search;
        if (!empty($search) || !is_null($search)) {
            $data = DB::table('transactions')->where('trigered_by', auth()->user()->id)->where('transaction_for', 'like', '%' . $search . '%')->orWhere('transaction_id', 'like', '%' . $search . '%')->select('transactions.transaction_id', 'transactions.service_type', 'transactions.transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.created_at', 'transactions.updated_at')->latest()->get();
            // ->paginate(200);
            return $data;
        }
        if (!is_null($this->name) || !empty($this->name)) {
            if (!is_null($this->status) || !empty($this->status)) {
                $data = DB::table('transactions')->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])->where('service_type', $this->name)->where(function ($q) {
                    $q->where('trigered_by', auth()->user()->id);
                })->whereJsonContains('metadata->status', $this->status)
                    ->select('transactions.transaction_id', 'transactions.service_type', 'transactions.transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.created_at', 'transactions.updated_at')
                    ->latest()
                    ->get();
                return $data;
            }
            $data = DB::table('transactions')->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])->where('service_type', $this->name)->where(function ($q) {
                $q->where('trigered_by', auth()->user()->id);
            })
                ->select('transactions.transaction_id', 'transactions.service_type', 'transactions.transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.created_at', 'transactions.updated_at')
                ->latest()
                ->get();
            return $data;
        }
        $data = DB::table('transactions')->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])->where('trigered_by', auth()->user()->id)->orWhere('user_id', auth()->user()->id)
            ->select('transactions.transaction_id', 'transactions.service_type', 'transactions.transaction_for', 'transactions.credit_amount', 'transactions.debit_amount', 'transactions.opening_balance', 'transactions.closing_balance', 'transactions.created_at', 'transactions.updated_at')
            ->latest()->get();
        return $data;
    }

    public function headings(): array
    {
        return ["Trxn ID", "Service", "Description", "Credit", "Debit", "Opening Bal", "Closing Bal", "Created At", "Updated At"];
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
