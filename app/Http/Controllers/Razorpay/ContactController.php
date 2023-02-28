<?php

namespace App\Http\Controllers\Razorpay;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ContactController extends Controller
{
    public function createContact()
    {
        $data = [
        'name' => 'Rishi',
        'email' => 'rk3141508@gmail.com',
        'contact' => 9971412064,
        'type' => 'employee',
        'reference_id' =>  uniqid(),
        ];

        $request = Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')->withHeaders([
            'Content-type' => 'application/json'
        ])->post('https://api.razorpay.com/v1/contacts', $data);
        
        return $request;
    }

    public function updateContact()
    {
        $data = [];
    }
}
