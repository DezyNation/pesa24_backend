<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UserHasService
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $service_code = $request['service_code'];
        $provider = "$request->provider"."_"."active";
        $query = DB::table('service_user')->where(['user_id'=> auth()->user()->id, 'service_id' => $service_code])->first();
        if (!$query || $query->$provider == 0) {
            return response()->json(['message' => 'Service not available at the moment']);
        }
        return $next($request);
    }
}
