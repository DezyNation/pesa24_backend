<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class TicketController extends Controller
{

    public function store(Request $request)
    {
        $ticket = DB::table('tickets')->insert([
            'title' => $request['title'],
            'body' => $request['body'],
            'status' => 'created',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $ticket;
    }

    public function index()
    {
        $id = DB::table('organizations')->where('code', Session::get('organization_code'))->pluck('id');
        $ticket = Ticket::with(['users' => function($query) use ($id) {
            $query->where('organization_id', $id);
        }]);

        return $ticket;
    }
}
