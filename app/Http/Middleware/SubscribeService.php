<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SubscribeService
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->route('service_id');
        $service_id = DB::table('services')->where(['id' => $id, 'can_subscribe' => 1])->exists();
        if (!$service_id) {
            return response("Service not found.", 404);
        }
        return $next($request);
    }
}
