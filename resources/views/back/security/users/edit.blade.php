@extends('back.layouts.app')

@section('title', 'Modifier un utilisateur | Data Rocket')

@section('content')
<style>
    .user-form-page{max-width:1080px;margin:0 auto;padding:24px}
    .user-form-header{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:24px}
    .user-form-header h1{font-size:30px;font-weight:950;color:#0f172a;margin:0}
    .user-form-header p{color:#64748b;margin-top:7px;line-height:1.6}
    .user-card{background:white;border:1px solid #e2e8f0;border-radius:26px;padding:30px;box-shadow:0 16px 45px rgba(15,23,42,.07)}
    .user-section{margin-bottom:30px}
    .user-section-title{display:flex;align-items:center;gap:10px;font-size:14px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;color:#0053b3;margin-bottom:18px}
    .user-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
    .user-group label{display:block;font-size:13px;font-weight:850;color:#334155;margin-bottom:7px}
    .user-group input,.user-group select{width:100%;border:1.5px solid #dbe3ef;border-radius:14px;padding:13px 14px;font-size:14px;background:white}
    .user-group input:focus,.user-group select:focus{outline:none;border-color:#0053b3;box-shadow:0 0 0 4px rgba(0,83,179,.10)}
    .user-help{font-size:12px;color:#64748b;margin-top:6px;line-height:1.5}
    .user-error{font-size:12px;color:#b91c1c;font-weight:800;margin-top:6px}
    .user-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:18px}
    .user-check{border:1px solid #e2e8f0;background:#f8fafc;border-radius:18px;padding:15px;display:flex;gap:12px;align-items:flex-start;cursor:pointer}
    .user-check:hover{border-color:#0053b3;background:#f0f7ff}
    .user-check input{margin-top:3px;width:18px;height:18px}
    .user-check strong{display:block;color:#0f172a;font-size:14px}
    .user-check span{display:block;color:#64748b;font-size:12px;margin-top:4px;line-height:1.45}
    .user-actions{display:flex;justify-content:flex-end;gap:12px;border-top:1px solid #e2e8f0;padding-top:24px}
    .btn{border:none;border-radius:14px;padding:12px 18px;font-weight:900;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px}
    .btn-primary{background:#0053b3;color:white}
    .btn-primary:hover{background:#003d85;color:white}
    .btn-gray{background:#f1f5f9;color:#334155}
    .btn-gray:hover{background:#e2e8f0;color:#0f172a}
    .alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;border-radius:16px;padding:16px;margin-bottom:20px;font-weight:800}
    .alert-error div+div{margin-top:6px}
    .user-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:24px}
    .user-meta-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:14px}
    .user-meta-label{font-size:11px;font-weight:900;color:#64748b;text-transform:uppercase;letter-spacing:.06em}
    .user-meta-value{font-size:14px;font-weight:900;color:#0f172a;margin-top:5px}
    @media(max-width:760px){.user-grid,.user-checks,.user-meta{grid-template-columns:1fr}.user-form-header{flex-direction:column}.user-actions{flex-direction:column}.btn{width:100%}}
</style>

<div class="user-form-page">
    <div class="user-form-header">
        <div>
            <h1>Modifier un utilisateur</h1>
            <p>Modifiez les informations, crédits, accès administrateur, OTP et vérification email.</p>
        </div>

        <a href="{{ route('admin.security.users.index') }}" class="btn btn-gray">
            Retour à la liste
        </a>
    </div>

    @if ($errors->any())
        <div class="alert-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="user-card">
        <div class="user-meta">
            <div class="user-meta-card">
                <div class="user-meta-label">ID utilisateur</div>
                <div class="user-meta-value">#{{ $user->id }}</div>
            </div>

            <div class="user-meta-card">
                <div class="user-meta-label">Créé le</div>
                <div class="user-meta-value">{{ optional($user->created_at)->format('d/m/Y H:i') ?? '-' }}</div>
            </div>

            <div class="user-meta-card">
                <div class="user-meta-label">Dernière connexion</div>
                <div class="user-meta-value">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '-' }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.security.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="user-section">
                <div class="user-section-title">Identité du compte</div>

                <div class="user-grid">
                    <div class="user-group">
                        <label>Nom complet</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required placeholder="Nom complet">
                        @error('name') <div class="user-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="user-group">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required placeholder="exemple@email.com">
                        @error('email') <div class="user-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="user-group">
                        <label>Téléphone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+212 6XX XXX XXX">
                        <div class="user-help">Utilisé pour OTP SMS si le laissez-passer OTP est désactivé.</div>
                        @error('phone') <div class="user-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="user-group">
                        <label>Plan</label>
                        <select name="plan">
                            <option value="free" {{ old('plan', $user->plan) === 'free' ? 'selected' : '' }}>Free</option>
                            <option value="premium" {{ old('plan', $user->plan) === 'premium' ? 'selected' : '' }}>Premium</option>
                            <option value="enterprise" {{ old('plan', $user->plan) === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                        </select>
                        @error('plan') <div class="user-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="user-section">
                <div class="user-section-title">Sécurité du compte</div>

                <div class="user-grid">
                    <div class="user-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="password" placeholder="Laisser vide pour conserver l’ancien">
                        <div class="user-help">Remplissez uniquement si vous voulez modifier le mot de passe.</div>
                        @error('password') <div class="user-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="user-group">
                        <label>Confirmation du mot de passe</label>
                        <input type="password" name="password_confirmation" placeholder="Répéter le nouveau mot de passe">
                        @error('password_confirmation') <div class="user-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="user-section">
                <div class="user-section-title">Accès, crédits et validations</div>

                <div class="user-grid">
                    <div class="user-group">
                        <label>Crédits disponibles</label>
                        <input type="number" name="credits" value="{{ old('credits', $user->credits ?? 0) }}" min="0">
                        @error('credits') <div class="user-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="user-checks">
                    <label class="user-check">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <div>
                            <strong>Compte actif</strong>
                            <span>L’utilisateur pourra se connecter.</span>
                        </div>
                    </label>

                    <label class="user-check">
                        <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                        <div>
                            <strong>Administrateur</strong>
                            <span>Accès au backoffice Data Rocket.</span>
                        </div>
                    </label>

                    <label class="user-check">
                        <input type="checkbox" name="otp_bypass" value="1" {{ old('otp_bypass', $user->otp_bypass) ? 'checked' : '' }}>
                        <div>
                            <strong>Laissez-passer OTP</strong>
                            <span>Connexion sans code SMS/OTP.</span>
                        </div>
                    </label>

                    <label class="user-check">
                        <input type="checkbox" name="email_verified" value="1" {{ old('email_verified', !is_null($user->email_verified_at)) ? 'checked' : '' }}>
                        <div>
                            <strong>Email vérifié</strong>
                            <span>Marquer l’adresse email comme confirmée.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="user-actions">
                <a href="{{ route('admin.security.users.index') }}" class="btn btn-gray">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>
@endsection