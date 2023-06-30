<?php

namespace App\Http\Controllers\Razorpay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Razorpay\FundAccountController;
use Illuminate\Support\Facades\Log;

class ContactController extends FundAccountController
{
    public function createContact(Request $request)
    {
        $data = [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'contact' => auth()->user()->phone_number,
            'type' => 'employee',
            'reference_id' =>  "FLYWID".uniqid(),
        ];

        $response = Http::withBasicAuth('rzp_live_XgWJpiVBPIl3AC', '1vrEAOIWxIxHkHUQdKrnSWlF')->withHeaders([
            'Content-type' => 'application/json'
        ])->post('https://api.razorpay.com/v1/contacts', $data);
        if (array_key_exists('id', $response->json())) {
            DB::table('users')->where('id', auth()->user()->id)->update(['rzp_contact_id' => $response['id'], 'updated_at' => now()]);
        }
        Log::channel('response')->info($response);
        return $this->createFundAcc($request);
    }
}
