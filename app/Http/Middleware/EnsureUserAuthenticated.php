<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('site')->check()) {
            return redirect()->route('authentication.access-form');
        }

        return $next($request);
    }
}