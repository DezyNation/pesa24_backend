<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{

    public function store(Request $request)
    {
        $ticket = DB::table('tickets')->insert([
            'user_id' => auth()->user()->id,
            'title' => $request['title'],
            'body' => $request['body'],
            'status' => 'created',
            'organization_id' => auth()->user()->organization_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $ticket;
    }

    public function index()
    {
        $org_id = DB::table('organizations')->where('code', session()->get('organization_code'))->pluck('id');
        $data = DB::table('tickets')->where('organization_id', $org_id)->get();
        return $data;
    }

    public function userTicket($id)
    {
        $org_id = DB::table('organizations')->where('code', session()->get('organization_code'))->pluck('id');
        $data = DB::table('tickets')->where(['organization_id' => $org_id, 'user_id' => $id])->get();
        return $data;
    }

    public function ticket($id)
    {
        $org_id = DB::table('organizations')->where('code', session()->get('organization_code'))->pluck('id');
        $data = DB::table('tickets')->where(['organization_id' => $org_id, 'id' => $id])->get();
        return $data;
    }

    public function update(Request $request, $id)
    {
        $org_id = DB::table('organizations')->where('code', session()->get('organization_code'))->pluck('id');
        $data = DB::table('tickets')->where(['organization_id' => $org_id, 'id' => $id])->update([
            'status' => $request['status']
        ]);
        return response()->json(['message' => 'Status updated.']);
    }
}
