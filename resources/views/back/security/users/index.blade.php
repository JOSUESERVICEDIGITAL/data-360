@extends('back.layouts.app')
@section('title', 'Gestion des utilisateurs | Data Rocket')
@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root {
    --dr-primary:      #0053b3;
    --dr-primary-dark: #003d85;
    --dr-primary-soft: #e6f0ff;
    --dr-success:      #15803d;
    --dr-success-soft: #dcfce7;
    --dr-danger:       #b91c1c;
    --dr-danger-soft:  #fee2e2;
    --dr-warning:      #b45309;
    --dr-warning-soft: #fff7ed;
    --dr-info:         #1d4ed8;
    --dr-info-soft:    #dbeafe;
    --dr-dark:         #0f172a;
    --dr-muted:        #64748b;
    --dr-border:       #e2e8f0;
    --dr-gold:         #f59e0b;
    --dr-gold-soft:    #fffbeb;
}

/* ── Page ── */
.users-page {
    min-height: 100vh;
    padding: clamp(12px, 3vw, 28px);
    background: radial-gradient(circle at top left, rgba(0,83,179,.06), transparent 40%),
                linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}

.users-container { max-width: 1320px; margin: 0 auto; }

/* ── Hero ── */
.users-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 62%, #0053b3 100%);
    color: white;
    border-radius: clamp(18px, 3vw, 30px);
    padding: clamp(18px, 4vw, 30px);
    box-shadow: 0 24px 70px rgba(15,23,42,.20);
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
    margin-bottom: 20px;
    overflow: hidden;
    position: relative;
}

.users-hero::after {
    content: "";
    position: absolute;
    right: -90px; top: -120px;
    width: 300px; height: 300px;
    border-radius: 999px;
    background: rgba(255,255,255,.08);
    pointer-events: none;
}

.hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.12);
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 12px;
}

.users-hero h1 {
    margin: 0;
    font-size: clamp(22px, 4vw, 38px);
    font-weight: 900;
    letter-spacing: -.03em;
    line-height: 1.1;
}

.users-hero p {
    margin: 10px 0 0;
    color: rgba(255,255,255,.75);
    line-height: 1.6;
    font-size: clamp(13px, 1.5vw, 15px);
}

.hero-actions {
    position: relative;
    z-index: 2;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
    justify-content: flex-end;
}

/* ── Buttons ── */
.dr-btn {
    border: none;
    border-radius: 12px;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: all .2s ease;
    white-space: nowrap;
    line-height: 1;
}

