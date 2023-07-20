<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::where('email', $request['email'] ?? auth()->user()->email)->orWhere('phone_number', $request['phone_number'])->first();
        if ($user->is_active == 0) {
            return response("You can not access to the specified resourece", 403);
        }
        return $next($request);
    }
}
