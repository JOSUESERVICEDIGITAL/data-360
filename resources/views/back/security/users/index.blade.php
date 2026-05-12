@extends('back.layouts.app')

@section('title', 'Gestion des utilisateurs | Data Rocket')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    :root {
        --dr-primary:#0053b3;
        --dr-primary-dark:#003d85;
        --dr-primary-soft:#e6f0ff;
        --dr-success:#15803d;
        --dr-success-soft:#dcfce7;
        --dr-danger:#b91c1c;
        --dr-danger-soft:#fee2e2;
        --dr-warning:#b45309;
        --dr-warning-soft:#fff7ed;
        --dr-info:#1d4ed8;
        --dr-info-soft:#dbeafe;
        --dr-dark:#0f172a;
        --dr-muted:#64748b;
        --dr-border:#e2e8f0;
        --dr-bg:#f8fafc;
        --dr-card:#ffffff;
    }

    .users-page {
        min-height: 100vh;
        padding: 28px;
        background:
            radial-gradient(circle at top left, rgba(0,83,179,.08), transparent 32%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    }

    .users-container {
        max-width: 1320px;
        margin: 0 auto;
    }

    .users-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 62%, #0053b3 100%);
        color: white;
        border-radius: 30px;
        padding: 30px;
        box-shadow: 0 24px 70px rgba(15,23,42,.20);
        display: flex;
        justify-content: space-between;
        gap: 22px;
        align-items: flex-start;
        margin-bottom: 22px;
        overflow: hidden;
        position: relative;
    }

    .users-hero::after {
        content: "";
        position: absolute;
        right: -90px;
        top: -120px;
        width: 300px;
        height: 300px;
        border-radius: 999px;
        background: rgba(255,255,255,.10);
    }

    .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid rgba(255,255,255,.18);
        background: rgba(255,255,255,.12);
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 14px;
    }

    .users-hero h1 {
        margin: 0;
        font-size: clamp(28px, 4vw, 42px);
        font-weight: 950;
        letter-spacing: -.03em;
        line-height: 1.05;
    }

    .users-hero p {
        margin: 12px 0 0;
        color: rgba(255,255,255,.78);
        line-height: 1.65;
        max-width: 760px;
    }

    .hero-actions {
        position: relative;
        z-index: 2;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .dr-btn {
        border: none;
        border-radius: 14px;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 900;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: .2s ease;
        white-space: nowrap;
    }

    .dr-btn-primary {
        background: var(--dr-primary);
        color: white;
    }

    .dr-btn-primary:hover {
        background: var(--dr-primary-dark);
        color: white;
        transform: translateY(-1px);
    }

    .dr-btn-white {
        background: white;
        color: var(--dr-primary);
    }

    .dr-btn-white:hover {
        background: #eff6ff;
        color: var(--dr-primary-dark);
    }

    .dr-btn-soft {
        background: #f1f5f9;
        color: #334155;
    }

    .dr-btn-soft:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .dr-btn-danger {
        background: var(--dr-danger);
        color: white;
    }

    .dr-btn-danger:hover {
        background: #991b1b;
        color: white;
    }

    .dr-btn-warning {
        background: var(--dr-warning);
        color: white;
    }

    .dr-btn-warning:hover {
        background: #92400e;
        color: white;
    }

    .dr-btn-sm {
        padding: 9px 12px;
        font-size: 12px;
        border-radius: 12px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .kpi-card {
        background: white;
        border: 1px solid var(--dr-border);
        border-radius: 22px;
        padding: 18px;
        box-shadow: 0 12px 35px rgba(15,23,42,.045);
    }

    .kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: var(--dr-primary-soft);
        color: var(--dr-primary);
        margin-bottom: 12px;
    }

    .kpi-label {
        color: var(--dr-muted);
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 7px;
    }

    .kpi-value {
        color: var(--dr-dark);
        font-size: 24px;
        font-weight: 950;
    }

    .panel {
        background: white;
        border: 1px solid var(--dr-border);
        border-radius: 24px;
        box-shadow: 0 12px 35px rgba(15,23,42,.045);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .panel-header {
        padding: 20px 22px;
        border-bottom: 1px solid var(--dr-border);
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
    }

    .panel-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .panel-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: var(--dr-primary-soft);
        color: var(--dr-primary);
    }

    .panel-title h2 {
        margin: 0;
        font-size: 19px;
        font-weight: 950;
        color: var(--dr-dark);
    }

    .panel-title p {
        margin: 4px 0 0;
        color: var(--dr-muted);
        font-size: 13px;
    }

    .filter-form {
        padding: 20px 22px;
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 12px;
        align-items: center;
    }

    .search-box {
        position: relative;
    }

    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-box input {
        width: 100%;
        border: 1.5px solid var(--dr-border);
        border-radius: 15px;
        padding: 13px 14px 13px 42px;
        font-size: 14px;
        outline: none;
        transition: .2s ease;
    }

    .search-box input:focus {
        border-color: var(--dr-primary);
        box-shadow: 0 0 0 4px rgba(0,83,179,.10);
    }

    .alert {
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 18px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-weight: 800;
    }

    .alert-success {
        background: var(--dr-success-soft);
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-error {
        background: var(--dr-danger-soft);
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .users-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .users-table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 950;
        text-align: left;
        padding: 14px 16px;
        border-bottom: 1px solid var(--dr-border);
        white-space: nowrap;
    }

    .users-table td {
        padding: 16px;
        border-bottom: 1px solid var(--dr-border);
        vertical-align: middle;
        color: #334155;
        font-size: 14px;
    }

    .users-table tr:hover td {
        background: #f8fafc;
    }

    .user-identity {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 260px;
    }

    .avatar {
        width: 44px;
        height: 44px;
        border-radius: 15px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #0053b3, #1d4ed8);
        color: white;
        font-weight: 950;
        flex: 0 0 auto;
        text-transform: uppercase;
    }

    .user-name {
        color: var(--dr-dark);
        font-weight: 950;
        line-height: 1.2;
    }

    .user-email,
    .user-phone,
    .user-id {
        color: var(--dr-muted);
        font-size: 12px;
        margin-top: 3px;
    }

    .badge-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        min-width: 210px;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 999px;
        padding: 6px 9px;
        font-size: 11px;
        font-weight: 900;
        white-space: nowrap;
    }

    .badge-success {
        color: #166534;
        background: var(--dr-success-soft);
    }

    .badge-danger {
        color: #991b1b;
        background: var(--dr-danger-soft);
    }

    .badge-info {
        color: #1e40af;
        background: var(--dr-info-soft);
    }

    .badge-warning {
        color: #92400e;
        background: var(--dr-warning-soft);
    }

    .badge-gray {
        color: #475569;
        background: #f1f5f9;
    }

    .credits {
        font-size: 22px;
        font-weight: 950;
        color: var(--dr-primary);
    }

    .plan-pill {
        text-transform: capitalize;
    }

    .ip-box code {
        display: inline-block;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 6px 8px;
        border-radius: 10px;
        color: #334155;
        font-size: 12px;
    }

    .ip-box small {
        display: block;
        color: #94a3b8;
        margin-top: 6px;
        font-size: 12px;
    }

    .actions-cell {
        text-align: right;
    }

    .action-menu {
        position: relative;
        display: inline-flex;
        justify-content: flex-end;
    }

    .menu-trigger {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        border: 1px solid var(--dr-border);
        background: white;
        color: #64748b;
        cursor: pointer;
        transition: .2s ease;
    }

    .menu-trigger:hover {
        border-color: var(--dr-primary);
        color: var(--dr-primary);
        background: #f8fafc;
    }

    .menu-dropdown {
        position: absolute;
        right: 0;
        top: 44px;
        width: 250px;
        background: white;
        border: 1px solid var(--dr-border);
        border-radius: 16px;
        box-shadow: 0 24px 70px rgba(15,23,42,.18);
        padding: 8px;
        z-index: 50;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: .2s ease;
    }

    .action-menu.active .menu-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .menu-item {
        width: 100%;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 12px;
        cursor: pointer;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        text-align: left;
        text-decoration: none;
    }

    .menu-item:hover {
        background: #f1f5f9;
        color: var(--dr-primary);
    }

    .menu-item.danger {
        color: var(--dr-danger);
    }

    .menu-item.danger:hover {
        background: var(--dr-danger-soft);
    }

    .menu-divider {
        height: 1px;
        background: var(--dr-border);
        margin: 6px 0;
    }

    .empty-state {
        text-align: center;
        padding: 55px 20px;
        color: var(--dr-muted);
    }

    .empty-state i {
        font-size: 38px;
        color: #cbd5e1;
        margin-bottom: 12px;
    }

    .pagination-wrap {
        padding: 18px 22px;
        border-top: 1px solid var(--dr-border);
        display: flex;
        justify-content: center;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.55);
        backdrop-filter: blur(5px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 20px;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-card {
        width: min(520px, 100%);
        background: white;
        border-radius: 24px;
        box-shadow: 0 30px 90px rgba(15,23,42,.30);
        overflow: hidden;
        animation: modalIn .2s ease;
    }

    @keyframes modalIn {
        from { opacity: 0; transform: translateY(10px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-header {
        padding: 20px 22px;
        border-bottom: 1px solid var(--dr-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 950;
        color: var(--dr-dark);
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        border: none;
        background: #f1f5f9;
        color: #64748b;
        cursor: pointer;
    }

    .modal-body {
        padding: 22px;
    }

    .modal-body p {
        margin: 0;
        color: #475569;
        line-height: 1.6;
    }

    .modal-field {
        margin-top: 16px;
    }

    .modal-field label {
        display: block;
        font-size: 13px;
        font-weight: 900;
        color: #334155;
        margin-bottom: 7px;
    }

    .modal-field input {
        width: 100%;
        border: 1.5px solid var(--dr-border);
        border-radius: 14px;
        padding: 12px 14px;
        outline: none;
    }

    .modal-footer {
        padding: 18px 22px;
        border-top: 1px solid var(--dr-border);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 980px) {
        .users-hero {
            flex-direction: column;
        }

        .hero-actions {
            justify-content: flex-start;
        }

        .kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .filter-form {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .users-page {
            padding: 16px;
        }

        .users-hero,
        .panel {
            border-radius: 22px;
        }

        .kpi-grid {
            grid-template-columns: 1fr;
        }

        .users-table th,
        .users-table td {
            padding: 13px 12px;
        }
    }
</style>

@php
    $usersCollection = $users->getCollection();

    $totalOnPage = $usersCollection->count();
    $activeOnPage = $usersCollection->where('is_active', true)->count();
    $adminsOnPage = $usersCollection->where('is_admin', true)->count();
    $verifiedOnPage = $usersCollection->filter(fn($u) => !is_null($u->email_verified_at))->count();
    $totalCreditsOnPage = $usersCollection->sum('credits');
@endphp

<div class="users-page">
    <div class="users-container">

        <section class="users-hero">
            <div>
                <div class="hero-kicker">
                    <i class="fa-solid fa-shield-halved"></i>
                    Centre de sécurité
                </div>

                <h1>Gestion des utilisateurs</h1>

                <p>
                    Supervisez les comptes, crédits, droits administrateur, vérifications email,
                    laissez-passer OTP, suspensions et identités bloquées depuis un espace unique.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('admin.security.users.create') }}" class="dr-btn dr-btn-white">
                    <i class="fa-solid fa-user-plus"></i>
                    Créer un utilisateur
                </a>

                <a href="{{ route('admin.security.blocked.index') }}" class="dr-btn dr-btn-soft">
                    <i class="fa-solid fa-ban"></i>
                    Identités bloquées
                </a>
            </div>
        </section>

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

        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
                <div class="kpi-label">Utilisateurs page</div>
                <div class="kpi-value">{{ $totalOnPage }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fa-solid fa-user-check"></i></div>
                <div class="kpi-label">Comptes actifs</div>
                <div class="kpi-value">{{ $activeOnPage }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fa-solid fa-user-shield"></i></div>
                <div class="kpi-label">Administrateurs</div>
                <div class="kpi-value">{{ $adminsOnPage }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
                <div class="kpi-label">Emails vérifiés</div>
                <div class="kpi-value">{{ $verifiedOnPage }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="fa-solid fa-coins"></i></div>
                <div class="kpi-label">Crédits page</div>
                <div class="kpi-value">{{ number_format($totalCreditsOnPage, 0, ',', ' ') }}</div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div class="panel-title">
                    <div class="panel-title-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <div>
                        <h2>Recherche et filtrage</h2>
                        <p>Rechercher par nom, email ou téléphone.</p>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.security.users.index') }}" class="filter-form">
                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Rechercher un utilisateur, email ou téléphone..."
                    >
                </div>

                <button type="submit" class="dr-btn dr-btn-primary">
                    <i class="fa-solid fa-filter"></i>
                    Rechercher
                </button>

                <a href="{{ route('admin.security.users.index') }}" class="dr-btn dr-btn-soft">
                    <i class="fa-solid fa-rotate-left"></i>
                    Réinitialiser
                </a>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div class="panel-title">
                    <div class="panel-title-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <h2>Comptes utilisateurs</h2>
                        <p>{{ $users->total() }} utilisateur(s) au total.</p>
                    </div>
                </div>

                <a href="{{ route('admin.security.users.create') }}" class="dr-btn dr-btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Nouveau compte
                </a>
            </div>

            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
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
                                $initials = collect(explode(' ', trim($user->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn($part) => mb_substr($part, 0, 1))
                                    ->implode('');

                                $safeName = e($user->name);
                                $safeIp = e($user->last_login_ip ?? '');
                            @endphp

                            <tr>
                                <td>
                                    <div class="user-identity">
                                        <div class="avatar">{{ $initials ?: 'U' }}</div>

                                        <div>
                                            <div class="user-name">{{ $user->name }}</div>
                                            <div class="user-email">{{ $user->email }}</div>
                                            <div class="user-phone">{{ $user->phone ?? 'Téléphone non renseigné' }}</div>
                                            <div class="user-id">ID #{{ $user->id }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="badge-list">
                                        @if($user->is_admin)
                                            <span class="badge badge-info">
                                                <i class="fa-solid fa-user-shield"></i>
                                                Admin
                                            </span>
                                        @endif

                                        @if($user->is_active)
                                            <span class="badge badge-success">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Actif
                                            </span>
                                        @else
                                            <span class="badge badge-danger">
                                                <i class="fa-solid fa-circle-xmark"></i>
                                                Suspendu
                                            </span>
                                        @endif

                                        @if($user->email_verified_at)
                                            <span class="badge badge-success">
                                                <i class="fa-solid fa-envelope-circle-check"></i>
                                                Email vérifié
                                            </span>
                                        @else
                                            <span class="badge badge-gray">
                                                <i class="fa-solid fa-envelope"></i>
                                                Email non vérifié
                                            </span>
                                        @endif

                                        @if($user->otp_bypass)
                                            <span class="badge badge-warning">
                                                <i class="fa-solid fa-unlock-keyhole"></i>
                                                OTP bypass
                                            </span>
                                        @endif

                                        @if($user->phone_verified_at)
                                            <span class="badge badge-success">
                                                <i class="fa-solid fa-mobile-screen-button"></i>
                                                Téléphone vérifié
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="credits">{{ number_format($user->credits ?? 0, 0, ',', ' ') }}</div>
                                </td>

                                <td>
                                    <span class="badge plan-pill {{ $user->plan === 'premium' || $user->plan === 'enterprise' ? 'badge-info' : 'badge-gray' }}">
                                        <i class="fa-solid fa-layer-group"></i>
                                        {{ $user->plan ?? 'free' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="ip-box">
                                        <code>{{ $user->last_login_ip ?? '-' }}</code>
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
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                Modifier
                                            </a>

                                            <button class="menu-item" onclick="openActionModal('credits_add', {{ $user->id }}, '{{ $safeName }}')">
                                                <i class="fa-solid fa-circle-plus"></i>
                                                Ajouter des crédits
                                            </button>

                                            <button class="menu-item" onclick="openActionModal('credits_remove', {{ $user->id }}, '{{ $safeName }}')">
                                                <i class="fa-solid fa-circle-minus"></i>
                                                Retirer des crédits
                                            </button>

                                            <div class="menu-divider"></div>

                                            <button class="menu-item" onclick="openActionModal('status', {{ $user->id }}, '{{ $safeName }}', '{{ $user->is_active ? 'suspendre' : 'reactiver' }}')">
                                                <i class="fa-solid {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                {{ $user->is_active ? 'Suspendre le compte' : 'Réactiver le compte' }}
                                            </button>

                                            @if(!$user->is_admin)
                                                <button class="menu-item" onclick="openActionModal('make_admin', {{ $user->id }}, '{{ $safeName }}')">
                                                    <i class="fa-solid fa-user-shield"></i>
                                                    Nommer administrateur
                                                </button>
                                            @else
                                                <button class="menu-item" onclick="openActionModal('remove_admin', {{ $user->id }}, '{{ $safeName }}')">
                                                    <i class="fa-solid fa-user-minus"></i>
                                                    Retirer les droits admin
                                                </button>
                                            @endif

                                            <button class="menu-item" onclick="openActionModal('verify_email', {{ $user->id }}, '{{ $safeName }}')">
                                                <i class="fa-solid fa-envelope-circle-check"></i>
                                                Vérifier l’email
                                            </button>

                                            <button class="menu-item" onclick="openActionModal('otp_bypass', {{ $user->id }}, '{{ $safeName }}', '{{ $user->otp_bypass ? 'desactiver' : 'activer' }}')">
                                                <i class="fa-solid fa-key"></i>
                                                {{ $user->otp_bypass ? 'Désactiver OTP bypass' : 'Activer OTP bypass' }}
                                            </button>

                                            <div class="menu-divider"></div>

                                            <button class="menu-item danger" onclick="openActionModal('ban', {{ $user->id }}, '{{ $safeName }}')">
                                                <i class="fa-solid fa-ban"></i>
                                                Bannir l’utilisateur
                                            </button>

                                            @if($user->last_login_ip)
                                                <button class="menu-item danger" onclick="openActionModal('ban_ip', {{ $user->id }}, '{{ $safeName }}', '{{ $safeIp }}')">
                                                    <i class="fa-solid fa-globe"></i>
                                                    Bloquer l’IP
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-user-slash"></i>
                                        <div style="font-weight:950;color:#334155;">Aucun utilisateur trouvé</div>
                                        <div style="margin-top:6px;">Essayez une autre recherche ou créez un nouveau compte.</div>
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
                <button type="button" class="dr-btn dr-btn-soft" onclick="closeModal()">
                    Annuler
                </button>

                <button type="submit" class="dr-btn dr-btn-primary" id="modalConfirmBtn">
                    Confirmer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_URL = "{{ url('/admin/security/users') }}";

    const ROUTES = {
        giveCredits: "{{ route('admin.security.users.giveCredits') }}",
        removeCredits: "{{ route('admin.security.users.removeCredits') }}",
        blockedStore: "{{ route('admin.security.blocked.store') }}"
    };

    const modal = document.getElementById('actionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const actionForm = document.getElementById('actionForm');
    const modalExtraFields = document.getElementById('modalExtraFields');
    const extraLabel = document.getElementById('extraLabel');
    const extraValue = document.getElementById('extraValue');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');

    function toggleMenu(button) {
        const current = button.closest('.action-menu');

        document.querySelectorAll('.action-menu').forEach(menu => {
            if (menu !== current) {
                menu.classList.remove('active');
            }
        });

        current.classList.toggle('active');
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.action-menu')) {
            document.querySelectorAll('.action-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    function addHiddenField(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        actionForm.appendChild(input);
    }

    function clearHiddenFields() {
        actionForm.querySelectorAll('input[type="hidden"]:not([name="_token"])').forEach(input => {
            input.remove();
        });
    }

    function resetModal() {
        modalExtraFields.style.display = 'none';
        modalConfirmBtn.className = 'dr-btn dr-btn-primary';
        modalConfirmBtn.innerText = 'Confirmer';
        clearHiddenFields();
    }

    function openActionModal(action, userId, userName, extra = null) {
        resetModal();

        document.querySelectorAll('.action-menu').forEach(menu => {
            menu.classList.remove('active');
        });

        switch (action) {
            case 'credits_add':
                actionForm.action = ROUTES.giveCredits;
                modalTitle.innerText = 'Ajouter des crédits';
                modalMessage.innerHTML = `Indiquez le nombre de crédits à ajouter au compte <strong>${userName}</strong>.`;
                modalExtraFields.style.display = 'block';
                extraLabel.innerText = 'Nombre de crédits';
                extraValue.value = 10;
                addHiddenField('user_id', userId);
                break;

            case 'credits_remove':
                actionForm.action = ROUTES.removeCredits;
                modalTitle.innerText = 'Retirer des crédits';
                modalMessage.innerHTML = `Indiquez le nombre de crédits à retirer du compte <strong>${userName}</strong>.`;
                modalExtraFields.style.display = 'block';
                extraLabel.innerText = 'Nombre de crédits';
                extraValue.value = 10;
                addHiddenField('user_id', userId);
                break;

            case 'status':
                actionForm.action = BASE_URL + '/' + userId + '/toggle-active';
                modalTitle.innerText = extra === 'suspendre' ? 'Suspendre le compte' : 'Réactiver le compte';
                modalMessage.innerHTML = `Voulez-vous vraiment <strong>${extra === 'suspendre' ? 'suspendre' : 'réactiver'}</strong> le compte <strong>${userName}</strong> ?`;
                modalConfirmBtn.className = 'dr-btn dr-btn-warning';
                break;

            case 'make_admin':
                actionForm.action = BASE_URL + '/' + userId + '/make-admin';
                modalTitle.innerText = 'Nommer administrateur';
                modalMessage.innerHTML = `Voulez-vous donner les droits administrateur à <strong>${userName}</strong> ?`;
                break;

            case 'remove_admin':
                actionForm.action = BASE_URL + '/' + userId + '/remove-admin';
                modalTitle.innerText = 'Retirer les droits administrateur';
                modalMessage.innerHTML = `Voulez-vous retirer les droits administrateur à <strong>${userName}</strong> ?`;
                break;

            case 'verify_email':
                actionForm.action = BASE_URL + '/' + userId + '/verify-email';
                modalTitle.innerText = 'Vérifier l’email';
                modalMessage.innerHTML = `Marquer l’email de <strong>${userName}</strong> comme vérifié ?`;
                break;

            case 'otp_bypass':
                actionForm.action = BASE_URL + '/' + userId + '/toggle-otp-bypass';
                modalTitle.innerText = extra === 'activer' ? 'Activer le laissez-passer OTP' : 'Désactiver le laissez-passer OTP';
                modalMessage.innerHTML = `Voulez-vous <strong>${extra === 'activer' ? 'activer' : 'désactiver'}</strong> le laissez-passer OTP pour <strong>${userName}</strong> ?`;
                break;

            case 'ban':
                actionForm.action = BASE_URL + '/' + userId + '/ban';
                modalTitle.innerText = 'Bannir l’utilisateur';
                modalMessage.innerHTML = `Cette action va suspendre et bloquer <strong>${userName}</strong>. Confirmez-vous le bannissement ?`;
                modalConfirmBtn.className = 'dr-btn dr-btn-danger';
                modalConfirmBtn.innerText = 'Bannir';
                break;

            case 'ban_ip':
                actionForm.action = ROUTES.blockedStore;
                modalTitle.innerText = 'Bloquer l’adresse IP';
                modalMessage.innerHTML = `Voulez-vous bloquer l’adresse IP <strong>${extra}</strong> associée à <strong>${userName}</strong> ?`;
                modalConfirmBtn.className = 'dr-btn dr-btn-danger';
                modalConfirmBtn.innerText = 'Bloquer l’IP';
                addHiddenField('type', 'ip');
                addHiddenField('value', extra);
                addHiddenField('reason', 'IP bannie depuis l’interface admin');
                break;

            default:
                return;
        }

        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
        actionForm.action = '';
        resetModal();
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });
</script>

@endsection