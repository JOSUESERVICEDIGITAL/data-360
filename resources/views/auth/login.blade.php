<x-guest-layout>

    <div class="auth-header">
        <h2>Connexion</h2>
        <p>Accédez à votre dashboard Data 360</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="auth-alert" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <!-- Email -->
        <div class="auth-group">
            <label>Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                placeholder="exemple@email.com"
            >
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <!-- Password -->
        <div class="auth-group">
            <label>Mot de passe</label>
            <input
                type="password"
                name="password"
                required
                placeholder="••••••••"
            >
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <!-- Remember -->
        <div class="auth-remember">
            <label>
                <input type="checkbox" name="remember">
                Se souvenir de moi
            </label>
        </div>

        <!-- Actions -->
        <div class="auth-actions">

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">
                    Mot de passe oublié ?
                </a>
            @endif

            <button type="submit" class="auth-btn">
                Se connecter
            </button>
        </div>
    </form>
{{-- //mise jour --}}
    <div class="auth-footer">
        <p>Pas encore de compte ?</p>
        <a href="{{ route('register') }}">Créer un compte</a>
    </div>
</x-guest-layout>
