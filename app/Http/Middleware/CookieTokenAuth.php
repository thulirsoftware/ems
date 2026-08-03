<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CookieTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();

        if (str_starts_with($path, 'web/v1/admin')) {
            if ($request->cookie('admin_access_token')) {
                $request->headers->set(
                    'Authorization',
                    'Bearer ' . $request->cookie('admin_access_token')
                );
            }
        }

        if (str_starts_with($path, 'web/v1/user')) {
            if ($request->cookie('user_access_token')) {
                $request->headers->set(
                    'Authorization',
                    'Bearer ' . $request->cookie('user_access_token')
                );
            }
        }

        return $next($request);
    }
}
