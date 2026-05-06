<x-guest-layout>

    <div class="auth-header">
        <h2>Vérification téléphone</h2>
        <p>Entrez le code reçu sur votre numéro.</p>
    </div>

    <x-auth-session-status class="auth-alert" :status="session('status')" />

    <form method="POST" action="{{ route('auth.otp.verify') }}" class="auth-form">
        @csrf

        <div class="auth-group">
            <label>Code OTP</label>
            <input
                type="text"
                name="code"
                required
                maxlength="6"
                inputmode="numeric"
                placeholder="123456"
                autofocus
            >
            <x-input-error :messages="$errors->get('code')" class="auth-error" />
        </div>

        <button type="submit" class="auth-btn" style="width:100%;">
            Valider le code
        </button>
    </form>

    <form method="POST" action="{{ route('auth.otp.resend') }}" style="margin-top:14px;text-align:center;">
        @csrf
        <button type="submit" class="auth-link" style="border:none;background:none;cursor:pointer;">
            Renvoyer le code
        </button>
    </form>

    <div class="auth-footer">
        <p>Le code expire après 10 minutes.</p>
        <a href="{{ route('login') }}">Retour à la connexion</a>
    </div>

</x-guest-layout>