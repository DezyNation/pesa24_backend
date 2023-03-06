<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;

class AttachServiceController extends Controller
{
    public function allServices()
    {
        $data = DB::table('services')->get([
            'name',
            'price'
        ]);

        return $data;
    }

    public function services($id)
    {
        $data = DB::table('services')->where('id', $id)->get([
            'name',
            'price'
        ]);

        return $data;
    }

    public function attachService(Request $request, $id)
    {
        $user = User::findOrfail(auth()->user()->id);
        $service = Service::findOrFail($id);
        if ($user->hasRole('retailer')) {
            $commission = $service['retailer_commission'];
        } elseif ($user->hasRole('distributor')) {
            $commission = $service['distributor_commission'];
        } else {
            $commission = $service['super_distributor_commission'];
        }
        $user->services()->attach($service, ['is_paid' => 1, 'is_active' => 1, 'paid_at' => now(), 'price' => $service['price'], 'commission' => $commission]);

        $updatedBalance =$user['wallet'] - $service['price'];
        $user->update([
            'wallet' => $updatedBalance
        ]);

        return response("Sucecssfully enrolled", 200);
    }
}
