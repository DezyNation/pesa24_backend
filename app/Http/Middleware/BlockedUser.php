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
        if (is_null(auth()->user())) {
            $email = $request['email'];
            $phone_number = $request['phone_number'];
        } else {
            $email = auth()->user()->email;
            $phone_number = auth()->user()->phone_number;
        }
        $user = User::where('email', $email)->orWhere('phone_number', $phone_number)->first();
        if (empty($user)) {
            return response("User not found.", 404);
        }
        if ($user->is_active == 0) {
            return response("You can not access to the specified resourece", 403);
        }
        return $next($request);
    }
}
