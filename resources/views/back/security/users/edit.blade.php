@extends('back.layouts.app')

@section('title', 'Modifier un utilisateur | Data Rocket')

@section('content')
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0053b3;
            box-shadow: 0 0 0 3px rgba(0, 83, 179, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .checkbox-group input {
            width: auto;
        }

        .checkbox-group label {
            margin-bottom: 0;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .error-message {
            color: #dc2626;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .info-badge {
            background: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            color: #475569;
            display: inline-block;
        }
    </style>

    <div class="sec-page">
        <div class="sec-header">
            <div class="sec-title">
                <h1>✏️ Modifier l'utilisateur</h1>
                <p>Modifiez les informations du compte</p>
            </div>

            <a href="{{ route('admin.security.users.index') }}" class="sec-btn gray">
                ← Retour à la liste
            </a>
        </div>

        @if ($errors->any())
            <div class="alert-error" style="margin-bottom: 1.5rem;">
                <ul style="margin-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>❌ {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="sec-card form-container">
            <form method="POST" action="{{ route('admin.security.users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>ID utilisateur</label>
                    <div class="info-badge">#{{ $user->id }}</div>
                </div>

                <div class="form-group">
                    <label>Nom complet *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+33 X XX XX XX XX">
                        @error('phone')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="password" placeholder="Laissez vide pour ne pas changer">
                        <div class="password-hint" style="font-size:0.7rem; color:#64748b;">Minimum 8 caractères, une majuscule, un chiffre, un caractère spécial</div>
                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation" placeholder="Confirmez le nouveau mot de passe">
                        @error('password_confirmation')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Crédits</label>
                        <input type="number" name="credits" value="{{ old('credits', $user->credits ?? 0) }}" min="0">
                        @error('credits')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Plan</label>
                        <select name="plan">
                            <option value="free" {{ old('plan', $user->plan) === 'free' ? 'selected' : '' }}>Free</option>
                            <option value="premium" {{ old('plan', $user->plan) === 'premium' ? 'selected' : '' }}>Premium</option>
                            <option value="enterprise" {{ old('plan', $user->plan) === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                        </select>
                        @error('plan')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="is_active" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label for="is_active">✓ Compte actif</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="is_admin" id="is_admin" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                        <label for="is_admin">👑 Administrateur</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="sec-btn green">💾 Enregistrer les modifications</button>
                    <a href="{{ route('admin.security.users.index') }}" class="sec-btn gray">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection