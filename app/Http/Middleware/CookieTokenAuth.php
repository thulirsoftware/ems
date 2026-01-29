<?php

namespace App\Http\Middleware;

use Closure;

class CookieTokenAuth
{
    public function handle($request, Closure $next)
    {
        $path = $request->path();

        if (str_starts_with($path, 'api/admin')) {
            if ($request->cookie('admin_access_token')) {
                $request->headers->set(
                    'Authorization',
                    'Bearer ' . $request->cookie('admin_access_token')
                );
            }
        }

        if (str_starts_with($path, 'api/user')) {
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
