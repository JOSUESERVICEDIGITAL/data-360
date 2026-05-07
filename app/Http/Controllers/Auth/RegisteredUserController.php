<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Back\BlockedIdentity;
use App\Models\Back\VisitorDevice;
use App\Models\User;
use App\Services\Security\FingerprintService;
use App\Services\Security\VpnDetectionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(
        Request $request,
        FingerprintService $fingerprintService,
        VpnDetectionService $vpnDetectionService
    ): RedirectResponse {
        if ($request->filled('website')) {
            throw ValidationException::withMessages([
                'email' => 'Inscription refusée.',
            ]);
        }

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone' => ['required', 'string', 'min:8', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
            'fingerprint' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:30'],
            'screen' => ['nullable', 'string', 'max:50'],
        ]);

        $email = strtolower((string) $request->input('email'));
        $domain = substr(strrchr($email, '@'), 1);

        $blockedDomains = [
            'mailinator.com',
            'yopmail.com',
            'tempmail.com',
            '10minutemail.com',
            'guerrillamail.com',
            'trashmail.com',
            'emailondeck.com',
        ];

        if (in_array($domain, $blockedDomains, true)) {
            throw ValidationException::withMessages([
                'email' => 'Les emails temporaires ne sont pas autorisés.',
            ]);
        }

        $ip = $request->ip();
        $fingerprintHash = $fingerprintService->makeHash($request);
        $userAgent = (string) $request->userAgent();

        $blocked = BlockedIdentity::active()
            ->where(function ($query) use ($ip, $fingerprintHash, $domain) {
                $query->where(fn($q) => $q->where('type', 'ip')->where('value', $ip))
                    ->orWhere(fn($q) => $q->where('type', 'fingerprint')->where('value', $fingerprintHash))
                    ->orWhere(fn($q) => $q->where('type', 'email_domain')->where('value', $domain));
            })
            ->first();

        if ($blocked) {
            throw ValidationException::withMessages([
                'email' => $blocked->reason ?: 'Inscription bloquée.',
            ]);
        }

        $vpnData = $vpnDetectionService->check($ip);

        $isRiskyNetwork = ($vpnData['is_vpn'] ?? false)
            || ($vpnData['is_proxy'] ?? false)
            || ($vpnData['is_tor'] ?? false)
            || ($vpnData['is_datacenter'] ?? false);

        if ($isRiskyNetwork) {
            throw ValidationException::withMessages([
                'email' => 'Connexion VPN/proxy détectée. Veuillez désactiver le VPN pour créer un compte.',
            ]);
        }

        $sameDeviceAccounts = VisitorDevice::where('fingerprint_hash', $fingerprintHash)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        if ($sameDeviceAccounts >= 2) {
            throw ValidationException::withMessages([
                'email' => 'Trop de comptes ont déjà été créés depuis cet appareil.',
            ]);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $email,
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->input('password')),
            'is_admin' => false,
            'is_active' => true,
            'credits' => 0,
            'plan' => 'free',
            'last_login_ip' => $ip,
            'last_login_at' => now(),
        ]);

        VisitorDevice::updateOrCreate(
            [
                'fingerprint_hash' => $fingerprintHash,
                'ip_address' => $ip,
            ],
            [
                'user_id' => $user->id,
                'user_agent_hash' => hash('sha256', $userAgent),
                'user_agent' => $userAgent,
                'timezone' => $request->input('timezone'),
                'language' => $request->input('language'),
                'is_vpn' => $vpnData['is_vpn'] ?? false,
                'is_proxy' => $vpnData['is_proxy'] ?? false,
                'is_tor' => $vpnData['is_tor'] ?? false,
                'is_datacenter' => $vpnData['is_datacenter'] ?? false,
                'risk_score' => $vpnData['risk_score'] ?? 0,
                'last_seen_at' => now(),
                'raw_data' => [
                    'vpn' => $vpnData,
                    'screen' => $request->input('screen'),
                ],
            ]
        );

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('front.credits.buy')
            ->with('success', 'Compte créé. Achetez des crédits ou contactez l’administrateur.');
    }
}