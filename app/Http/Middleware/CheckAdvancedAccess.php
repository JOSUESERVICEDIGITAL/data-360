<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdvancedAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!in_array(Auth::user()->plan, ['premium', 'enterprise'])) {
            abort(403, 'Accès réservé aux comptes Premium et Enterprise.');
        }

        return $next($request);
    }
}
