<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return DB::table('transactions')
        ->join('user_parent', 'user_parent.user_id', '=', 'transactions.trigered_by')
        ->join('users', 'users.id', '=', 'transactions.trigered_by')
        ->where('user_parent.parent_id', 105)
        ->select('users.name', 'transactions.*', 'users.phone_number')
        ->get();
    }
}
