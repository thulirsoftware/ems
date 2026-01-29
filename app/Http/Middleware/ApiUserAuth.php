<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiUserAuth
{
    public function handle(Request $request, Closure $next)
    {
        app(CookieTokenAuth::class)->handle($request, fn($r) => $r);

        if (!Auth::guard('users')->check()) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (!(Auth::guard('users')->user() instanceof User)) {
            return response()->json([
                'message' => 'User access required'
            ], 403);
        }

        return $next($request);
    }
}
