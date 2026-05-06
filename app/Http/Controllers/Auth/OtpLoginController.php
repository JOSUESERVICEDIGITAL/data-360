<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\PhoneOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpLoginController extends Controller
{
    public function show()
    {
        if (!session('otp_login_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.otp-login');
    }

    public function verify(Request $request, PhoneOtpService $otpService)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::find(session('otp_login_user_id'));

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$otpService->verify($user, $request->input('code'), 'login')) {
            return back()->withErrors([
                'code' => 'Code incorrect ou expiré.',
            ]);
        }

        Auth::login($user, (bool) session('otp_remember', false));

        $user->update([
            'last_login_ip' => $request->ip(),
            'last_login_at' => now(),
        ]);

        session()->forget(['otp_login_user_id', 'otp_remember']);

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request, PhoneOtpService $otpService)
    {
        $user = User::find(session('otp_login_user_id'));

        if (!$user) {
            return redirect()->route('login');
        }

        $otpService->create($user, 'login', $request->ip());

        return back()->with('status', 'Un nouveau code a été envoyé.');
    }
}