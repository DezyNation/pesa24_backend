<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/api*',
        'register',
        'login',
        'send-otp',
        'forgot-password',
        'reset-password',
        'logout'
    ];


    // public function handle($request, Closure $next)
    // {
    //     if($request->route()->named('logout')) {

    //         if (!Auth::check() || Auth::guard()->viaRemember()) {

    //             $this->except[] = route('logout');

    //         }   

    //     }

    //     return parent::handle($request, $next);
    // }

}
