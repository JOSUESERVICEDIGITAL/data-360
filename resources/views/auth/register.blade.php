<x-guest-layout>

    <div class="auth-header">
        <h2>Créer un compte</h2>
        <p>Continuez vos recherches avec un compte Data 360</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form" id="registerForm" novalidate>
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
                id="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Votre nom complet"
            >
            <div class="auth-error" id="nameError"></div>
            <x-input-error :messages="$errors->get('name')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Email professionnel ou personnel</label>
            <input
                type="email"
                name="email"
                id="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                placeholder="exemple@email.com"
            >
            <div class="auth-error" id="emailError"></div>
            <x-input-error :messages="$errors->get('email')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Téléphone</label>
            <input
                type="tel"
                name="phone"
                id="phone"
                value="{{ old('phone') }}"
                required
                autocomplete="tel"
                placeholder="+33 6 00 00 00 00"
            >
            <div class="auth-error" id="phoneError"></div>
            <x-input-error :messages="$errors->get('phone')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Mot de passe</label>
            <input
                type="password"
                name="password"
                id="password"
                required
                autocomplete="new-password"
                placeholder="••••••••"
            >
            <div class="password-strength" id="passwordStrength"></div>
            <div class="auth-error" id="passwordError"></div>
            <x-input-error :messages="$errors->get('password')" class="auth-error" />
        </div>

        <div class="auth-group">
            <label>Confirmer le mot de passe</label>
            <input
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="••••••••"
            >
            <div class="auth-error" id="passwordConfirmError"></div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
        </div>

        <div class="auth-remember">
            <label>
                <input type="checkbox" name="terms" id="terms" value="1" required>
                J’accepte les conditions d’utilisation et la politique anti-fraude.
            </label>
            <div class="auth-error" id="termsError"></div>
            <x-input-error :messages="$errors->get('terms')" class="auth-error" />
        </div>

        <div class="auth-actions">
            <a href="{{ route('login') }}" class="auth-link">
                Déjà inscrit ?
            </a>

            <button type="submit" id="submitBtn" class="auth-btn">
                Créer mon compte
            </button>
        </div>
    </form>

    <div class="auth-footer">
        <p>Un compte créé ne donne pas automatiquement des crédits.</p>
        <a href="{{ route('login') }}">Se connecter</a>
    </div>

    <style>
        .password-strength {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .password-strength.weak { color: #dc2626; }
        .password-strength.medium { color: #f59e0b; }
        .password-strength.strong { color: #10b981; }
        
        .auth-error {
            color: #dc2626;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        input.error {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }
        
        input.valid {
            border-color: #10b981 !important;
        }
        
        .auth-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ==================== FINGERPRINT (inchangé) ====================
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

            // ==================== VALIDATIONS ====================
            
            // 1. Validation Email (critères stricts)
            function validateEmail(email) {
                const emailRegex = /^(?=[a-zA-Z0-9][a-zA-Z0-9._%+-]{0,63}@)(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/;
                
                if (!email) return "L'email est requis";
                if (email.length < 5) return "Email trop court";
                if (email.length > 254) return "Email trop long";
                if (!emailRegex.test(email)) return "Format d'email invalide (exemple: nom@domaine.com)";
                
                const [localPart, domain] = email.split('@');
                if (localPart.length > 64) return "Partie locale trop longue";
                if (localPart.startsWith('.') || localPart.endsWith('.')) return "L'email ne peut pas commencer ou finir par un point";
                if (localPart.includes('..')) return "Double point interdit dans l'email";
                
                return null;
            }

            // 2. Validation Téléphone avec indicatif pays obligatoire
            function validatePhone(phone) {
                if (!phone) return "Le numéro de téléphone est requis";
                
                // Supprimer espaces, tirets, points
                const cleaned = phone.replace(/[\s\-\.\(\)]/g, '');
                
                // Vérification présence obligatoire de l'indicatif international
                const internationalRegex = /^\+[1-9][0-9]{1,3}[0-9]{4,14}$/;
                if (!internationalRegex.test(cleaned)) {
                    return "Le numéro DOIT commencer par l'indicatif pays (ex: +33 pour France, +1 pour USA)";
                }
                
                // Vérification longueur minimale après indicatif
                const withoutPrefix = cleaned.replace(/^\+[1-9][0-9]{1,3}/, '');
                if (withoutPrefix.length < 4) {
                    return "Le numéro est trop court après l'indicatif";
                }
                if (withoutPrefix.length > 15) {
                    return "Le numéro est trop long";
                }
                
                // Vérification que le reste ne contient que des chiffres
                if (!/^\d+$/.test(withoutPrefix)) {
                    return "Le numéro ne doit contenir que des chiffres après l'indicatif";
                }
                
                return null;
            }

            // 3. Validation Mot de passe (Maj + Min + Chiffre + Caractère spécial)
            function validatePassword(password) {
                if (!password) return "Le mot de passe est requis";
                if (password.length < 8) return "Le mot de passe doit contenir au moins 8 caractères";
                if (password.length > 64) return "Le mot de passe ne peut pas dépasser 64 caractères";
                
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
                
                if (!hasUppercase) return "Le mot de passe doit contenir au moins une majuscule (A-Z)";
                if (!hasLowercase) return "Le mot de passe doit contenir au moins une minuscule (a-z)";
                if (!hasNumber) return "Le mot de passe doit contenir au moins un chiffre (0-9)";
                if (!hasSpecial) return "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&* etc.)";
                
                // Vérification mots de passe interdits
                const commonPasswords = ['Password123!', 'Admin2024!', 'User@123', 'Test@1234'];
                if (commonPasswords.includes(password)) {
                    return "Ce mot de passe est trop commun, veuillez en choisir un autre";
                }
                
                return null;
            }
            
            // Évaluation force du mot de passe
            function getPasswordStrength(password) {
                if (!password) return { level: 0, text: '' };
                
                let score = 0;
                if (password.length >= 8) score++;
                if (password.length >= 12) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[a-z]/.test(password)) score++;
                if (/\d/.test(password)) score++;
                if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) score++;
                if (password.length >= 16) score += 2;
                
                if (score <= 3) return { level: 'weak', text: '🔴 Faible - Ajoutez majuscule, chiffre, caractère spécial' };
                if (score <= 5) return { level: 'medium', text: '🟡 Moyen - Bon début, mais peut être renforcé' };
                return { level: 'strong', text: '🟢 Fort - Excellent mot de passe' };
            }

            // 4. Validation Nom
            function validateName(name) {
                if (!name) return "Le nom complet est requis";
                if (name.length < 2) return "Nom trop court";
                if (name.length > 100) return "Nom trop long";
                if (!/^[a-zA-Zàâçéèêëîïôûùüÿñœæ\s\-']+$/i.test(name)) {
                    return "Le nom ne doit contenir que des lettres, espaces, tirets ou apostrophes";
                }
                return null;
            }

            // ==================== Écouteurs d'événements ====================
            
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const passwordInput = document.getElementById('password');
            const passwordConfirmInput = document.getElementById('password_confirmation');
            const termsCheckbox = document.getElementById('terms');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('registerForm');
            
            // Afficher erreur
            function showError(element, errorId, message) {
                const errorDiv = document.getElementById(errorId);
                if (errorDiv) {
                    errorDiv.textContent = message || '';
                }
                if (element) {
                    if (message) {
                        element.classList.add('error');
                        element.classList.remove('valid');
                    } else {
                        element.classList.remove('error');
                        element.classList.add('valid');
                    }
                }
            }
            
            // Validation globale
            function validateForm() {
                const nameError = validateName(nameInput.value);
                const emailError = validateEmail(emailInput.value);
                const phoneError = validatePhone(phoneInput.value);
                const passwordError = validatePassword(passwordInput.value);
                const passwordConfirmError = (passwordInput.value !== passwordConfirmInput.value) ? "Les mots de passe ne correspondent pas" : null;
                const termsError = (!termsCheckbox.checked) ? "Vous devez accepter les conditions d'utilisation" : null;
                
                showError(nameInput, 'nameError', nameError);
                showError(emailInput, 'emailError', emailError);
                showError(phoneInput, 'phoneError', phoneError);
                showError(passwordInput, 'passwordError', passwordError);
                showError(passwordConfirmInput, 'passwordConfirmError', passwordConfirmError);
                showError(termsCheckbox, 'termsError', termsError);
                
                const isValid = !nameError && !emailError && !phoneError && !passwordError && !passwordConfirmError && !termsError;
                submitBtn.disabled = !isValid;
                return isValid;
            }
            
            // Force mot de passe
            passwordInput.addEventListener('input', function() {
                const strength = getPasswordStrength(this.value);
                const strengthDiv = document.getElementById('passwordStrength');
                if (strengthDiv) {
                    strengthDiv.textContent = strength.text;
                    strengthDiv.className = 'password-strength ' + strength.level;
                }
                validateForm();
            });
            
            // Événements de validation
            nameInput.addEventListener('input', validateForm);
            emailInput.addEventListener('input', validateForm);
            phoneInput.addEventListener('input', validateForm);
            passwordConfirmInput.addEventListener('input', validateForm);
            termsCheckbox.addEventListener('change', validateForm);
            
            // Validation avant soumission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    // Scroll vers la première erreur
                    const firstError = document.querySelector('.error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
            
            // Validation initiale
            validateForm();
        });
    </script>

</x-guest-layout>