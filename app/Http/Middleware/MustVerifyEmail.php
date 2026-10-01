<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MustVerifyEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Belum login → biarkan auth middleware yang handle
        if (! $user) {
            return $next($request);
        }

        // Model User tidak implement MustVerifyEmail → lewatkan
        if (! $user instanceof MustVerifyEmailContract) {
            return $next($request);
        }

        // Sudah verified → lanjut
        if ($user->hasVerifiedEmail()) {
            return $next($request);
        }

        // Belum verified:
        // - Request AJAX / Livewire → return 409 + pesan
        // - Request web biasa → redirect ke halaman notice
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Email Anda belum diverifikasi.',
            ], 409);
        }

        return redirect()
            ->route('verification.notice')
            ->with('error', 'Silakan verifikasi email Anda terlebih dahulu.');
    }
}
