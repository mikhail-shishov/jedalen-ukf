<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isAdminByRole = $user && (int) $user->role_id === 4;
        $hasAdminAccess = $user && ((bool) $user->is_admin || $isAdminByRole);

        if (!$hasAdminAccess) {
            abort(403, 'Nemáte oprávnenie na prístup do administrácie.');
        }

        return $next($request);
    }
}
