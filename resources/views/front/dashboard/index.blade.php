@extends('back.layouts.app')

@section('title', 'Mon espace | Data 360')

@section('content')
<style>
    .dash-page{min-height:100vh;background:#f8fafc;padding:32px 18px}
    .dash-container{max-width:1200px;margin:0 auto}
    .dash-hero{background:linear-gradient(135deg,#0f172a,#0053b3);color:white;border-radius:28px;padding:30px;margin-bottom:22px}
    .dash-hero h1{margin:0;font-size:34px;font-weight:900}
    .dash-hero p{color:rgba(255,255,255,.78);margin-top:8px}
    .dash-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
    .dash-card{background:white;border:1px solid #e2e8f0;border-radius:22px;padding:20px;box-shadow:0 10px 30px rgba(15,23,42,.04)}
    .dash-label{color:#64748b;font-size:12px;text-transform:uppercase;font-weight:900;letter-spacing:.06em}
    .dash-value{font-size:28px;font-weight:900;color:#0f172a;margin-top:8px}
    .dash-layout{display:grid;grid-template-columns:1fr 340px;gap:20px}
    .panel{background:white;border:1px solid #e2e8f0;border-radius:22px;padding:22px;margin-bottom:20px}
    .panel h2{margin:0 0 16px;font-size:22px;color:#0f172a}
    .table{width:100%;border-collapse:collapse}
    .table th{font-size:12px;color:#64748b;text-align:left;text-transform:uppercase;padding:12px;border-bottom:1px solid #e2e8f0}
    .table td{padding:14px 12px;border-bottom:1px solid #e2e8f0;color:#334155}
    .badge{display:inline-flex;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900}
    .success{background:#dcfce7;color:#166534}
    .danger{background:#fee2e2;color:#991b1b}
    .warning{background:#fff7ed;color:#92400e}
    .info{background:#dbeafe;color:#1e40af}
    .profile-line{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px solid #e2e8f0}
    .profile-line span{color:#64748b}
    .profile-line strong{color:#0f172a;text-align:right}
    .btn{display:inline-flex;background:#0053b3;color:white;text-decoration:none;padding:11px 15px;border-radius:12px;font-weight:900}
    @media(max-width:900px){.dash-grid{grid-template-columns:repeat(2,1fr)}.dash-layout{grid-template-columns:1fr}}
    @media(max-width:560px){.dash-grid{grid-template-columns:1fr}}
</style>

<div class="dash-page">
    <div class="dash-container">

        <section class="dash-hero">
            <h1>Bonjour {{ $user->name }}</h1>
            <p>Bienvenue dans votre espace utilisateur Data 360.</p>
        </section>

        <section class="dash-grid">
            <div class="dash-card">
                <div class="dash-label">Crédits disponibles</div>
                <div class="dash-value">{{ $stats['credits'] }}</div>
            </div>

            <div class="dash-card">
                <div class="dash-label">Recherches totales</div>
                <div class="dash-value">{{ $stats['recherches_total'] }}</div>
            </div>

            <div class="dash-card">
                <div class="dash-label">Recherches ce mois</div>
                <div class="dash-value">{{ $stats['recherches_mois'] }}</div>
            </div>

            <div class="dash-card">
                <div class="dash-label">Statut du compte</div>
                <div class="dash-value" style="font-size:18px;">
                    @if($stats['compte_actif'])
                        <span class="badge success">Actif</span>
                    @else
                        <span class="badge danger">Suspendu</span>
                    @endif
                </div>
            </div>
        </section>

        <div class="dash-layout">
            <main>
                <section class="panel">
                    <h2>Mes recherches récentes</h2>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Adresse recherchée</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($recherches as $recherche)
                                <tr>
                                    <td>{{ $recherche->requete }}</td>
                                    <td>
                                        <span class="badge {{ $recherche->statut === 'trouve' ? 'success' : 'warning' }}">
                                            {{ $recherche->statut }}
                                        </span>
                                    </td>
                                    <td>{{ $recherche->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">Aucune recherche effectuée pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div style="margin-top:16px;">
                        {{ $recherches->links() }}
                    </div>
                </section>

                <section class="panel">
                    <h2>Mes achats</h2>

                    @if($achats->count())
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Offre</th>
                                    <th>Montant</th>
                                    <th>Crédits</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($achats as $achat)
                                    <tr>
                                        <td>{{ $achat->label }}</td>
                                        <td>{{ $achat->amount }} €</td>
                                        <td>{{ $achat->credits }}</td>
                                        <td>{{ $achat->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p style="color:#64748b;">Aucun achat enregistré pour le moment.</p>
                    @endif
                </section>
            </main>

            <aside>
                <section class="panel">
                    <h2>Mon profil</h2>

                    <div class="profile-line">
                        <span>Nom</span>
                        <strong>{{ $user->name }}</strong>
                    </div>

                    <div class="profile-line">
                        <span>Email</span>
                        <strong>{{ $user->email }}</strong>
                    </div>

                    <div class="profile-line">
                        <span>Téléphone</span>
                        <strong>{{ $user->phone ?? '-' }}</strong>
                    </div>

                    <div class="profile-line">
                        <span>Plan</span>
                        <strong>{{ ucfirst($user->plan ?? 'free') }}</strong>
                    </div>

                    <div class="profile-line">
                        <span>Email vérifié</span>
                        <strong>{{ $stats['email_verifie'] ? 'Oui' : 'Non' }}</strong>
                    </div>

                    <div class="profile-line">
                        <span>OTP bypass</span>
                        <strong>{{ $stats['otp_bypass'] ? 'Oui' : 'Non' }}</strong>
                    </div>
                </section>

                <section class="panel">
                    <h2>Notifications</h2>

                    @foreach($notifications as $notification)
                        <div class="badge {{ $notification['type'] }}" style="margin-bottom:10px;">
                            {{ $notification['title'] }}
                        </div>

                        <p style="color:#64748b;margin-top:0;">
                            {{ $notification['message'] }}
                        </p>
                    @endforeach
                </section>

                <section class="panel">
                    <h2>Besoin de crédits ?</h2>
                    <p style="color:#64748b;">Achetez des crédits pour continuer vos analyses immobilières.</p>

                    <a href="{{ route('front.credits.buy') }}" class="btn">
                        Acheter des crédits
                    </a>
                </section>
            </aside>
        </div>

    </div>
</div>
@endsection