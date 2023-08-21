<?php

namespace App\Exports\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayoutExport implements FromCollection, WithHeadings, WithStyles, WithChunkReading
{

    protected $from;
    protected $to;
    protected $search;
    protected $user_id;
    protected $status;
    protected $processing;

    public function __construct($from, $to, $search, $user_id, $status, $processing)
    {
        $this->from = $from;
        $this->to = $to;
        $this->search = $search;
        $this->user_id = $user_id;
        $this->status = $status;
        $this->processing = $processing;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if (!empty($this->user_id) || !is_null($this->user_id)) {
            if (!empty($this->status) || !is_null($this->status)) {
                $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                    ->where([
                        'users.organization_id' => auth()->user()->organization_id,
                        'payouts.user_id' => $this->user_id
                    ])
                    ->where('payouts.status', $this->status)
                    ->whereBetween('payouts.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
                    ->select('payouts.id', 'payouts.created_at', 'payouts.user_id', 'users.name', 'users.phone_number', 'payouts.reference_id', 'payouts.payout_id', 'payouts.utr', 'payouts.amount', 'payouts.beneficiary_name', 'payouts.account_number', 'payouts.status', 'payouts.updated_at')->latest()->get();

                return $payout;
            } else {
                $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                    ->where([
                        'users.organization_id' => auth()->user()->organization_id,
                        'payouts.user_id' => $this->user_id
                    ])
                    ->whereBetween('payouts.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
                    ->select('payouts.id', 'payouts.created_at', 'payouts.user_id', 'users.name', 'users.phone_number', 'payouts.reference_id', 'payouts.payout_id', 'payouts.utr', 'payouts.amount', 'payouts.beneficiary_name', 'payouts.account_number', 'payouts.status', 'payouts.updated_at')->latest()->get();

                return $payout;
            }
        }
        $search = $this->search;
        if (!empty($search)) {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->where("payouts.account_number", 'LIKE', '%' . $search . '%')->orWhere("payouts.reference_id", 'LIKE', '%' . $search . '%')->orWhere("payouts.utr", 'LIKE', '%' . $search . '%')
                ->select('payouts.id', 'payouts.created_at', 'payouts.user_id', 'users.name', 'users.phone_number', 'payouts.reference_id', 'payouts.payout_id', 'payouts.utr', 'payouts.amount', 'payouts.beneficiary_name', 'payouts.account_number', 'payouts.status', 'payouts.updated_at')->latest()->get();

            return $payout;
        }
        if ($this->processing == 'all') {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                // ->where('payouts.status', '!=', 'processing')
                ->whereBetween('payouts.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
                ->select('payouts.id', 'payouts.created_at', 'payouts.user_id', 'users.name', 'users.phone_number', 'payouts.reference_id', 'payouts.payout_id', 'payouts.utr', 'payouts.amount', 'payouts.beneficiary_name', 'payouts.account_number', 'payouts.status', 'payouts.updated_at')->latest()->get();

            return $payout;
        } elseif ($this->processing == 'processing') {

            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->whereBetween('payouts.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
                ->where('payouts.status', 'processing')->orWhere('payouts.status', 'pending')->orWhere('payouts.status', 'queued')
                ->select('payouts.id', 'payouts.created_at', 'payouts.user_id', 'users.name', 'users.phone_number', 'payouts.reference_id', 'payouts.payout_id', 'payouts.utr', 'payouts.amount', 'payouts.beneficiary_name', 'payouts.account_number', 'payouts.status', 'payouts.updated_at')->latest()->get();

            return $payout;
        } else {
            $payout = DB::table('payouts')->join('users', 'users.id', '=', 'payouts.user_id')
                ->where([
                    'users.organization_id' => auth()->user()->organization_id
                ])
                ->whereBetween('payouts.created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
                ->where('payouts.status', $this->processing)
                ->select('payouts.id', 'payouts.created_at', 'payouts.user_id', 'users.name', 'users.phone_number', 'payouts.reference_id', 'payouts.payout_id', 'payouts.utr', 'payouts.amount', 'payouts.beneficiary_name', 'payouts.account_number', 'payouts.status', 'payouts.updated_at')->latest()->get();

            return $payout;
        }
    }

    public function headings(): array
    {
        return ["Req ID", "Created At", "User ID", "User Name", "Phone Number", "Ref ID", "Payout ID", "UTR", "Amount", "Beneficiary Name", "Account Number", "Status", "Updated At"];
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
