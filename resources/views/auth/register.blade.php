<x-guest-layout>

    <div class="auth-header">
        <h2>Créer un compte</h2>
        <p>Rejoignez votre espace Data 360</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <div class="auth-group">
            <label>Nom complet</label>
            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Votre nom"
            >
            <x-input-error :messages="$errors->get('name')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                placeholder="exemple@email.com"
            >
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Mot de passe</label>
            <input
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="••••••••"
            >
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Confirmer le mot de passe</label>
            <input
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="••••••••"
            >
            <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
        </div>

        <div class="auth-actions">
            <a href="{{ route('login') }}" class="auth-link">
                Déjà inscrit ?
            </a>

            <button type="submit" class="auth-btn">
                Créer mon compte
            </button>
        </div>
    </form>

    <div class="auth-footer">
        <p>Vous avez déjà un compte ?</p>
        <a href="{{ route('login') }}">Se connecter</a>
    </div>

</x-guest-layout>
