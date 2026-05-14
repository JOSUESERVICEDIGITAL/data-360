@extends('back.layouts.app')

@section('title', 'Mon profil | Data 360')

@section('content')

<style>
    .profile-page{min-height:100vh;background:#f8fafc;padding:32px 18px}
    .profile-container{max-width:1150px;margin:0 auto}
    .profile-hero{background:linear-gradient(135deg,#0f172a,#0053b3);color:white;border-radius:28px;padding:32px;margin-bottom:24px;display:flex;justify-content:space-between;gap:20px;align-items:flex-start}
    .profile-hero h1{margin:0;font-size:34px;font-weight:950}
    .profile-hero p{margin:8px 0 0;color:rgba(255,255,255,.78)}
    .profile-badge{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18);padding:10px 14px;border-radius:999px;font-weight:900}
    .profile-grid{display:grid;grid-template-columns:1fr 360px;gap:22px}
    .panel{background:white;border:1px solid #e2e8f0;border-radius:24px;padding:24px;margin-bottom:20px;box-shadow:0 12px 35px rgba(15,23,42,.05)}
    .panel h2{margin:0;color:#0f172a;font-size:22px;font-weight:950}
    .panel p{color:#64748b;line-height:1.6}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;font-size:13px;font-weight:900;color:#334155;margin-bottom:7px}
    .form-group input{width:100%;border:1.5px solid #e2e8f0;border-radius:14px;padding:13px 14px;outline:none}
    .form-group input:focus{border-color:#0053b3;box-shadow:0 0 0 4px rgba(0,83,179,.10)}
    .error{font-size:12px;color:#b91c1c;font-weight:800;margin-top:6px}
    .btn{border:none;border-radius:14px;padding:12px 17px;font-weight:900;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
    .btn-primary{background:#0053b3;color:white}
    .btn-gray{background:#f1f5f9;color:#334155}
    .btn-danger{background:#b91c1c;color:white}
    .status-line{display:flex;justify-content:space-between;gap:12px;padding:13px 0;border-bottom:1px solid #e2e8f0}
    .status-line span{color:#64748b}
    .status-line strong{color:#0f172a;text-align:right}
    .badge{display:inline-flex;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900}
    .success{background:#dcfce7;color:#166534}
    .danger{background:#fee2e2;color:#991b1b}
    .warning{background:#fff7ed;color:#92400e}
    .info{background:#dbeafe;color:#1e40af}
    .alert{padding:14px 16px;border-radius:16px;margin-bottom:18px;font-weight:800}
    .alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
    .alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
    @media(max-width:900px){.profile-grid{grid-template-columns:1fr}.profile-hero{flex-direction:column}.form-grid{grid-template-columns:1fr}}
</style>

<div class="profile-page">
    <div class="profile-container">

        <section class="profile-hero">
            <div>
                <h1>Mon profil</h1>
                <p>Gérez vos informations personnelles, votre sécurité et l’état de votre compte Data 360.</p>
            </div>

            <div class="profile-badge">
                {{ ucfirst(auth()->user()->plan ?? 'free') }} · {{ auth()->user()->credits ?? 0 }} crédits
            </div>
        </section>

        @if(session('status') === 'profile-updated')
            <div class="alert alert-success">Profil mis à jour avec succès.</div>
        @endif

        @if(session('status') === 'password-updated')
            <div class="alert alert-success">Mot de passe modifié avec succès.</div>
        @endif

        @if(session('status') === 'verification-link-sent')
            <div class="alert alert-success">Un nouvel email de vérification a été envoyé.</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="profile-grid">
            <main>

                <section class="panel">
                    <h2>Informations personnelles</h2>
                    <p>Modifiez votre nom, email et téléphone. Si vous changez votre email, une nouvelle confirmation sera demandée.</p>

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nom complet</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+212 6XX XXX XXX">
                                @error('phone') <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn-primary">Enregistrer les informations</button>
                        </div>
                    </form>

                    @if(!$user->hasVerifiedEmail())
                        <form method="POST" action="{{ route('verification.send') }}" style="margin-top:16px;">
                            @csrf
                            <button type="submit" class="btn btn-gray">Renvoyer l’email de confirmation</button>
                        </form>
                    @endif
                </section>

                <section class="panel">
                    <h2>Sécurité du compte</h2>
                    <p>Changez votre mot de passe régulièrement pour protéger votre compte.</p>

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Mot de passe actuel</label>
                                <input type="password" name="current_password" autocomplete="current-password">
                                @error('current_password', 'updatePassword') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Nouveau mot de passe</label>
                                <input type="password" name="password" autocomplete="new-password">
                                @error('password', 'updatePassword') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>Confirmer le nouveau mot de passe</label>
                                <input type="password" name="password_confirmation" autocomplete="new-password">
                                @error('password_confirmation', 'updatePassword') <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn-primary">Modifier le mot de passe</button>
                        </div>
                    </form>
                </section>

            </main>

            <aside>
                <section class="panel">
                    <h2>État du compte</h2>

                    <div class="status-line">
                        <span>Statut</span>
                        <strong>
                            @if($user->is_active)
                                <span class="badge success">Actif</span>
                            @else
                                <span class="badge danger">Suspendu</span>
                            @endif
                        </strong>
                    </div>

                    <div class="status-line">
                        <span>Email</span>
                        <strong>
                            @if($user->hasVerifiedEmail())
                                <span class="badge success">Vérifié</span>
                            @else
                                <span class="badge warning">Non vérifié</span>
                            @endif
                        </strong>
                    </div>

                    <div class="status-line">
                        <span>OTP</span>
                        <strong>
                            @if($user->otp_bypass)
                                <span class="badge info">Laissez-passer actif</span>
                            @else
                                <span class="badge warning">OTP requis</span>
                            @endif
                        </strong>
                    </div>

                    <div class="status-line">
                        <span>Plan</span>
                        <strong>{{ ucfirst($user->plan ?? 'free') }}</strong>
                    </div>

                    <div class="status-line">
                        <span>Crédits</span>
                        <strong>{{ $user->credits ?? 0 }}</strong>
                    </div>

                    <div class="status-line">
                        <span>Dernière IP</span>
                        <strong>{{ $user->last_login_ip ?? '-' }}</strong>
                    </div>

                    <div class="status-line">
                        <span>Dernière connexion</span>
                        <strong>{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '-' }}</strong>
                    </div>
                </section>

                <section class="panel">
                    <h2>Notifications</h2>

                    @if(!$user->is_active)
                        <p><span class="badge danger">Compte suspendu</span></p>
                        <p>Votre compte est suspendu. Contactez l’administration.</p>
                    @elseif(!$user->hasVerifiedEmail())
                        <p><span class="badge warning">Email à confirmer</span></p>
                        <p>Veuillez confirmer votre adresse email pour sécuriser votre compte.</p>
                    @else
                        <p><span class="badge success">Tout est en règle</span></p>
                        <p>Votre profil est opérationnel.</p>
                    @endif
                </section>
            </aside>
        </div>

    </div>
</div>

@endsection