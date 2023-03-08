<?php

namespace App\Http\Controllers\Paysprint;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardController extends Controller
{
    public function onboard(Request $request)
    {
        $user = auth()->user();
        $data = [
            'merchantcode' => $user->user_code,
            'mobile' => $user->phone_number,
            'is_new' => 0,
            'email' => $user->email,
            'firm' => $user->company_name,
            'callback' => ''
        ];
    }
}
