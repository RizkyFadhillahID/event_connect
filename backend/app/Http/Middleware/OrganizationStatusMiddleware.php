<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OrganizationStatusMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if ($user && $user->organization && $user->organization->status !== 'active') {
            return response()->json([
                'message' => 'Organisasi Anda sedang dinonaktifkan atau ditangguhkan. Silakan hubungi admin platform.'
            ], 403);
        }

        return $next($request);
    }
}
