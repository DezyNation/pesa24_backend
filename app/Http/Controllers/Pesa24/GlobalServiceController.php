<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class GlobalServiceController extends Controller
{
    public function manageService(Request $request)
    {

        $request->validate([
            'is_active' => 'required',
            'down_message' => 'required_if:can_subscribe,0', 'required_if:is_active,0'
        ]);

        $table = DB::table('services')->where('id', $request['id'])->update([
            'service_name' => $request['service_name'],
            'image_url' => $request['image_url'],
            'is_active' => $request['is_active'],
            'can_subscribe' => $request['can_subscribe'],
            'down_message' => $request['down_message'],
            'updated_at' => now()
        ]);

        return $table;
    }

    public function getServices()
    {
        $data = DB::table('services')->get();
        return $data;
    }
}
