<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    //accept a role
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        if ($user && $user->role == $role) {
            return $next($request);
        }
        // 2. The Kill Switch (with a dynamic error message)
        return response()->json([
            'status' => 'error',
            'status_code' => 403,
            'message' => 'Access Denied. You must be an ' . $role . ' to perform this action.'
        ], 403);
    }
}
