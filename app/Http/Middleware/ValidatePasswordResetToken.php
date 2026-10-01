<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

class ValidatePasswordResetToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya validasi untuk route password.reset (GET /reset-password/{token})
        if (! $request->routeIs('password.reset')) {
            return $next($request);
        }

        $token = $request->route('token');
        $email = $request->query('email');

        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user || ! $token || ! Password::broker()->tokenExists($user, $token)) {
            return redirect()
                ->route('password.request')
                ->with('error', 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.');
        }

        return $next($request);
    }
}
