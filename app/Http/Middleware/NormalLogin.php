<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request['authMethod'] == 'email') {
            $user = User::with('roles')->where('email', $request['email'])->first();
        } else {
            $user = User::with('roles')->where('phone_number', $request['phone_number'])->first();
        }
        
        $role = $user[0]['roles'];
        if (sizeof($role) == 0) {
            return response("User doesn't have assigned role, contact admins", 400);
        }

        $role = $role[0]['name'];

        if ($role == 'admin') {
            return response("Admins are not allowed to login through here", 400);
        }

        return $next($request);
    }
}
