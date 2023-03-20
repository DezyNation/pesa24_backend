<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class GlobalServiceController extends Controller
{
    public function manageService($service_id, $active)
    {
        $table = DB::table('services')->where('id', $service_id)->update([
            'is_active' => $active
        ]);

        return $table;
    }
}
