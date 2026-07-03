<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        // Compare using string values of Role enum
        if ($userRole instanceof Role) {
            $userRole = $userRole->value;
        }

        if ($userRole !== $role) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
