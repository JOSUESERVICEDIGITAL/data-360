<x-guest-layout>

    <div class="auth-header">
        <h2>Créer un compte</h2>
        <p>Continuez vos recherches avec un compte Data 360</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form" id="registerForm">
        @csrf

        <input type="hidden" name="fingerprint" id="fingerprint">
        <input type="hidden" name="timezone" id="timezone">
        <input type="hidden" name="language" id="language">
        <input type="hidden" name="screen" id="screen">

        {{-- Honeypot anti-bot --}}
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

        <div class="auth-group">
            <label>Nom complet</label>
            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Votre nom complet"
            >
            <x-input-error :messages="$errors->get('name')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Email professionnel ou personnel</label>
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
            <label>Téléphone</label>
            <input
                type="tel"
                name="phone"
                value="{{ old('phone') }}"
                required
                autocomplete="tel"
                placeholder="+33 6 00 00 00 00"
            >
            <x-input-error :messages="$errors->get('phone')" class="auth-error" />
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

        <div class="auth-remember">
            <label>
                <input type="checkbox" name="terms" value="1" required>
                J’accepte les conditions d’utilisation et la politique anti-fraude.
            </label>
            <x-input-error :messages="$errors->get('terms')" class="auth-error" />
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
        <p>Un compte créé ne donne pas automatiquement des crédits.</p>
        <a href="{{ route('login') }}">Se connecter</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const payload = [
                navigator.userAgent || '',
                navigator.language || '',
                Intl.DateTimeFormat().resolvedOptions().timeZone || '',
                screen.width + 'x' + screen.height,
                screen.colorDepth || '',
                navigator.platform || ''
            ].join('|');

            async function sha256(text) {
                const buffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
                return Array.from(new Uint8Array(buffer)).map(b => b.toString(16).padStart(2, '0')).join('');
            }

            sha256(payload).then(hash => {
                document.getElementById('fingerprint').value = hash;
            });

            document.getElementById('timezone').value = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
            document.getElementById('language').value = navigator.language || '';
            document.getElementById('screen').value = screen.width + 'x' + screen.height;
        });
    </script>

</x-guest-layout> 
