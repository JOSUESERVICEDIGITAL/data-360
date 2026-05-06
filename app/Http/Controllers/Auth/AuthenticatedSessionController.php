<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Security\PhoneOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, PhoneOtpService $otpService): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', strtolower($request->input('email')))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors([
                'email' => 'Identifiants incorrects.',
            ])->onlyInput('email');
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'email' => 'Votre compte est suspendu.',
            ])->onlyInput('email');
        }
        if ($user->otp_bypass || $user->is_admin) {
            Auth::login($user, (bool) $request->boolean('remember'));

            $user->update([
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        if (!$user->phone) {
            return back()->withErrors([
                'email' => 'Aucun numéro de téléphone n’est associé à ce compte.',
            ])->onlyInput('email');
        }

        $otpService->create($user, 'login', $request->ip());

        session([
            'otp_login_user_id' => $user->id,
            'otp_remember' => (bool) $request->boolean('remember'),
        ]);

        return redirect()->route('auth.otp.show');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}