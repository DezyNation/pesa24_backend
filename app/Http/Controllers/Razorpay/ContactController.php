<?php

namespace App\Http\Controllers\Razorpay;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ContactController extends Controller
{
    public function createContact()
    {
        $data = [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'contact' => auth()->user()->phone,
            'type' => 'employee',
            'reference_id' =>  uniqid(),
        ];

        $response = Http::withBasicAuth('rzp_test_f76VR5UvDUksZJ', 'pCcVlr5pRFcBZxAH4xBqGY62')->withHeaders([
            'Content-type' => 'application/json'
        ])->post('https://api.razorpay.com/v1/contacts', $data);

        DB::table('contacts')->insert([
            'user_id' => 23,
            'contact_id' => $response['id'],
            'entity' => $response['entity'],
            'contact' => $response['contact'],
            'email' => $response['email'],
            'reference_id' => $response['reference_id'],
            'batch_id' => $response['batch_id'],
            'added_at' => $response['created_at'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response('Your contact has been created', 200);
    }
}
