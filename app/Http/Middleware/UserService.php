<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UserService
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user_id = auth()->user()->id;
        $service_id = $request->route('service_id');
        $service_active = DB::table('services')->where('id', $service_id)->pluck('is_active');
        $db = DB::table('service_user')->where(['user_id' => $user_id, 'service_id' => $service_id]);
        $pesa24_status = $db->pluck('pesa24_active');
        if (!$db->exists()) {
            return response("Service is not activated.", 404);
        } elseif (!$service_active) {
            return response("Service is not avaoilable at the moment", 403);
        }
         elseif (!$pesa24_status) {
            return response("User can not use this service at the momemnt.", 403);
        }
        return $next($request);
    }
}
