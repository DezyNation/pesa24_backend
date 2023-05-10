<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{

    public function store(Request $request)
    {
        if ($request->hasFile('attachments')) {
            $ticket = $request->file('attachments')->store('tickets');
        }
        $ticket = DB::table('tickets')->insert([
            'user_id' => auth()->user()->id,
            'title' => $request['title'],
            'transaction_id' => $request['linkedTransactionId'],
            'body' => $request['body'],
            'status' => 'created',
            'document' => $ticket ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $ticket;
    }

    public function index()
    {
        $data = DB::table('tickets')->where('user_id', auth()->user()->id)->get();
        return $data;
    }

    public function adminTicket()
    {
        $data = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.user_id')
            ->where('users.organization_id', auth()->user()->organization_id)
            ->select('tickets.*', 'users.name', 'users.phone_number', 'users.email')
            ->get();

        return $data;
    }

    public function adminUpdateTicket(Request $request)
    {
        $data = DB::table('tickets')
            ->join('users', 'users.id', '=', 'tickets.user_id')
            ->where(['users.organization_id'=> auth()->user()->organization_id, 'tickets.id' => $request['id']])
            ->update([
                'tickets.status' => $request['status'],
                'tickets.admin_remarks' => $request['admin_remarks']
            ]);

        return $data;
    }
}
