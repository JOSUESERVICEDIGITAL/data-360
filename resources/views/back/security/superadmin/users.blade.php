@extends('back.layouts.app')
@section('title', 'Superadmin — Utilisateurs | Data 360')
@section('content')

<style>
:root {
    --sa-gold:#f59e0b; --sa-gold-soft:#fffbeb; --sa-gold-dark:#d97706;
    --sa-blue:#3b82f6; --sa-blue-soft:#dbeafe;
    --sa-green:#10b981; --sa-green-soft:#d1fae5;
    --sa-red:#ef4444; --sa-red-soft:#fee2e2;
    --sa-purple:#8b5cf6; --sa-purple-soft:#ede9fe;
    --sa-border:#e2e8f0; --sa-dark:#0f172a; --sa-muted:#64748b;
}

.sa-page { min-height:100vh; padding:clamp(14px,3vw,28px); background:linear-gradient(160deg,#f0f7ff,#f8fafc 50%,#fefce8); }
.sa-container { max-width:1400px; margin:0 auto; }

/* Hero */
.sa-hero {
    background:linear-gradient(135deg,#0a1628,#1e3a8a 60%,#0f172a);
    border-radius:clamp(16px,3vw,26px);
    padding:clamp(18px,4vw,30px);
    margin-bottom:20px;
    border:1px solid rgba(245,158,11,.2);
    box-shadow:0 20px 60px rgba(10,22,40,.3);
    display:flex; justify-content:space-between; align-items:center;
    gap:16px; flex-wrap:wrap; position:relative; overflow:hidden;
}
.sa-hero::after { content:''; position:absolute; right:-80px; top:-80px; width:260px; height:260px; border-radius:999px; background:radial-gradient(circle,rgba(245,158,11,.1),transparent 70%); pointer-events:none; }
.sa-hero-left { position:relative; z-index:2; }
.sa-crown-badge { display:inline-flex; align-items:center; gap:7px; background:rgba(245,158,11,.15); border:1px solid rgba(245,158,11,.3); border-radius:999px; padding:5px 12px; font-size:10px; font-weight:800; color:var(--sa-gold); letter-spacing:.08em; text-transform:uppercase; margin-bottom:10px; }
.sa-hero h1 { margin:0; font-size:clamp(20px,4vw,32px); font-weight:900; color:white; letter-spacing:-.03em; }
.sa-hero p { margin:8px 0 0; color:rgba(255,255,255,.65); font-size:13px; }
.sa-hero-actions { display:flex; gap:10px; flex-wrap:wrap; position:relative; z-index:2; }

/* Stats row */
.sa-stats-row { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; margin-bottom:20px; }
.sa-stat-card { background:white; border:1px solid var(--sa-border); border-radius:16px; padding:14px 16px; display:flex; align-items:center; gap:12px; box-shadow:0 2px 12px rgba(15,23,42,.04); transition:transform .2s; }
.sa-stat-card:hover { transform:translateY(-2px); }
.sa-stat-icon { width:38px; height:38px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.sa-stat-val { font-size:clamp(18px,3vw,22px); font-weight:900; color:var(--sa-dark); line-height:1; }
.sa-stat-lbl { font-size:10px; font-weight:700; color:var(--sa-muted); text-transform:uppercase; letter-spacing:.08em; margin-top:3px; }

/* Panel */
.sa-panel { background:white; border:1px solid var(--sa-border); border-radius:20px; overflow:hidden; margin-bottom:20px; box-shadow:0 2px 12px rgba(15,23,42,.04); }
.sa-panel-header { padding:16px 20px; border-bottom:1px solid var(--sa-border); display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.sa-panel-title { display:flex; align-items:center; gap:10px; }
.sa-panel-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; background:var(--sa-blue-soft); color:var(--sa-blue); }
.sa-panel-title h2 { margin:0; font-size:clamp(14px,2vw,17px); font-weight:900; color:var(--sa-dark); }
.sa-panel-title p { margin:2px 0 0; color:var(--sa-muted); font-size:12px; }

/* Filters */
.sa-filters { padding:14px 20px; border-bottom:1px solid var(--sa-border); display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.sa-search-box { position:relative; flex:1; min-width:200px; }
.sa-search-box i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px; pointer-events:none; }
.sa-search-box input { width:100%; border:1.5px solid var(--sa-border); border-radius:10px; padding:9px 12px 9px 34px; font-size:13px; outline:none; transition:border-color .2s; box-sizing:border-box; }
.sa-search-box input:focus { border-color:var(--sa-blue); }
.sa-select { border:1.5px solid var(--sa-border); border-radius:10px; padding:9px 12px; font-size:13px; outline:none; background:white; cursor:pointer; }
.sa-select:focus { border-color:var(--sa-blue); }

/* Buttons */
.sa-btn { border:none; border-radius:10px; padding:9px 14px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .18s; text-decoration:none; white-space:nowrap; }
.sa-btn-primary  { background:var(--sa-dark); color:white; }
.sa-btn-primary:hover { background:#1e293b; }
.sa-btn-gold     { background:var(--sa-gold); color:#78350f; }
.sa-btn-gold:hover { background:var(--sa-gold-dark); color:white; }
.sa-btn-soft     { background:#f1f5f9; color:#334155; }
.sa-btn-soft:hover { background:#e2e8f0; }
.sa-btn-danger   { background:var(--sa-red); color:white; }
.sa-btn-danger:hover { background:#dc2626; }
.sa-btn-green    { background:var(--sa-green); color:white; }
.sa-btn-green:hover { background:#059669; }
.sa-btn-sm { padding:7px 11px; font-size:11px; }

/* Bulk bar */
.sa-bulk-bar { padding:10px 20px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; border-bottom:1px solid var(--sa-border); background:#fafbfc; }
.sa-bulk-info { font-size:12px; font-weight:700; color:var(--sa-muted); }

/* Table */
.table-wrapper { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.sa-table { width:100%; border-collapse:separate; border-spacing:0; min-width:900px; }
.sa-table th { background:#f8fafc; color:#64748b; font-size:10px; text-transform:uppercase; letter-spacing:.1em; font-weight:800; text-align:left; padding:11px 14px; border-bottom:1px solid var(--sa-border); white-space:nowrap; }
.sa-table td { padding:13px 14px; border-bottom:1px solid var(--sa-border); vertical-align:middle; font-size:13px; color:#334155; }
.sa-table tr:last-child td { border-bottom:none; }
.sa-table tbody tr:hover td { background:#fafbfc; }

/* User identity */
.sa-user-id { display:flex; align-items:center; gap:10px; min-width:220px; }
.sa-avatar { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#0053b3,#1d4ed8); color:white; font-weight:900; font-size:12px; flex-shrink:0; text-transform:uppercase; }
.sa-avatar-super { background:linear-gradient(135deg,#f59e0b,#d97706); }
.sa-user-name { font-weight:800; font-size:13px; color:var(--sa-dark); }
.sa-user-email { font-size:11px; color:var(--sa-muted); margin-top:1px; }
.sa-user-phone { font-size:10px; color:#94a3b8; margin-top:1px; }

/* Badges */
.sa-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font-size:10px; font-weight:800; white-space:nowrap; }
.sa-badge-green  { background:var(--sa-green-soft); color:#065f46; }
.sa-badge-red    { background:var(--sa-red-soft); color:#991b1b; }
.sa-badge-gold   { background:var(--sa-gold-soft); color:#92400e; border:1px solid rgba(245,158,11,.25); }
.sa-badge-blue   { background:var(--sa-blue-soft); color:#1e40af; }
.sa-badge-purple { background:var(--sa-purple-soft); color:#5b21b6; }
.sa-badge-gray   { background:#f1f5f9; color:#475569; }

/* Credits */
.sa-credits { font-size:18px; font-weight:900; color:var(--sa-blue); }

/* Action menu */
.action-menu { position:relative; display:inline-flex; justify-content:flex-end; }
.menu-trigger { width:32px; height:32px; border-radius:9px; border:1px solid var(--sa-border); background:white; color:#64748b; cursor:pointer; display:grid; place-items:center; transition:all .15s; }
.menu-trigger:hover { background:#f1f5f9; }
.menu-dropdown { position:absolute; right:0; top:38px; width:230px; background:white; border:1px solid var(--sa-border); border-radius:13px; box-shadow:0 20px 60px rgba(15,23,42,.15); padding:6px; z-index:50; opacity:0; visibility:hidden; transform:translateY(-6px); transition:all .15s; }
.action-menu.active .menu-dropdown { opacity:1; visibility:visible; transform:translateY(0); }
.menu-item { width:100%; border:none; background:transparent; display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:9px; cursor:pointer; color:#334155; font-size:12px; font-weight:700; text-align:left; text-decoration:none; transition:all .15s; }
.menu-item:hover { background:#f1f5f9; color:var(--sa-blue); }
.menu-item.danger { color:var(--sa-red); }
.menu-item.danger:hover { background:var(--sa-red-soft); }
.menu-item.gold { color:#92400e; }
.menu-item.gold:hover { background:var(--sa-gold-soft); }
.menu-item i { width:14px; text-align:center; flex-shrink:0; }
.menu-divider { height:1px; background:var(--sa-border); margin:4px 0; }

/* Empty */
.sa-empty { text-align:center; padding:50px 20px; color:var(--sa-muted); }
.sa-empty i { font-size:32px; color:#cbd5e1; display:block; margin-bottom:10px; }

/* Pagination */
.sa-pagination { padding:14px 20px; border-top:1px solid var(--sa-border); display:flex; justify-content:center; }

/* Modal */
.sa-modal-overlay { position:fixed; inset:0; background:rgba(10,22,40,.6); backdrop-filter:blur(5px); display:none; align-items:center; justify-content:center; z-index:10000; padding:16px; }
.sa-modal-overlay.active { display:flex; }
.sa-modal { width:min(560px,100%); background:white; border-radius:20px; box-shadow:0 40px 100px rgba(10,22,40,.25); overflow:hidden; animation:saIn .2s ease; max-height:90vh; display:flex; flex-direction:column; }
@keyframes saIn { from{opacity:0;transform:scale(.94) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
.sa-modal-header { padding:18px 20px; border-bottom:1px solid var(--sa-border); display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
.sa-modal-header h3 { margin:0; font-size:16px; font-weight:900; color:var(--sa-dark); display:flex; align-items:center; gap:8px; }
.sa-modal-close { width:32px; height:32px; border-radius:9px; border:none; background:#f1f5f9; color:#64748b; cursor:pointer; display:grid; place-items:center; transition:background .15s; }
.sa-modal-close:hover { background:#e2e8f0; }
.sa-modal-body { padding:20px; overflow-y:auto; flex:1; }
.sa-modal-footer { padding:14px 20px; border-top:1px solid var(--sa-border); display:flex; justify-content:flex-end; gap:8px; flex-shrink:0; }
.sa-field { margin-bottom:14px; }
.sa-field label { display:block; font-size:12px; font-weight:800; color:#334155; margin-bottom:5px; }
.sa-field input, .sa-field select, .sa-field textarea { width:100%; border:1.5px solid var(--sa-border); border-radius:10px; padding:10px 12px; font-size:13px; outline:none; box-sizing:border-box; transition:border-color .2s; }
.sa-field input:focus, .sa-field select:focus, .sa-field textarea:focus { border-color:var(--sa-blue); }
.sa-warning-box { background:var(--sa-red-soft); border:1px solid #fecaca; border-radius:10px; padding:12px 14px; margin-bottom:14px; display:flex; gap:10px; font-size:13px; color:#991b1b; line-height:1.5; }

/* Impersonate banner */
.sa-impersonate-banner { background:linear-gradient(135deg,#f59e0b,#d97706); color:#78350f; padding:10px 20px; display:flex; align-items:center; justify-content:space-between; gap:10px; font-size:13px; font-weight:700; flex-wrap:wrap; }

/* Responsive */
@media(max-width:1100px) { .sa-stats-row { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media(max-width:768px) {
    .sa-hero { flex-direction:column; }
    .sa-stats-row { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .sa-panel-header { flex-direction:column; align-items:flex-start; }
    .sa-filters { flex-direction:column; }
    .sa-filters .sa-search-box { width:100%; }
    .sa-bulk-bar { flex-direction:column; align-items:flex-start; }
}
@media(max-width:480px) { .sa-stats-row { grid-template-columns:1fr 1fr; } }
</style>

@php
    $totalUsers   = $users->total();
    $collection   = $users->getCollection();
    $onlineCnt    = $collection->filter(fn($u) => $u->last_login_at && $u->last_login_at->diffInMinutes(now()) < 30)->count();
    $superCnt     = $collection->where('is_superadmin', true)->count();
    $premiumCnt   = $collection->whereIn('plan', ['premium','enterprise'])->count();
    $suspendedCnt = $collection->where('is_active', false)->count();
@endphp

<div class="sa-page">
<div class="sa-container">

    {{-- Impersonate banner --}}
    @if(session('superadmin_impersonating'))
    <div class="sa-impersonate-banner">
        <span><i class="fa-solid fa-mask" style="margin-right:6px;"></i> Mode impersonate actif — vous êtes connecté en tant que {{ Auth::user()->name }}</span>
        <form method="POST" action="{{ route('admin.superadmin.stop-impersonate') }}" style="margin:0;">
            @csrf
            <button type="submit" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-right-from-bracket"></i> Reprendre ma session
            </button>
        </form>
    </div>
    @endif

    {{-- Hero --}}
    <div class="sa-hero">
        <div class="sa-hero-left">
            <div class="sa-crown-badge"><i class="fa-solid fa-crown"></i> Superadmin</div>
            <h1>Gestion des utilisateurs</h1>
            <p>Contrôle total — tous les comptes, rôles, crédits et plans.</p>
        </div>
        <div class="sa-hero-actions">
            <a href="{{ route('admin.superadmin.users.export') }}" class="sa-btn sa-btn-gold">
                <i class="fa-solid fa-download"></i> Exporter CSV
            </a>
            <a href="{{ route('admin.security.users.create') }}" class="sa-btn" style="background:white;color:#0053b3;">
                <i class="fa-solid fa-user-plus"></i> Nouveau compte
            </a>
            <a href="{{ route('admin.superadmin.index') }}" class="sa-btn sa-btn-soft">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-weight:700;font-size:13px;color:#166534;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-weight:700;font-size:13px;color:#991b1b;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="sa-stats-row">
        <div class="sa-stat-card">
            <div class="sa-stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-users"></i></div>
            <div><div class="sa-stat-val">{{ $users->total() }}</div><div class="sa-stat-lbl">Total</div></div>
        </div>
        <div class="sa-stat-card">
            <div class="sa-stat-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-circle"></i></div>
            <div><div class="sa-stat-val" style="color:#10b981;">{{ $onlineCnt }}</div><div class="sa-stat-lbl">En ligne</div></div>
        </div>
        <div class="sa-stat-card">
            <div class="sa-stat-icon" style="background:#fffbeb;color:#f59e0b;"><i class="fa-solid fa-crown"></i></div>
            <div><div class="sa-stat-val" style="color:#f59e0b;">{{ $superCnt }}</div><div class="sa-stat-lbl">Superadmins</div></div>
        </div>
        <div class="sa-stat-card">
            <div class="sa-stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-bolt"></i></div>
            <div><div class="sa-stat-val" style="color:#8b5cf6;">{{ $premiumCnt }}</div><div class="sa-stat-lbl">Premium</div></div>
        </div>
        <div class="sa-stat-card">
            <div class="sa-stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-user-slash"></i></div>
            <div><div class="sa-stat-val" style="color:#ef4444;">{{ $suspendedCnt }}</div><div class="sa-stat-lbl">Suspendus</div></div>
        </div>
    </div>

    {{-- Panel principal --}}
    <div class="sa-panel">
        <div class="sa-panel-header">
            <div class="sa-panel-title">
                <div class="sa-panel-icon"><i class="fa-solid fa-list-check"></i></div>
                <div>
                    <h2>Comptes utilisateurs</h2>
                    <p>{{ $users->total() }} compte(s) — page {{ $users->currentPage() }}/{{ $users->lastPage() }}</p>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="sa-btn sa-btn-gold sa-btn-sm" onclick="openModal('modal-bulk-credits')">
                    <i class="fa-solid fa-coins"></i> Crédits en masse
                </button>
                <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="openModal('modal-bulk-plan')">
                    <i class="fa-solid fa-layer-group"></i> Plans en masse
                </button>
            </div>
        </div>

        {{-- Filtres --}}
        <form method="GET" action="{{ route('admin.superadmin.users.index') }}" class="sa-filters">
            <div class="sa-search-box">
                <i class="fa-solid fa-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher nom, email, téléphone...">
            </div>
            <select name="plan" class="sa-select">
                <option value="">Tous les plans</option>
                <option value="free"       {{ request('plan') === 'free'       ? 'selected' : '' }}>Free</option>
                <option value="premium"    {{ request('plan') === 'premium'    ? 'selected' : '' }}>Premium</option>
                <option value="enterprise" {{ request('plan') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
            </select>
            <select name="role" class="sa-select">
                <option value="">Tous les rôles</option>
                <option value="superadmin" {{ request('role') === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                <option value="admin"      {{ request('role') === 'admin'      ? 'selected' : '' }}>Admin</option>
                <option value="free"       {{ request('role') === 'free'       ? 'selected' : '' }}>Utilisateur</option>
            </select>
            <button type="submit" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            <a href="{{ route('admin.superadmin.users.index') }}" class="sa-btn sa-btn-soft sa-btn-sm">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        </form>

        {{-- Bulk form caché --}}
        <form method="POST" action="{{ route('admin.security.users.bulkDelete') }}" id="bulkForm">
            @csrf @method('DELETE')
        </form>

        <div class="sa-bulk-bar">
            <div class="sa-bulk-info">Sélectionnez des utilisateurs pour les actions en masse.</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="submit" form="bulkForm" class="sa-btn sa-btn-danger sa-btn-sm"
                        onclick="return confirm('Supprimer les utilisateurs sélectionnés ?');">
                    <i class="fa-regular fa-trash-can"></i> Supprimer sélection
                </button>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-wrapper">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th style="width:38px;"><input type="checkbox" id="checkAll"></th>
                        <th>Utilisateur</th>
                        <th>Rôle & Statut</th>
                        <th>Plan</th>
                        <th>Crédits</th>
                        <th>Dernière connexion</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                @php
                    $initials   = collect(explode(' ', trim($user->name ?? '')))->filter()->take(2)->map(fn($p) => mb_substr($p,0,1))->implode('');
                    $isSuperAdm = $user->isSuperAdmin();
                    $isOnline   = $user->last_login_at && $user->last_login_at->diffInMinutes(now()) < 30;
                    $safeName   = addslashes($user->name ?? '');
                    $safeId     = $user->id;
                @endphp
                <tr>
                    <td>
                        @if($user->id !== auth()->id() && !$isSuperAdm)
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-cb" form="bulkForm">
                        @endif
                    </td>
                    <td>
                        <div class="sa-user-id">
                            <div class="sa-avatar {{ $isSuperAdm ? 'sa-avatar-super' : '' }}">
                                {{ $isSuperAdm ? '👑' : ($initials ?: 'U') }}
                            </div>
                            <div>
                                <div class="sa-user-name">{{ $user->name ?? '—' }}</div>
                                <div class="sa-user-email">{{ $user->email }}</div>
                                <div class="sa-user-phone">{{ $user->phone ?? '—' }} · ID #{{ $user->id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                            @if($isSuperAdm)
                                <span class="sa-badge sa-badge-gold"><i class="fa-solid fa-crown"></i> Superadmin</span>
                            @elseif($user->is_admin)
                                <span class="sa-badge sa-badge-blue"><i class="fa-solid fa-user-shield"></i> Admin</span>
                            @endif
                            @if($isOnline)
                                <span class="sa-badge sa-badge-green"><i class="fa-solid fa-circle" style="font-size:7px;"></i> En ligne</span>
                            @elseif($user->is_active)
                                <span class="sa-badge sa-badge-gray">Actif</span>
                            @else
                                <span class="sa-badge sa-badge-red">Suspendu</span>
                            @endif
                            @if($user->email_verified_at)
                                <span class="sa-badge sa-badge-green"><i class="fa-solid fa-envelope-circle-check"></i></span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="sa-badge {{ in_array($user->plan,['premium','enterprise']) ? 'sa-badge-purple' : 'sa-badge-gray' }}">
                            {{ $user->plan ?? 'free' }}
                        </span>
                    </td>
                    <td><div class="sa-credits">{{ number_format($user->credits ?? 0, 0, ',', ' ') }}</div></td>
                    <td>
                        <div style="font-size:12px;">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '—' }}</div>
                        <div style="font-size:10px;color:#94a3b8;">{{ $user->last_login_ip ?? '' }}</div>
                    </td>
                    <td style="text-align:right;">
                        <div class="action-menu">
                            <button class="menu-trigger" onclick="toggleMenu(this)">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="menu-dropdown">
                                <a class="menu-item" href="{{ route('admin.security.users.edit', $user) }}">
                                    <i class="fa-solid fa-pen-to-square"></i> Modifier
                                </a>
                                <button class="menu-item" onclick="openCreditsModal({{ $safeId }}, '{{ $safeName }}')">
                                    <i class="fa-solid fa-coins"></i> Gérer crédits
                                </button>
                                <div class="menu-divider"></div>
                                @if(!$isSuperAdm && auth()->user()->isSuperAdmin())
                                    <form method="POST" action="{{ route('admin.superadmin.users.make-superadmin', $user) }}">
                                        @csrf
                                        <button type="submit" class="menu-item gold"
                                                onclick="return confirm('Promouvoir {{ $safeName }} en superadmin ?')">
                                            <i class="fa-solid fa-crown"></i> Nommer superadmin
                                        </button>
                                    </form>
                                @elseif($isSuperAdm && $user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.superadmin.users.remove-superadmin', $user) }}">
                                        @csrf
                                        <button type="submit" class="menu-item"
                                                onclick="return confirm('Rétrograder {{ $safeName }} ?')">
                                            <i class="fa-solid fa-crown" style="opacity:.4;"></i> Rétrograder
                                        </button>
                                    </form>
                                @endif
                                @if(!$isSuperAdm)
                                    <form method="POST" action="{{ route('admin.superadmin.users.impersonate', $user) }}">
                                        @csrf
                                        <button type="submit" class="menu-item"
                                                onclick="return confirm('Se connecter en tant que {{ $safeName }} ?')">
                                            <i class="fa-solid fa-mask"></i> Impersonate
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.superadmin.users.force-password-reset', $user) }}">
                                        @csrf
                                        <button type="submit" class="menu-item"
                                                onclick="return confirm('Réinitialiser le mot de passe de {{ $safeName }} ?')">
                                            <i class="fa-solid fa-key"></i> Reset password
                                        </button>
                                    </form>
                                    <div class="menu-divider"></div>
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.security.users.destroy', $user) }}"
                                              onsubmit="return confirm('Supprimer définitivement {{ $safeName }} ?')">
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
                <tr><td colspan="7"><div class="sa-empty"><i class="fa-solid fa-user-slash"></i><strong>Aucun utilisateur trouvé</strong></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="sa-pagination">{{ $users->onEachSide(1)->links() }}</div>
    </div>

</div>
</div>

{{-- ── Modal crédits individuels ── --}}
<div id="modal-credits-single" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-coins" style="color:#8b5cf6;"></i> Gérer les crédits</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-credits-single')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="creditsForm" method="POST" action="">
            @csrf
            <div class="sa-modal-body">
                <p id="creditModalDesc" style="color:#64748b;font-size:13px;margin-bottom:14px;"></p>
                <div class="sa-field">
                    <label>Action</label>
                    <select name="action" id="creditAction" class="sa-select" style="width:100%;">
                        <option value="add">Ajouter des crédits</option>
                        <option value="remove">Retirer des crédits</option>
                        <option value="set">Définir à un montant fixe</option>
                    </select>
                </div>
                <div class="sa-field">
                    <label>Montant</label>
                    <input type="number" name="amount" min="0" value="10" style="width:100%;">
                </div>
                <input type="hidden" name="user_id" id="creditUserId">
            </div>
            <div class="sa-modal-footer">
                <button type="submit" class="sa-btn sa-btn-primary">Confirmer</button>
                <button type="button" class="sa-btn sa-btn-soft" onclick="closeModal('modal-credits-single')">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal bulk crédits ── --}}
<div id="modal-bulk-credits" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-coins" style="color:#8b5cf6;"></i> Crédits en masse</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-bulk-credits')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('admin.superadmin.users.bulk-credits') }}">
            @csrf
            <div class="sa-modal-body">
                <div class="sa-field">
                    <label>Cible</label>
                    <select name="target" class="sa-select" style="width:100%;">
                        <option value="all">Tous les utilisateurs</option>
                        <option value="free">Plan Free uniquement</option>
                        <option value="premium">Plan Premium uniquement</option>
                        <option value="enterprise">Plan Enterprise uniquement</option>
                    </select>
                </div>
                <div class="sa-field">
                    <label>Action</label>
                    <select name="action" class="sa-select" style="width:100%;">
                        <option value="add">Ajouter</option>
                        <option value="set">Définir à</option>
                        <option value="reset">Remettre à zéro</option>
                    </select>
                </div>
                <div class="sa-field">
                    <label>Montant</label>
                    <input type="number" name="amount" min="0" value="10">
                </div>
            </div>
            <div class="sa-modal-footer">
                <button type="submit" class="sa-btn sa-btn-gold"
                        onclick="return confirm('Appliquer les crédits en masse ?')">
                    <i class="fa-solid fa-coins"></i> Appliquer
                </button>
                <button type="button" class="sa-btn sa-btn-soft" onclick="closeModal('modal-bulk-credits')">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal bulk plan ── --}}
<div id="modal-bulk-plan" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-layer-group" style="color:#10b981;"></i> Plans en masse</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-bulk-plan')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('admin.superadmin.users.bulk-plan') }}">
            @csrf
            <div class="sa-modal-body">
                <div class="sa-warning-box">
                    <i class="fa-solid fa-triangle-exclamation" style="flex-shrink:0;margin-top:2px;"></i>
                    <div>Cette action modifie le plan de plusieurs utilisateurs simultanément. Les superadmins sont exclus.</div>
                </div>
                <div class="sa-field">
                    <label>Depuis le plan</label>
                    <select name="from_plan" class="sa-select" style="width:100%;">
                        <option value="all">Tous les plans</option>
                        <option value="free">Free</option>
                        <option value="premium">Premium</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                <div class="sa-field">
                    <label>Vers le plan</label>
                    <select name="to_plan" class="sa-select" style="width:100%;">
                        <option value="free">Free</option>
                        <option value="premium">Premium</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>
            <div class="sa-modal-footer">
                <button type="submit" class="sa-btn sa-btn-green"
                        onclick="return confirm('Modifier les plans en masse ?')">
                    <i class="fa-solid fa-layer-group"></i> Appliquer
                </button>
                <button type="button" class="sa-btn sa-btn-soft" onclick="closeModal('modal-bulk-plan')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id)?.classList.add('active'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id)?.classList.remove('active'); document.body.style.overflow=''; }

document.querySelectorAll('.sa-modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if(e.target===o) closeModal(o.id); });
});
document.addEventListener('keydown', e => {
    if(e.key==='Escape') document.querySelectorAll('.sa-modal-overlay.active').forEach(m => closeModal(m.id));
});

function toggleMenu(btn) {
    const cur = btn.closest('.action-menu');
    document.querySelectorAll('.action-menu').forEach(m => { if(m!==cur) m.classList.remove('active'); });
    cur.classList.toggle('active');
}
document.addEventListener('click', e => {
    if(!e.target.closest('.action-menu')) document.querySelectorAll('.action-menu').forEach(m => m.classList.remove('active'));
});

function openCreditsModal(userId, userName) {
    document.getElementById('creditUserId').value = userId;
    document.getElementById('creditModalDesc').innerHTML = `Modifier les crédits de <strong>${userName}</strong>.`;
    document.getElementById('creditsForm').action = `/admin/security/users/${userId}/give-credits`;
    openModal('modal-credits-single');
}

const checkAll = document.getElementById('checkAll');
if(checkAll) checkAll.addEventListener('change', function() {
    document.querySelectorAll('.user-cb').forEach(cb => cb.checked = this.checked);
});
</script>

@endsection
