<?php

namespace App\Http\Controllers\Pesa24;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FundController extends Controller
{
    public function parents()
    {
        $user = User::with(['parentsRoles.parentsRoles.parentsRoles'])->select('id', 'name')->where('id', auth()->user()->id)->get();
        return $user;
    }
}
