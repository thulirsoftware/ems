<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiAdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        app(CookieTokenAuth::class)->handle($request, fn($r) => $r);

        if (!Auth::guard('admins')->check()) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (!(Auth::guard('admins')->user() instanceof Admin)) {
            return response()->json([
                'message' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