.dr-btn-primary  { background: var(--dr-primary); color: white; }
.dr-btn-primary:hover { background: var(--dr-primary-dark); color: white; transform: translateY(-1px); }
.dr-btn-white    { background: white; color: var(--dr-primary); }
.dr-btn-white:hover { background: #f0f7ff; color: var(--dr-primary-dark); }
.dr-btn-soft     { background: #f1f5f9; color: #334155; }
.dr-btn-soft:hover { background: #e2e8f0; }
.dr-btn-danger   { background: var(--dr-danger); color: white; }
.dr-btn-danger:hover { background: #991b1b; }
.dr-btn-warning  { background: var(--dr-warning); color: white; }
.dr-btn-sm { padding: 8px 12px; font-size: 12px; }

/* ── Alerts ── */
.alert {
    border-radius: 14px;
    padding: 13px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-weight: 700;
    font-size: 14px;
}

.alert-success { background: var(--dr-success-soft); color: #166534; border: 1px solid #bbf7d0; }
.alert-error   { background: var(--dr-danger-soft);  color: #991b1b; border: 1px solid #fecaca; }

/* ── KPI Grid ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}

.kpi-card {
    background: white;
    border: 1px solid var(--dr-border);
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 4px 20px rgba(15,23,42,.04);
    transition: transform .2s ease, box-shadow .2s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(15,23,42,.08);
}

.kpi-icon {
    width: 38px; height: 38px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: var(--dr-primary-soft);
    color: var(--dr-primary);
    margin-bottom: 10px;
    font-size: 15px;
}

.kpi-label {
    color: var(--dr-muted);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 5px;
}

.kpi-value {
    color: var(--dr-dark);
    font-size: clamp(18px, 3vw, 24px);
    font-weight: 900;
}

/* ── Panel ── */
.panel {
    background: white;
    border: 1px solid var(--dr-border);
    border-radius: clamp(16px, 2.5vw, 22px);
    box-shadow: 0 4px 20px rgba(15,23,42,.04);
    margin-bottom: 20px;
    overflow: hidden;
}

.panel-header {
    padding: clamp(14px, 2.5vw, 20px) clamp(16px, 2.5vw, 22px);
    border-bottom: 1px solid var(--dr-border);
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
    flex-wrap: wrap;
}

.panel-title {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.panel-title-icon {
    width: 38px; height: 38px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: var(--dr-primary-soft);
    color: var(--dr-primary);
    flex-shrink: 0;
}

.panel-title h2 { margin: 0; font-size: clamp(15px, 2vw, 18px); font-weight: 900; color: var(--dr-dark); }
.panel-title p  { margin: 3px 0 0; color: var(--dr-muted); font-size: 12px; }

/* ── Filter form ── */
.filter-form {
    padding: clamp(14px, 2.5vw, 20px) clamp(16px, 2.5vw, 22px);
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box { position: relative; flex: 1; min-width: 200px; }

.search-box i {
    position: absolute;
    left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
}

.search-box input {
    width: 100%;
    border: 1.5px solid var(--dr-border);
    border-radius: 12px;
    padding: 11px 14px 11px 38px;
    font-size: 14px;
    outline: none;
    transition: border-color .2s;
    box-sizing: border-box;
}

.search-box input:focus { border-color: var(--dr-primary); box-shadow: 0 0 0 3px rgba(0,83,179,.08); }

/* ── Bulk bar ── */
.bulk-bar {
    padding: 0 clamp(16px, 2.5vw, 22px) 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.bulk-info { color: #64748b; font-size: 12px; font-weight: 700; }

/* ── Table wrapper ── */
.table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }

.users-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 700px;
}

.users-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .1em;
    font-weight: 800;
    text-align: left;
    padding: 12px 14px;
    border-bottom: 1px solid var(--dr-border);
    white-space: nowrap;
}

.users-table td {
    padding: 14px;
    border-bottom: 1px solid var(--dr-border);
    vertical-align: middle;
    color: #334155;
    font-size: 13px;
}

.users-table tr:last-child td { border-bottom: none; }
.users-table tbody tr:hover td { background: #fafbfc; }

/* ── User identity ── */
.user-identity {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 200px;
}

.avatar {
    width: 38px; height: 38px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #0053b3, #1d4ed8);
    color: white;
    font-weight: 900;
    font-size: 13px;
    flex-shrink: 0;
    text-transform: uppercase;
}

.avatar.superadmin-avatar {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.user-name  { color: var(--dr-dark); font-weight: 800; font-size: 13px; line-height: 1.2; }
.user-email { color: var(--dr-muted); font-size: 11px; margin-top: 2px; }
.user-phone { color: #94a3b8; font-size: 11px; margin-top: 1px; }
.user-id    { color: #cbd5e1; font-size: 10px; margin-top: 1px; }

/* ── Badges ── */
.badge-list { display: flex; flex-wrap: wrap; gap: 5px; }

.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 4px 8px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.badge-success { color: #166534; background: var(--dr-success-soft); }
.badge-danger  { color: #991b1b; background: var(--dr-danger-soft); }
.badge-info    { color: #1e40af; background: var(--dr-info-soft); }
.badge-warning { color: #92400e; background: var(--dr-warning-soft); }
.badge-gray    { color: #475569; background: #f1f5f9; }
.badge-gold    { color: #92400e; background: var(--dr-gold-soft); border: 1px solid rgba(245,158,11,.25); }

.plan-pill { text-transform: capitalize; }

/* ── Credits ── */
.credits {
    font-size: clamp(16px, 2.5vw, 20px);
    font-weight: 900;
    color: var(--dr-primary);
}

/* ── IP box ── */
.ip-box code {
    display: inline-block;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 4px 7px;
    border-radius: 8px;
    color: #334155;
    font-size: 11px;
}

.ip-box small { display: block; color: #94a3b8; margin-top: 4px; font-size: 11px; }

/* ── Action menu ── */
.actions-cell { text-align: right; }

.action-menu {
    position: relative;
    display: inline-flex;
    justify-content: flex-end;
}

.menu-trigger {
    width: 34px; height: 34px;
    border-radius: 10px;
    border: 1px solid var(--dr-border);
    background: white;
    color: #64748b;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: all .15s ease;
}

.menu-trigger:hover { background: #f1f5f9; border-color: #cbd5e1; }

.menu-dropdown {
    position: absolute;
    right: 0; top: 40px;
    width: 240px;
    background: white;
    border: 1px solid var(--dr-border);
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(15,23,42,.15);
    padding: 6px;
    z-index: 50;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-6px);
    transition: all .15s ease;
}

.action-menu.active .menu-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Dropdown sur mobile — vers le haut si en bas de page */
@media (max-width: 640px) {
    .menu-dropdown {
        right: 0;
        width: 220px;
    }
}

.menu-item {
    width: 100%;
    border: none;
    background: transparent;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    cursor: pointer;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    text-decoration: none;
    transition: all .15s ease;
}

.menu-item:hover     { background: #f1f5f9; color: var(--dr-primary); }
.menu-item.danger    { color: var(--dr-danger); }
.menu-item.danger:hover { background: var(--dr-danger-soft); }
.menu-item i         { width: 15px; text-align: center; flex-shrink: 0; }
.menu-divider        { height: 1px; background: var(--dr-border); margin: 5px 0; }

/* ── Empty state ── */
.empty-state { text-align: center; padding: 50px 20px; color: var(--dr-muted); }
.empty-state i { font-size: 34px; color: #cbd5e1; margin-bottom: 10px; display: block; }
.empty-state strong { display: block; font-size: 15px; color: #334155; font-weight: 900; }

/* ── Pagination ── */
.pagination-wrap {
    padding: 16px 22px;
    border-top: 1px solid var(--dr-border);
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
}

/* ── Modal ── */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 16px;
}

.modal-overlay.active { display: flex; }

.modal-card {
    width: min(500px, 100%);
    background: white;
    border-radius: 20px;
    box-shadow: 0 30px 90px rgba(15,23,42,.25);
    overflow: hidden;
    animation: modalIn .2s ease;
}

@keyframes modalIn {
    from { opacity: 0; transform: scale(.96) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header {
    padding: 18px 20px;
    border-bottom: 1px solid var(--dr-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.modal-header h3 { margin: 0; font-size: 16px; font-weight: 900; color: var(--dr-dark); }

.modal-close {
    width: 32px; height: 32px;
    border-radius: 10px;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: background .15s;
}
.modal-close:hover { background: #e2e8f0; }

.modal-body { padding: 20px; }
.modal-body p { margin: 0; color: #475569; line-height: 1.6; font-size: 14px; }
.modal-field { margin-top: 14px; }

.modal-field label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: #334155;
    margin-bottom: 6px;
}

.modal-field input {
    width: 100%;
    border: 1.5px solid var(--dr-border);
    border-radius: 12px;
    padding: 11px 13px;
    outline: none;
    font-size: 14px;
    box-sizing: border-box;
    transition: border-color .2s;
}
.modal-field input:focus { border-color: var(--dr-primary); }

.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--dr-border);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

/* ── Checkbox style ── */
input[type="checkbox"] {
    width: 15px; height: 15px;
    cursor: pointer;
    accent-color: var(--dr-primary);
}

/* ── Responsive breakpoints ── */
@media (max-width: 1100px) {
    .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .users-hero { flex-direction: column; }
    .hero-actions { justify-content: flex-start; width: 100%; }
    .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .panel-header { flex-direction: column; align-items: flex-start; }
    .panel-header .dr-btn { align-self: flex-start; }
    .bulk-bar { flex-direction: column; align-items: flex-start; }
}

@media (max-width: 480px) {
    .kpi-grid { grid-template-columns: 1fr 1fr; }
    .filter-form { flex-direction: column; }
    .filter-form .dr-btn { width: 100%; }
    .hero-actions .dr-btn { flex: 1; }
    .dr-btn { padding: 10px 12px; font-size: 12px; }
}

@media (max-width: 360px) {
    .kpi-grid { grid-template-columns: 1fr; }
}
</style>

@php
    $usersCollection   = $users->getCollection();
    $totalOnPage       = $usersCollection->count();
    $activeOnPage      = $usersCollection->where('is_active', true)->count();
    $adminsOnPage      = $usersCollection->where('is_admin', true)->count();
    $verifiedOnPage    = $usersCollection->filter(fn($u) => !is_null($u->email_verified_at))->count();
    $totalCreditsOnPage= $usersCollection->sum('credits');
    $superAdminsOnPage = $usersCollection->where('is_superadmin', true)->count();
@endphp

<div class="users-page">
<div class="users-container">

    {{-- ── Hero ── --}}
    <section class="users-hero">
        <div style="min-width:0;">
            <div class="hero-kicker">
                <i class="fa-solid fa-shield-halved"></i>
                Centre de sécurité
            </div>
            <h1>Gestion des utilisateurs</h1>
            <p>Supervisez les comptes, crédits, droits admin, vérifications email, OTP et suspensions depuis un espace unique.</p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('admin.security.users.create') }}" class="dr-btn dr-btn-white">
                <i class="fa-solid fa-user-plus"></i>
                <span>Créer un utilisateur</span>
            </a>
            <a href="{{ route('admin.security.blocked.index') }}" class="dr-btn dr-btn-soft">
                <i class="fa-solid fa-ban"></i>
                <span>Identités bloquées</span>
            </a>
        </div>
    </section>

    {{-- ── Alerts ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- ── KPIs ── --}}
    <section class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-label">Utilisateurs</div>
            <div class="kpi-value">{{ $totalOnPage }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-user-check"></i></div>
            <div class="kpi-label">Actifs</div>
            <div class="kpi-value">{{ $activeOnPage }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-user-shield"></i></div>
            <div class="kpi-label">Admins</div>
            <div class="kpi-value">{{ $adminsOnPage }}</div>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fffbeb;color:#f59e0b;"><i class="fa-solid fa-crown"></i></div>
            <div class="kpi-label">Superadmins</div>
            <div class="kpi-value" style="color:#f59e0b;">{{ $superAdminsOnPage }}</div>
        </div>
    @endif
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-coins"></i></div>
            <div class="kpi-label">Crédits total</div>
            <div class="kpi-value">{{ number_format($totalCreditsOnPage, 0, ',', ' ') }}</div>
        </div>
    </section>

    {{-- ── Recherche ── --}}
    <section class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div>
                    <h2>Recherche</h2>
                    <p>Par nom, email ou téléphone.</p>
                </div>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.security.users.index') }}" class="filter-form">
            <div class="search-box">
                <i class="fa-solid fa-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un utilisateur...">
            </div>
            <button type="submit" class="dr-btn dr-btn-primary">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            <a href="{{ route('admin.security.users.index') }}" class="dr-btn dr-btn-soft">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </a>
        </form>
    </section>

    {{-- ── Table ── --}}
    <section class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-icon"><i class="fa-solid fa-list-check"></i></div>
                <div>
                    <h2>Comptes utilisateurs</h2>
                    <p>{{ $users->total() }} utilisateur(s) au total.</p>
                </div>
            </div>
            <a href="{{ route('admin.security.users.create') }}" class="dr-btn dr-btn-primary dr-btn-sm">
                <i class="fa-solid fa-plus"></i> Nouveau
            </a>
        </div>

        <form method="POST" action="{{ route('admin.security.users.bulkDelete') }}" id="bulkDeleteForm">
            @csrf
            @method('DELETE')
        </form>

        <div class="bulk-bar">
            <div class="bulk-info">Cochez des utilisateurs pour les supprimer en masse.</div>
            <button type="submit" form="bulkDeleteForm" class="dr-btn dr-btn-danger dr-btn-sm"
                    onclick="return confirm('Supprimer les utilisateurs sélectionnés ?');">
                <i class="fa-regular fa-trash-can"></i> Supprimer la sélection
            </button>
        </div>

        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="checkAllUsers"></th>
                        <th>Utilisateur</th>
                        <th>Statuts</th>
                        <th>Crédits</th>
                        <th>Plan</th>
                        <th>Connexion</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    @php
                        $initials  = collect(explode(' ', trim($user->name ?? '')))
                                        ->filter()->take(2)
                                        ->map(fn($p) => mb_substr($p, 0, 1))
                                        ->implode('');
                        $safeName  = addslashes($user->name ?? 'Utilisateur');
                        $safeIp    = addslashes($user->last_login_ip ?? '');
                        $isSuperAdmin = $user->isSuperAdmin();
                    @endphp
                    <tr>
                        <td>
                            @if($user->id !== auth()->id() && !$isSuperAdmin)
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                       class="user-checkbox" form="bulkDeleteForm">
                            @endif
                        </td>

                        <td>
                            <div class="user-identity">
                                <div class="avatar {{ $isSuperAdmin ? 'superadmin-avatar' : '' }}">
                                    {{ $isSuperAdmin ? '👑' : ($initials ?: 'U') }}
                                </div>
                                <div>
                                    <div class="user-name">
                                        {{ $user->name ?? '-' }}
                                        @if($isSuperAdmin)
                                            <span class="badge badge-gold" style="font-size:9px;padding:2px 6px;margin-left:4px;">SUPER</span>
                                        @endif
                                    </div>
                                    <div class="user-email">{{ $user->email ?? '-' }}</div>
                                    <div class="user-phone">{{ $user->phone ?? '—' }}</div>
                                    <div class="user-id">ID #{{ $user->id }}</div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="badge-list">
                                @if($isSuperAdmin)
                                    <span class="badge badge-gold"><i class="fa-solid fa-crown"></i> Superadmin</span>
                                @elseif($user->is_admin ?? false)
                                    <span class="badge badge-info"><i class="fa-solid fa-user-shield"></i> Admin</span>
                                @endif

                                @if($user->is_active ?? false)
                                    <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Actif</span>
                                @else
                                    <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Suspendu</span>
                                @endif

                                @if($user->email_verified_at)
                                    <span class="badge badge-success"><i class="fa-solid fa-envelope-circle-check"></i> Email ✓</span>
                                @else
                                    <span class="badge badge-gray"><i class="fa-solid fa-envelope"></i> Email ?</span>
                                @endif

                                @if($user->otp_bypass ?? false)
                                    <span class="badge badge-warning"><i class="fa-solid fa-unlock-keyhole"></i> OTP bypass</span>
                                @endif
                            </div>
                        </td>

                        <td>
                            <div class="credits">{{ number_format($user->credits ?? 0, 0, ',', ' ') }}</div>
                        </td>

                        <td>
                            <span class="badge plan-pill {{ in_array($user->plan ?? 'free', ['premium','enterprise']) ? 'badge-info' : 'badge-gray' }}">
                                @if(($user->plan ?? 'free') === 'enterprise')
                                    <i class="fa-solid fa-star"></i>
                                @elseif(($user->plan ?? 'free') === 'premium')
                                    <i class="fa-solid fa-bolt"></i>
                                @else
                                    <i class="fa-solid fa-user"></i>
                                @endif
                                {{ $user->plan ?? 'free' }}
                            </span>
                        </td>

                        <td>
                            <div class="ip-box">
                                <code>{{ $user->last_login_ip ?? '—' }}</code>
                                <small>{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? 'Jamais connecté' }}</small>
                            </div>
                        </td>

                        <td class="actions-cell">
                            <div class="action-menu">
                                <button type="button" class="menu-trigger" onclick="toggleMenu(this)">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div class="menu-dropdown">
                                    <a class="menu-item" href="{{ route('admin.security.users.edit', $user) }}">
                                        <i class="fa-solid fa-pen-to-square"></i> Modifier
                                    </a>
                                    <button type="button" class="menu-item" onclick="openActionModal('credits_add', {{ $user->id }}, '{{ $safeName }}')">
                                        <i class="fa-solid fa-circle-plus"></i> Ajouter des crédits
                                    </button>
                                    <button type="button" class="menu-item" onclick="openActionModal('credits_remove', {{ $user->id }}, '{{ $safeName }}')">
                                        <i class="fa-solid fa-circle-minus"></i> Retirer des crédits
                                    </button>

                                    <div class="menu-divider"></div>

                                    @if(!$isSuperAdmin)
                                        <button type="button" class="menu-item" onclick="openActionModal('status', {{ $user->id }}, '{{ $safeName }}', '{{ ($user->is_active ?? false) ? 'suspendre' : 'reactiver' }}')">
                                            <i class="fa-solid {{ ($user->is_active ?? false) ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                            {{ ($user->is_active ?? false) ? 'Suspendre' : 'Réactiver' }}
                                        </button>
                                    @endif

                                    @if(!($user->is_admin ?? false))
                                        <button type="button" class="menu-item" onclick="openActionModal('make_admin', {{ $user->id }}, '{{ $safeName }}')">
                                            <i class="fa-solid fa-user-shield"></i> Nommer admin
                                        </button>
                                    @elseif(!$isSuperAdmin)
                                        <button type="button" class="menu-item" onclick="openActionModal('remove_admin', {{ $user->id }}, '{{ $safeName }}')">
                                            <i class="fa-solid fa-user-minus"></i> Retirer droits admin
                                        </button>
                                    @endif

                                    {{-- Superadmin actions — visible uniquement pour superadmin connecté --}}
                                    @if(auth()->user()->isSuperAdmin())
                                        @if(!$isSuperAdmin)
                                            <button type="button" class="menu-item" onclick="openActionModal('make_superadmin', {{ $user->id }}, '{{ $safeName }}')" style="color:#f59e0b;">
                                                <i class="fa-solid fa-crown"></i> Nommer superadmin
                                            </button>
                                        @elseif($user->id !== auth()->id())
                                            <button type="button" class="menu-item" onclick="openActionModal('remove_superadmin', {{ $user->id }}, '{{ $safeName }}')">
                                                <i class="fa-solid fa-crown" style="opacity:.5;"></i> Rétrograder superadmin
                                            </button>
                                        @endif
                                    @endif

                                    <button type="button" class="menu-item" onclick="openActionModal('verify_email', {{ $user->id }}, '{{ $safeName }}')">
                                        <i class="fa-solid fa-envelope-circle-check"></i> Vérifier l'email
                                    </button>
                                    <button type="button" class="menu-item" onclick="openActionModal('otp_bypass', {{ $user->id }}, '{{ $safeName }}', '{{ ($user->otp_bypass ?? false) ? 'desactiver' : 'activer' }}')">
                                        <i class="fa-solid fa-key"></i>
                                        {{ ($user->otp_bypass ?? false) ? 'Désactiver OTP bypass' : 'Activer OTP bypass' }}
                                    </button>

                                    @if(!$isSuperAdmin)
                                        <div class="menu-divider"></div>
                                        <button type="button" class="menu-item danger" onclick="openActionModal('ban', {{ $user->id }}, '{{ $safeName }}')">
                                            <i class="fa-solid fa-ban"></i> Bannir
                                        </button>
                                        @if($user->last_login_ip ?? false)
                                            <button type="button" class="menu-item danger" onclick="openActionModal('ban_ip', {{ $user->id }}, '{{ $safeName }}', '{{ $safeIp }}')">
                                                <i class="fa-solid fa-globe"></i> Bloquer l'IP
                                            </button>
                                        @endif
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.security.users.destroy', $user) }}"
                                                  onsubmit="return confirm('Supprimer définitivement ?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="menu-item danger">
                                                    <i class="fa-regular fa-trash-can"></i> Supprimer
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa-solid fa-user-slash"></i>
                                <strong>Aucun utilisateur trouvé</strong>
                                <span style="margin-top:6px;display:block;">Essayez une autre recherche ou créez un nouveau compte.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $users->onEachSide(1)->links() }}
        </div>
    </section>

</div>
</div>

{{-- ── Modal ── --}}
<div id="actionModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Action utilisateur</h3>
            <button type="button" class="modal-close" onclick="closeModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="actionForm" method="POST" action="">
            @csrf
            <div class="modal-body">
                <p id="modalMessage"></p>
                <div id="modalExtraFields" class="modal-field" style="display:none;">
                    <label id="extraLabel" for="extraValue"></label>
                    <input type="number" id="extraValue" name="amount" min="1" value="10">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="dr-btn dr-btn-soft" onclick="closeModal()">Annuler</button>
                <button type="submit" class="dr-btn dr-btn-primary" id="modalConfirmBtn">Confirmer</button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE_URL = "{{ url('/admin/security/users') }}";
const ROUTES = {
    giveCredits:   "{{ route('admin.security.users.giveCredits') }}",
    removeCredits: "{{ route('admin.security.users.removeCredits') }}",
    blockedStore:  "{{ route('admin.security.blocked.store') }}"
};

const modal          = document.getElementById('actionModal');
const modalTitle     = document.getElementById('modalTitle');
const modalMessage   = document.getElementById('modalMessage');
const actionForm     = document.getElementById('actionForm');
const modalExtras    = document.getElementById('modalExtraFields');
const extraLabel     = document.getElementById('extraLabel');
const extraValue     = document.getElementById('extraValue');
const modalConfirmBtn= document.getElementById('modalConfirmBtn');

// ── Menu toggle ──
function toggleMenu(btn) {
    const cur = btn.closest('.action-menu');
    document.querySelectorAll('.action-menu').forEach(m => { if (m !== cur) m.classList.remove('active'); });
    cur.classList.toggle('active');
}

document.addEventListener('click', e => {
    if (!e.target.closest('.action-menu'))
        document.querySelectorAll('.action-menu').forEach(m => m.classList.remove('active'));
});

// ── Modal helpers ──
function addHidden(name, value) {
    const i = document.createElement('input');
    i.type = 'hidden'; i.name = name; i.value = value;
    actionForm.appendChild(i);
}

function clearHidden() {
    actionForm.querySelectorAll('input[type="hidden"]:not([name="_token"])').forEach(i => i.remove());
}

function resetModal() {
    modalExtras.style.display = 'none';
    modalConfirmBtn.className = 'dr-btn dr-btn-primary';
    modalConfirmBtn.innerText = 'Confirmer';
    clearHidden();
}

function openActionModal(action, userId, userName, extra = null) {
    resetModal();
    document.querySelectorAll('.action-menu').forEach(m => m.classList.remove('active'));

    switch (action) {
        case 'credits_add':
            actionForm.action = ROUTES.giveCredits;
            modalTitle.innerText = 'Ajouter des crédits';
            modalMessage.innerHTML = `Nombre de crédits à ajouter à <strong>${userName}</strong>.`;
            modalExtras.style.display = 'block';
            extraLabel.innerText = 'Montant';
            extraValue.value = 10;
            addHidden('user_id', userId);
            break;

        case 'credits_remove':
            actionForm.action = ROUTES.removeCredits;
            modalTitle.innerText = 'Retirer des crédits';
            modalMessage.innerHTML = `Nombre de crédits à retirer à <strong>${userName}</strong>.`;
            modalExtras.style.display = 'block';
            extraLabel.innerText = 'Montant';
            extraValue.value = 10;
            addHidden('user_id', userId);
            break;

        case 'status':
            actionForm.action = BASE_URL + '/' + userId + '/toggle-active';
            modalTitle.innerText = extra === 'suspendre' ? 'Suspendre le compte' : 'Réactiver le compte';
            modalMessage.innerHTML = `Voulez-vous <strong>${extra === 'suspendre' ? 'suspendre' : 'réactiver'}</strong> le compte de <strong>${userName}</strong> ?`;
            modalConfirmBtn.className = 'dr-btn dr-btn-warning';
            break;

        case 'make_admin':
            actionForm.action = BASE_URL + '/' + userId + '/make-admin';
            modalTitle.innerText = 'Nommer administrateur';
            modalMessage.innerHTML = `Donner les droits admin à <strong>${userName}</strong> ?`;
            break;

        case 'remove_admin':
            actionForm.action = BASE_URL + '/' + userId + '/remove-admin';
            modalTitle.innerText = 'Retirer les droits admin';
            modalMessage.innerHTML = `Retirer les droits admin à <strong>${userName}</strong> ?`;
            modalConfirmBtn.className = 'dr-btn dr-btn-warning';
            break;

        case 'make_superadmin':
            actionForm.action = BASE_URL + '/' + userId + '/make-superadmin';
            modalTitle.innerText = '👑 Nommer superadmin';
            modalMessage.innerHTML = `Promouvoir <strong>${userName}</strong> en superadmin ? Cette action donne un accès total à l'application.`;
            modalConfirmBtn.className = 'dr-btn dr-btn-warning';
            modalConfirmBtn.innerText = 'Promouvoir';
            break;

        case 'remove_superadmin':
            actionForm.action = BASE_URL + '/' + userId + '/remove-superadmin';
            modalTitle.innerText = 'Rétrograder superadmin';
            modalMessage.innerHTML = `Retirer le statut superadmin à <strong>${userName}</strong> ?`;
            modalConfirmBtn.className = 'dr-btn dr-btn-warning';
            break;

        case 'verify_email':
            actionForm.action = BASE_URL + '/' + userId + '/verify-email';
            modalTitle.innerText = 'Vérifier l\'email';
            modalMessage.innerHTML = `Marquer l'email de <strong>${userName}</strong> comme vérifié ?`;
            break;

        case 'otp_bypass':
            actionForm.action = BASE_URL + '/' + userId + '/toggle-otp-bypass';
            modalTitle.innerText = extra === 'activer' ? 'Activer OTP bypass' : 'Désactiver OTP bypass';
            modalMessage.innerHTML = `<strong>${extra === 'activer' ? 'Activer' : 'Désactiver'}</strong> le laissez-passer OTP pour <strong>${userName}</strong> ?`;
            break;

        case 'ban':
            actionForm.action = BASE_URL + '/' + userId + '/ban';
            modalTitle.innerText = 'Bannir l\'utilisateur';
            modalMessage.innerHTML = `Suspendre et bloquer définitivement <strong>${userName}</strong> ?`;
            modalConfirmBtn.className = 'dr-btn dr-btn-danger';
            modalConfirmBtn.innerText = 'Bannir';
            break;

        case 'ban_ip':
            actionForm.action = ROUTES.blockedStore;
            modalTitle.innerText = 'Bloquer l\'IP';
            modalMessage.innerHTML = `Bloquer l'IP <strong>${extra}</strong> de <strong>${userName}</strong> ?`;
            modalConfirmBtn.className = 'dr-btn dr-btn-danger';
            modalConfirmBtn.innerText = 'Bloquer';
            addHidden('type', 'ip');
            addHidden('value', extra);
            addHidden('reason', 'IP bannie depuis l\'interface admin');
            break;

        default: return;
    }

    modal.classList.add('active');
}

function closeModal() {
    modal.classList.remove('active');
    actionForm.action = '';
    resetModal();
}

modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── Check all ──
const checkAll = document.getElementById('checkAllUsers');
if (checkAll) {
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = this.checked);
    });
}
</script>

@endsection
