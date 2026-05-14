<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pas connecté
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Utilisateur suspendu
        if (!auth()->user()->is_active) {

            auth()->logout();

            return redirect()
                ->route('login')
                ->with('error', 'Votre compte a été suspendu.');
        }

        // Pas admin
        if (!auth()->user()->is_admin) {

            abort(403, 'Accès administrateur refusé.');
        }

        return $next($request);
    }
}