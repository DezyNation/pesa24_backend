<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $cache = Cache::put($request['time'], true);
        Log::channel('response')->info('request', $request->all());
        return $cache;
    }
}
