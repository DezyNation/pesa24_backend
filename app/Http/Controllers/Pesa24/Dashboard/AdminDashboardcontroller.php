<?php

namespace App\Http\Controllers\pesa24\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardcontroller extends Controller
{
    public function packageService(Request $request)
    {
        DB::table('package_service')->insert([
            'package_id' => $request['packageId'],
            'service_id' => $request['serviceId'],
            'from' => $request['fromValue'],
            'to' => $request['toValue'],
            'retailer_commission' => $request['retailerCommission'],
            'distributor_commission' => $request['retailerCommission'],
            'super_distributor_commission' => $request['retailerCommission'],
            'fixed_deduction' => $request['fixedDeduction'],
            'is_flat' => $request['isFlat'],
            'is_surcharge' => $request['isSurcharge'],
            'created_at' => now(),
            'updated_at' => now() 
        ]);

        return response()->json(['message' => 'Package linked']);
    }
}
