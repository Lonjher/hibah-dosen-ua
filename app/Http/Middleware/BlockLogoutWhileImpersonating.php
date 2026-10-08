<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockLogoutWhileImpersonating
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('logout') && session('impersonator_id')) {
            return redirect()->back()
                ->with('error', 'Return to your account before logging out.');
        }

        return $next($request);
    }
}
