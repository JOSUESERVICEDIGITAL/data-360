@extends('back.layouts.app')
@section('title', 'Superadmin — Paiements | Data 360')
@section('content')

<style>
:root {
    --sa-gold:#f59e0b; --sa-gold-soft:#fffbeb; --sa-gold-dark:#d97706;
    --sa-blue:#3b82f6; --sa-blue-soft:#dbeafe;
    --sa-green:#10b981; --sa-green-soft:#d1fae5;
    --sa-red:#ef4444; --sa-red-soft:#fee2e2;
    --sa-purple:#8b5cf6; --sa-purple-soft:#ede9fe;
    --sa-orange:#f97316; --sa-orange-soft:#ffedd5;
    --sa-border:#e2e8f0; --sa-dark:#0f172a; --sa-muted:#64748b;
}

.sa-page { min-height:100vh; padding:clamp(14px,3vw,28px); background:linear-gradient(160deg,#f0fff4,#f8fafc 50%,#fefce8); }
.sa-container { max-width:1400px; margin:0 auto; }

/* Hero */
.sa-hero { background:linear-gradient(135deg,#064e3b,#065f46 50%,#0a1628); border-radius:clamp(16px,3vw,26px); padding:clamp(18px,4vw,30px); margin-bottom:20px; border:1px solid rgba(16,185,129,.2); box-shadow:0 20px 60px rgba(6,78,59,.25); display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; position:relative; overflow:hidden; }
.sa-hero::after { content:''; position:absolute; right:-80px; top:-80px; width:260px; height:260px; border-radius:999px; background:radial-gradient(circle,rgba(16,185,129,.12),transparent 70%); pointer-events:none; }
.sa-hero-left { position:relative; z-index:2; }
.sa-kicker { display:inline-flex; align-items:center; gap:7px; background:rgba(16,185,129,.15); border:1px solid rgba(16,185,129,.3); border-radius:999px; padding:5px 12px; font-size:10px; font-weight:800; color:#6ee7b7; letter-spacing:.08em; text-transform:uppercase; margin-bottom:10px; }
.sa-hero h1 { margin:0; font-size:clamp(20px,4vw,32px); font-weight:900; color:white; letter-spacing:-.03em; }
.sa-hero p { margin:8px 0 0; color:rgba(255,255,255,.65); font-size:13px; }
.sa-hero-actions { display:flex; gap:10px; flex-wrap:wrap; position:relative; z-index:2; }

/* KPI grid */
.sa-kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
.sa-kpi { background:white; border:1px solid var(--sa-border); border-radius:18px; padding:18px; box-shadow:0 2px 12px rgba(15,23,42,.04); transition:transform .2s; position:relative; overflow:hidden; }
.sa-kpi:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(15,23,42,.08); }
.sa-kpi::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.sa-kpi-green::before  { background:linear-gradient(90deg,#10b981,#6ee7b7); }
.sa-kpi-blue::before   { background:linear-gradient(90deg,#3b82f6,#93c5fd); }
.sa-kpi-gold::before   { background:linear-gradient(90deg,#f59e0b,#fcd34d); }
.sa-kpi-purple::before { background:linear-gradient(90deg,#8b5cf6,#c4b5fd); }
.sa-kpi-icon { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:12px; }
.sa-kpi-val { font-size:clamp(20px,3vw,28px); font-weight:900; color:var(--sa-dark); line-height:1; }
.sa-kpi-lbl { font-size:10px; font-weight:700; color:var(--sa-muted); text-transform:uppercase; letter-spacing:.08em; margin-top:4px; }
.sa-kpi-trend { font-size:11px; font-weight:700; margin-top:8px; display:flex; align-items:center; gap:4px; }
.sa-kpi-trend.up { color:#10b981; }
.sa-kpi-trend.neutral { color:#94a3b8; }

/* Panel */
.sa-panel { background:white; border:1px solid var(--sa-border); border-radius:20px; overflow:hidden; margin-bottom:20px; box-shadow:0 2px 12px rgba(15,23,42,.04); }
.sa-panel-header { padding:16px 20px; border-bottom:1px solid var(--sa-border); display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.sa-panel-title { display:flex; align-items:center; gap:10px; }
.sa-panel-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.sa-panel-title h2 { margin:0; font-size:clamp(14px,2vw,17px); font-weight:900; color:var(--sa-dark); }
.sa-panel-title p { margin:2px 0 0; color:var(--sa-muted); font-size:12px; }

/* Buttons */
.sa-btn { border:none; border-radius:10px; padding:9px 14px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .18s; text-decoration:none; white-space:nowrap; }
.sa-btn-primary { background:var(--sa-dark); color:white; }
.sa-btn-primary:hover { background:#1e293b; }
.sa-btn-gold { background:var(--sa-gold); color:#78350f; }
.sa-btn-gold:hover { background:var(--sa-gold-dark); color:white; }
.sa-btn-soft { background:#f1f5f9; color:#334155; }
.sa-btn-soft:hover { background:#e2e8f0; }
.sa-btn-green { background:var(--sa-green); color:white; }
.sa-btn-sm { padding:7px 11px; font-size:11px; }

/* Grid 2 colonnes */
.sa-grid-2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-bottom:20px; }

/* Table */
.table-wrapper { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.sa-table { width:100%; border-collapse:separate; border-spacing:0; min-width:700px; }
.sa-table th { background:#f8fafc; color:#64748b; font-size:10px; text-transform:uppercase; letter-spacing:.1em; font-weight:800; text-align:left; padding:11px 14px; border-bottom:1px solid var(--sa-border); white-space:nowrap; }
.sa-table td { padding:13px 14px; border-bottom:1px solid var(--sa-border); vertical-align:middle; font-size:13px; color:#334155; }
.sa-table tr:last-child td { border-bottom:none; }
.sa-table tbody tr:hover td { background:#fafbfc; }

/* Badges */
.sa-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font-size:10px; font-weight:800; }
.sa-badge-green  { background:var(--sa-green-soft); color:#065f46; }
.sa-badge-red    { background:var(--sa-red-soft); color:#991b1b; }
.sa-badge-gold   { background:var(--sa-gold-soft); color:#92400e; }
.sa-badge-blue   { background:var(--sa-blue-soft); color:#1e40af; }
.sa-badge-purple { background:var(--sa-purple-soft); color:#5b21b6; }
.sa-badge-gray   { background:#f1f5f9; color:#475569; }
.sa-badge-orange { background:var(--sa-orange-soft); color:#9a3412; }

/* Plan chart */
.sa-plan-chart { padding:20px; }
.sa-plan-row { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.sa-plan-label { width:90px; font-size:12px; font-weight:700; color:#334155; flex-shrink:0; }
.sa-plan-bar-wrap { flex:1; background:#f1f5f9; border-radius:999px; height:10px; overflow:hidden; }
.sa-plan-bar { height:100%; border-radius:999px; transition:width 1s ease; }
.sa-plan-count { width:45px; text-align:right; font-size:12px; font-weight:800; color:var(--sa-dark); flex-shrink:0; }

/* Empty */
.sa-empty { text-align:center; padding:40px 20px; color:var(--sa-muted); }
.sa-empty i { font-size:30px; color:#cbd5e1; display:block; margin-bottom:8px; }

/* Info box */
.sa-info-box { background:var(--sa-blue-soft); border:1px solid #bfdbfe; border-radius:12px; padding:14px 16px; display:flex; gap:10px; font-size:13px; color:#1e40af; line-height:1.5; margin-bottom:16px; }

/* Stripe box */
.sa-stripe-box { background:linear-gradient(135deg,#635bff08,#635bff15); border:1px solid #635bff30; border-radius:14px; padding:16px; text-align:center; }
.sa-stripe-logo { font-size:24px; font-weight:900; color:#635bff; letter-spacing:-.02em; margin-bottom:8px; }
.sa-stripe-desc { font-size:12px; color:#64748b; line-height:1.5; }

/* Responsive */
@media(max-width:1100px) { .sa-kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:768px) {
    .sa-hero { flex-direction:column; }
    .sa-grid-2 { grid-template-columns:1fr; }
    .sa-kpi-grid { grid-template-columns:1fr 1fr; }
    .sa-panel-header { flex-direction:column; align-items:flex-start; }
}
@media(max-width:480px) { .sa-kpi-grid { grid-template-columns:1fr; } }
</style>

@php
    $totalTx      = $stats['total_transactions'] ?? 0;
    $totalAmount  = $stats['total_amount'] ?? 0;
    $monthAmount  = $stats['this_month'] ?? 0;
    $recent       = $stats['recent'] ?? collect();

    // Stats plans
    $planStats = \App\Models\User::selectRaw('plan, count(*) as count')->groupBy('plan')->pluck('count', 'plan');
    $totalUsers = \App\Models\User::count() ?: 1;

    $freeCount       = $planStats['free']       ?? 0;
    $premiumCount    = $planStats['premium']     ?? 0;
    $enterpriseCount = $planStats['enterprise']  ?? 0;
@endphp

<div class="sa-page">
<div class="sa-container">

    {{-- Hero --}}
    <div class="sa-hero">
        <div class="sa-hero-left">
            <div class="sa-kicker"><i class="fa-solid fa-credit-card"></i> Paiements</div>
            <h1>Gestion des paiements</h1>
            <p>Transactions Stripe, abonnements, crédits et revenus de l'application.</p>
        </div>
        <div class="sa-hero-actions">
            <a href="{{ route('admin.superadmin.index') }}" class="sa-btn sa-btn-soft">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
            <a href="{{ route('admin.superadmin.users.index') }}" class="sa-btn" style="background:white;color:#064e3b;">
                <i class="fa-solid fa-users"></i> Utilisateurs
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="sa-kpi-grid">
        <div class="sa-kpi sa-kpi-green">
            <div class="sa-kpi-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-receipt"></i></div>
            <div class="sa-kpi-val">{{ number_format($totalTx, 0, ',', ' ') }}</div>
            <div class="sa-kpi-lbl">Transactions totales</div>
            <div class="sa-kpi-trend neutral"><i class="fa-solid fa-database"></i> Depuis le début</div>
        </div>

        <div class="sa-kpi sa-kpi-blue">
            <div class="sa-kpi-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-euro-sign"></i></div>
            <div class="sa-kpi-val">{{ number_format($totalAmount / 100, 2, ',', ' ') }} €</div>
            <div class="sa-kpi-lbl">Revenus totaux</div>
            <div class="sa-kpi-trend neutral"><i class="fa-solid fa-chart-line"></i> Cumulé</div>
        </div>

        <div class="sa-kpi sa-kpi-gold">
            <div class="sa-kpi-icon" style="background:#fffbeb;color:#d97706;"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="sa-kpi-val">{{ number_format($monthAmount / 100, 2, ',', ' ') }} €</div>
            <div class="sa-kpi-lbl">Ce mois-ci</div>
            <div class="sa-kpi-trend up"><i class="fa-solid fa-arrow-trend-up"></i> Mois en cours</div>
        </div>

        <div class="sa-kpi sa-kpi-purple">
            <div class="sa-kpi-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-layer-group"></i></div>
            <div class="sa-kpi-val">{{ $premiumCount + $enterpriseCount }}</div>
            <div class="sa-kpi-lbl">Abonnés payants</div>
            <div class="sa-kpi-trend up"><i class="fa-solid fa-users"></i> Premium + Enterprise</div>
        </div>
    </div>

    {{-- Grid 2 colonnes --}}
    <div class="sa-grid-2">

        {{-- Distribution des plans --}}
        <div class="sa-panel">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <div class="sa-panel-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-chart-pie"></i></div>
                    <div>
                        <h2>Distribution des plans</h2>
                        <p>Répartition des {{ $totalUsers }} utilisateurs</p>
                    </div>
                </div>
            </div>
            <div class="sa-plan-chart">
                <div class="sa-plan-row">
                    <div class="sa-plan-label">
                        <span class="sa-badge sa-badge-gray">Free</span>
                    </div>
                    <div class="sa-plan-bar-wrap">
                        <div class="sa-plan-bar" style="width:{{ round($freeCount / $totalUsers * 100) }}%;background:linear-gradient(90deg,#94a3b8,#cbd5e1);"></div>
                    </div>
                    <div class="sa-plan-count">{{ $freeCount }}</div>
                </div>
                <div class="sa-plan-row">
                    <div class="sa-plan-label">
                        <span class="sa-badge sa-badge-purple">Premium</span>
                    </div>
                    <div class="sa-plan-bar-wrap">
                        <div class="sa-plan-bar" style="width:{{ round($premiumCount / $totalUsers * 100) }}%;background:linear-gradient(90deg,#8b5cf6,#c4b5fd);"></div>
                    </div>
                    <div class="sa-plan-count">{{ $premiumCount }}</div>
                </div>
                <div class="sa-plan-row">
                    <div class="sa-plan-label">
                        <span class="sa-badge sa-badge-gold">Enterprise</span>
                    </div>
                    <div class="sa-plan-bar-wrap">
                        <div class="sa-plan-bar" style="width:{{ round($enterpriseCount / $totalUsers * 100) }}%;background:linear-gradient(90deg,#f59e0b,#fcd34d);"></div>
                    </div>
                    <div class="sa-plan-count">{{ $enterpriseCount }}</div>
                </div>

                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--sa-border);display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <div style="text-align:center;">
                        <div style="font-size:20px;font-weight:900;color:#8b5cf6;">{{ round(($premiumCount + $enterpriseCount) / $totalUsers * 100) }}%</div>
                        <div style="font-size:10px;color:var(--sa-muted);font-weight:700;text-transform:uppercase;">Taux conversion</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:20px;font-weight:900;color:#f59e0b;">{{ $enterpriseCount }}</div>
                        <div style="font-size:10px;color:var(--sa-muted);font-weight:700;text-transform:uppercase;">Enterprise</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:20px;font-weight:900;color:var(--sa-dark);">{{ $totalUsers }}</div>
                        <div style="font-size:10px;color:var(--sa-muted);font-weight:700;text-transform:uppercase;">Total users</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Intégration Stripe --}}
        <div class="sa-panel">
            <div class="sa-panel-header">
                <div class="sa-panel-title">
                    <div class="sa-panel-icon" style="background:#f0f0ff;color:#635bff;"><i class="fa-solid fa-credit-card"></i></div>
                    <div>
                        <h2>Intégration Stripe</h2>
                        <p>Configuration et accès au dashboard</p>
                    </div>
                </div>
            </div>
            <div style="padding:20px;">
                <div class="sa-stripe-box">
                    <div class="sa-stripe-logo">stripe</div>
                    <div class="sa-stripe-desc">Gérez vos paiements, abonnements et remboursements directement depuis le dashboard Stripe.</div>
                    <a href="https://dashboard.stripe.com" target="_blank" class="sa-btn sa-btn-sm" style="background:#635bff;color:white;margin-top:12px;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Ouvrir Stripe Dashboard
                    </a>
                </div>

                <div style="margin-top:16px;">
                    <div style="font-size:11px;font-weight:800;color:var(--sa-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Configuration actuelle</div>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;">
                            <span style="color:#64748b;">Mode</span>
                            <span class="sa-badge {{ str_contains(config('services.stripe.key',''), 'test') ? 'sa-badge-orange' : 'sa-badge-green' }}">
                                {{ str_contains(config('services.stripe.key',''), 'test') ? 'Test' : 'Production' }}
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;">
                            <span style="color:#64748b;">Clé publique</span>
                            <code style="background:#f1f5f9;padding:2px 6px;border-radius:5px;font-size:10px;">{{ substr(config('services.stripe.key','—'), 0, 16) }}…</code>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;">
                            <span style="color:#64748b;">Environnement</span>
                            <span class="sa-badge sa-badge-blue">{{ config('app.env', 'local') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions récentes --}}
    <div class="sa-panel">
        <div class="sa-panel-header">
            <div class="sa-panel-title">
                <div class="sa-panel-icon" style="background:var(--sa-green-soft);color:#059669;"><i class="fa-solid fa-list-ul"></i></div>
                <div>
                    <h2>Transactions récentes</h2>
                    <p>{{ $totalTx }} transaction(s) au total</p>
                </div>
            </div>
            <a href="{{ route('admin.superadmin.users.export') }}" class="sa-btn sa-btn-soft sa-btn-sm">
                <i class="fa-solid fa-download"></i> Exporter
            </a>
        </div>

        @if($totalTx === 0)
            <div class="sa-empty">
                <i class="fa-solid fa-receipt"></i>
                <strong style="display:block;color:#334155;">Aucune transaction enregistrée</strong>
                <span style="font-size:12px;margin-top:6px;display:block;">Les transactions Stripe apparaîtront ici une fois la table <code>credit_transactions</code> implémentée.</span>
            </div>
        @else
            <div class="table-wrapper">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $tx)
                        <tr>
                            <td style="font-size:11px;color:#94a3b8;">#{{ $tx->id ?? '—' }}</td>
                            <td style="font-size:12px;">{{ $tx->user_id ?? '—' }}</td>
                            <td><span class="sa-badge sa-badge-blue">{{ $tx->type ?? 'credit' }}</span></td>
                            <td style="font-weight:800;color:var(--sa-green);">{{ number_format(($tx->amount ?? 0) / 100, 2, ',', ' ') }} €</td>
                            <td><span class="sa-badge sa-badge-green">Succès</span></td>
                            <td style="font-size:12px;">{{ optional(\Carbon\Carbon::parse($tx->created_at ?? null))->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6"><div class="sa-empty"><i class="fa-solid fa-receipt"></i> Aucune transaction</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Info box --}}
    <div class="sa-info-box">
        <i class="fa-solid fa-circle-info" style="font-size:16px;flex-shrink:0;margin-top:1px;"></i>
        <div>Pour afficher les transactions réelles, créez une table <strong>credit_transactions</strong> avec les colonnes <code>id, user_id, type, amount, stripe_id, status, created_at</code> et reliez-la au modèle <strong>CreditTransaction</strong>. Le controller <code>SuperAdminController@payments</code> est déjà prêt.</div>
    </div>

    <div style="height:30px;"></div>
</div>
</div>

@endsection
