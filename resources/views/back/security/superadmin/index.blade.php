@extends('back.layouts.app')
@section('title', 'Panneau Superadmin | Data 360')
@section('content')

<style>
:root {
    --sa-gold:       #f59e0b;
    --sa-gold-dark:  #d97706;
    --sa-gold-soft:  #fffbeb;
    --sa-gold-glow:  rgba(245,158,11,0.15);
    --sa-bg:         #0a1628;
    --sa-bg2:        #0f1f3d;
    --sa-blue:       #3b82f6;
    --sa-blue-soft:  #dbeafe;
    --sa-green:      #10b981;
    --sa-green-soft: #d1fae5;
    --sa-red:        #ef4444;
    --sa-red-soft:   #fee2e2;
    --sa-purple:     #8b5cf6;
    --sa-purple-soft:#ede9fe;
    --sa-cyan:       #06b6d4;
    --sa-cyan-soft:  #cffafe;
    --sa-orange:     #f97316;
    --sa-orange-soft:#ffedd5;
    --sa-border:     #e2e8f0;
    --sa-dark:       #0f172a;
    --sa-muted:      #64748b;
    --sa-radius:     18px;
}

.sa-page {
    min-height: 100vh;
    padding: clamp(14px, 3vw, 28px);
    background: linear-gradient(160deg, #f0f7ff 0%, #f8fafc 50%, #fefce8 100%);
}
.sa-container { max-width: 1400px; margin: 0 auto;margin-left: 17%; right: 0; }

/* ── Hero ── */
.sa-hero {
    background: linear-gradient(135deg, #0a1628 0%, #1e3a8a 50%, #0f172a 100%);
    border-radius: clamp(16px, 3vw, 28px);
    padding: clamp(20px, 4vw, 36px);
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(245,158,11,0.2);
    box-shadow: 0 0 0 1px rgba(245,158,11,0.1), 0 30px 80px rgba(10,22,40,0.4);
}
.sa-hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 320px; height: 320px;
    background: radial-gradient(circle, rgba(245,158,11,0.12), transparent 70%);
    pointer-events: none;
}
.sa-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -60px;
    width: 250px; height: 250px;
    background: radial-gradient(circle, rgba(59,130,246,0.1), transparent 70%);
    pointer-events: none;
}
.sa-hero-inner {
    position: relative; z-index: 2;
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 20px; flex-wrap: wrap;
}
.sa-crown-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(245,158,11,0.15);
    border: 1px solid rgba(245,158,11,0.35);
    border-radius: 999px; padding: 6px 14px;
    font-size: 11px; font-weight: 800; color: var(--sa-gold);
    letter-spacing: .08em; text-transform: uppercase; margin-bottom: 12px;
}
.sa-hero h1 {
    margin: 0; font-size: clamp(22px, 4vw, 38px); font-weight: 900;
    color: white; letter-spacing: -.03em; line-height: 1.1;
}
.sa-hero p {
    margin: 10px 0 0; color: rgba(255,255,255,.65);
    font-size: clamp(13px, 1.5vw, 14px); line-height: 1.6; max-width: 560px;
}
.sa-hero-stats { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-start; }
.sa-stat-pill {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 14px; padding: 12px 16px; text-align: center; min-width: 80px;
}
.sa-stat-pill .val { font-size: clamp(18px, 3vw, 24px); font-weight: 900; color: white; line-height: 1; }
.sa-stat-pill .lbl { font-size: 10px; font-weight: 700; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .08em; margin-top: 4px; }

/* ── Section title ── */
.sa-section-title { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; margin-top: 28px; }
.sa-section-title h2 { margin: 0; font-size: clamp(14px, 2vw, 17px); font-weight: 800; color: var(--sa-dark); }
.sa-section-divider { flex: 1; height: 1px; background: linear-gradient(to right, var(--sa-border), transparent); }
.sa-section-icon { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }

/* ── Grids ── */
.sa-grid { display: grid; gap: 14px; }
.sa-grid-3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
.sa-grid-4 { grid-template-columns: repeat(4, minmax(0,1fr)); }
.sa-grid-2 { grid-template-columns: repeat(2, minmax(0,1fr)); }

/* ── Cards ── */
.sa-card {
    background: white; border: 1px solid var(--sa-border); border-radius: var(--sa-radius);
    padding: clamp(16px, 2.5vw, 22px); cursor: pointer; transition: all .2s ease;
    position: relative; overflow: hidden; text-decoration: none;
    display: flex; flex-direction: column; gap: 12px;
    box-shadow: 0 2px 12px rgba(15,23,42,.04);
}
.sa-card:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(15,23,42,.1); border-color: transparent; }
.sa-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--card-accent, #e2e8f0); }
.sa-card-gold   { --card-accent: linear-gradient(90deg, #f59e0b, #fcd34d); }
.sa-card-blue   { --card-accent: linear-gradient(90deg, #3b82f6, #93c5fd); }
.sa-card-green  { --card-accent: linear-gradient(90deg, #10b981, #6ee7b7); }
.sa-card-red    { --card-accent: linear-gradient(90deg, #ef4444, #fca5a5); }
.sa-card-purple { --card-accent: linear-gradient(90deg, #8b5cf6, #c4b5fd); }
.sa-card-cyan   { --card-accent: linear-gradient(90deg, #06b6d4, #a5f3fc); }
.sa-card-orange { --card-accent: linear-gradient(90deg, #f97316, #fdba74); }
.sa-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.sa-card-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.sa-card-badge { font-size: 10px; font-weight: 800; padding: 4px 9px; border-radius: 999px; white-space: nowrap; }
.sa-card-title { font-size: clamp(14px, 2vw, 16px); font-weight: 800; color: var(--sa-dark); line-height: 1.2; }
.sa-card-desc { font-size: 12px; color: var(--sa-muted); line-height: 1.5; flex: 1; }
.sa-card-footer { display: flex; align-items: center; justify-content: space-between; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; color: var(--sa-muted); }
.sa-card-arrow { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: var(--sa-muted); transition: all .2s; font-size: 12px; }
.sa-card:hover .sa-card-arrow { background: var(--sa-dark); color: white; }
.sa-card-danger-zone { background: #fff5f5; border-color: #fecaca; }
.sa-card-danger-zone:hover { background: #fff1f1; border-color: #f87171; box-shadow: 0 12px 35px rgba(239,68,68,.12); }

/* ── Modals ── */
.sa-modal-overlay {
    position: fixed; inset: 0; background: rgba(10,22,40,.6);
    backdrop-filter: blur(6px); display: none; align-items: center;
    justify-content: center; z-index: 10000; padding: 16px;
}
.sa-modal-overlay.active { display: flex; }
.sa-modal {
    width: min(640px, 100%); background: white; border-radius: 22px;
    box-shadow: 0 40px 100px rgba(10,22,40,.3); overflow: hidden;
    animation: saModalIn .22s cubic-bezier(0.34,1.56,0.64,1);
    max-height: 90vh; display: flex; flex-direction: column;
}
@keyframes saModalIn { from { opacity: 0; transform: scale(.92) translateY(12px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.sa-modal-header { padding: 20px 22px; border-bottom: 1px solid var(--sa-border); display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-shrink: 0; }
.sa-modal-header h3 { margin: 0; font-size: 17px; font-weight: 900; color: var(--sa-dark); display: flex; align-items: center; gap: 10px; }
.sa-modal-close { width: 34px; height: 34px; border-radius: 10px; border: none; background: #f1f5f9; color: #64748b; cursor: pointer; display: grid; place-items: center; transition: all .15s; flex-shrink: 0; }
.sa-modal-close:hover { background: #e2e8f0; color: #334155; }
.sa-modal-body { padding: 22px; overflow-y: auto; flex: 1; }
.sa-modal-footer { padding: 16px 22px; border-top: 1px solid var(--sa-border); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; }

/* ── Option cards ── */
.sa-option-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 8px; }
.sa-option-card {
    border: 2px solid var(--sa-border); border-radius: 14px; padding: 16px; cursor: pointer;
    transition: all .2s; text-align: center; background: white; text-decoration: none;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.sa-option-card:hover { border-color: var(--sa-gold); background: var(--sa-gold-soft); transform: translateY(-2px); box-shadow: 0 6px 20px var(--sa-gold-glow); }
.sa-option-card i { font-size: 22px; margin-bottom: 2px; }
.opt-title { font-size: 13px; font-weight: 800; color: var(--sa-dark); }
.opt-desc  { font-size: 11px; color: var(--sa-muted); line-height: 1.4; }

/* ── Mini table ── */
.sa-mini-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.sa-mini-table th { background: #f8fafc; color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--sa-border); }
.sa-mini-table td { padding: 11px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
.sa-mini-table tr:last-child td { border-bottom: none; }
.sa-mini-table tbody tr:hover td { background: #fafbfc; }

/* ── Badges ── */
.sa-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; }
.sa-badge-green  { background: var(--sa-green-soft); color: #065f46; }
.sa-badge-red    { background: var(--sa-red-soft); color: #991b1b; }
.sa-badge-gold   { background: var(--sa-gold-soft); color: #92400e; border: 1px solid rgba(245,158,11,.2); }
.sa-badge-blue   { background: var(--sa-blue-soft); color: #1e40af; }
.sa-badge-purple { background: var(--sa-purple-soft); color: #5b21b6; }
.sa-badge-gray   { background: #f1f5f9; color: #475569; }

/* ── Buttons ── */
.sa-btn { border: none; border-radius: 11px; padding: 10px 16px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; transition: all .18s ease; text-decoration: none; white-space: nowrap; }
.sa-btn-primary { background: var(--sa-dark); color: white; }
.sa-btn-primary:hover { background: #1e293b; }
.sa-btn-gold    { background: var(--sa-gold); color: #78350f; }
.sa-btn-gold:hover { background: var(--sa-gold-dark); color: white; }
.sa-btn-soft    { background: #f1f5f9; color: #334155; }
.sa-btn-soft:hover { background: #e2e8f0; }
.sa-btn-danger  { background: var(--sa-red); color: white; }
.sa-btn-danger:hover { background: #dc2626; }
.sa-btn-green   { background: var(--sa-green); color: white; }
.sa-btn-green:hover { background: #059669; }
.sa-btn-sm { padding: 7px 12px; font-size: 12px; border-radius: 9px; }

/* ── Danger warning ── */
.sa-danger-warning { background: #fff5f5; border: 1px solid #fecaca; border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: #991b1b; line-height: 1.5; }
.sa-confirm-input { width: 100%; border: 1.5px solid var(--sa-border); border-radius: 10px; padding: 10px 13px; font-size: 14px; outline: none; box-sizing: border-box; margin-top: 8px; transition: border-color .2s; }
.sa-confirm-input:focus { border-color: var(--sa-red); box-shadow: 0 0 0 3px rgba(239,68,68,.08); }

/* ── Perf bars ── */
.sa-perf-bar { height: 8px; background: #f1f5f9; border-radius: 999px; overflow: hidden; margin-top: 6px; }
.sa-perf-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #10b981, #34d399); transition: width .8s ease; }
.sa-perf-fill.warn   { background: linear-gradient(90deg, #f59e0b, #fcd34d); }
.sa-perf-fill.danger { background: linear-gradient(90deg, #ef4444, #f87171); }

/* ── Responsive ── */
@media (max-width: 1100px) { .sa-grid-4 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
@media (max-width: 768px)  { .sa-grid-3 { grid-template-columns: repeat(2, minmax(0,1fr)); } .sa-grid-2 { grid-template-columns: 1fr; } .sa-hero-inner { flex-direction: column; } .sa-option-grid { grid-template-columns: 1fr; } }
@media (max-width: 480px)  { .sa-grid-3, .sa-grid-4 { grid-template-columns: 1fr; } .sa-modal-footer { flex-direction: column; } .sa-modal-footer .sa-btn { width: 100%; justify-content: center; } }
</style>

@php
    $totalUsers   = \App\Models\User::count();
    $activeUsers  = \App\Models\User::where('is_active', true)->count();
    $adminUsers   = \App\Models\User::where('is_admin', true)->count();
    $premiumUsers = \App\Models\User::whereIn('plan', ['premium','enterprise'])->count();
    $recentLogins = \App\Models\User::whereNotNull('last_login_at')
                        ->orderByDesc('last_login_at')->take(10)->get();
@endphp

<div class="sa-page">
<div class="sa-container">

    {{-- ── Hero ── --}}
    <div class="sa-hero">
        <div class="sa-hero-inner">
            <div>
                <div class="sa-crown-badge">
                    <i class="fa-solid fa-crown"></i> Panneau Superadmin
                </div>
                <h1>Centre de contrôle total</h1>
                <p>Gestion complète de l'application Data 360 — utilisateurs, paiements, base de données, performances et historiques.</p>
            </div>
            <div class="sa-hero-stats">
                <div class="sa-stat-pill">
                    <div class="val">{{ $totalUsers }}</div>
                    <div class="lbl">Utilisateurs</div>
                </div>
                <div class="sa-stat-pill">
                    <div class="val">{{ $premiumUsers }}</div>
                    <div class="lbl">Premium</div>
                </div>
                <div class="sa-stat-pill">
                    <div class="val">{{ $activeUsers }}</div>
                    <div class="lbl">Actifs</div>
                </div>
                <div class="sa-stat-pill">
                    <div class="val" id="heroTime" style="font-size:16px;">--:--</div>
                    <div class="lbl">Heure</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ SECTION 1 — GESTION ═══ --}}
    <div class="sa-section-title">
        <div class="sa-section-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-users-gear"></i></div>
        <h2>Gestion des utilisateurs</h2>
        <div class="sa-section-divider"></div>
    </div>

    <div class="sa-grid sa-grid-4">
        <div class="sa-card sa-card-blue" onclick="openModal('modal-all-users')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-users"></i></div>
                <span class="sa-card-badge" style="background:#dbeafe;color:#1e40af;">{{ $totalUsers }} total</span>
            </div>
            <div class="sa-card-title">Tous les utilisateurs</div>
            <div class="sa-card-desc">Gérer, modifier, suspendre ou supprimer tous les comptes incluant admins et superadmins.</div>
            <div class="sa-card-footer"><span>Accès complet</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-gold" onclick="openModal('modal-admins')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#fffbeb;color:#f59e0b;"><i class="fa-solid fa-user-shield"></i></div>
                <span class="sa-card-badge" style="background:#fffbeb;color:#92400e;">{{ $adminUsers }} admins</span>
            </div>
            <div class="sa-card-title">Gestion des admins</div>
            <div class="sa-card-desc">Promouvoir, rétrograder, gérer les droits des administrateurs.</div>
            <div class="sa-card-footer"><span>Droits & rôles</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-purple" onclick="openModal('modal-credits')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-coins"></i></div>
                <span class="sa-card-badge" style="background:#ede9fe;color:#5b21b6;">Crédits</span>
            </div>
            <div class="sa-card-title">Gestion des crédits</div>
            <div class="sa-card-desc">Attribuer, retirer ou réinitialiser les crédits de recherche par utilisateur ou en masse.</div>
            <div class="sa-card-footer"><span>Distribution crédits</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-green" onclick="openModal('modal-plans')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-layer-group"></i></div>
                <span class="sa-card-badge" style="background:#d1fae5;color:#065f46;">{{ $premiumUsers }} premium</span>
            </div>
            <div class="sa-card-title">Gestion des plans</div>
            <div class="sa-card-desc">Modifier les abonnements free/premium/enterprise de chaque utilisateur.</div>
            <div class="sa-card-footer"><span>Abonnements</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>
    </div>

    {{-- ═══ SECTION 2 — HISTORIQUES ═══ --}}
    <div class="sa-section-title" style="margin-top:28px;">
        <div class="sa-section-icon" style="background:#cffafe;color:#0891b2;"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <h2>Historiques & Activité</h2>
        <div class="sa-section-divider"></div>
    </div>

    <div class="sa-grid sa-grid-3">
        <div class="sa-card sa-card-cyan" onclick="openModal('modal-connexions')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#cffafe;color:#0891b2;"><i class="fa-solid fa-right-to-bracket"></i></div>
                <span class="sa-card-badge" style="background:#cffafe;color:#164e63;">Temps réel</span>
            </div>
            <div class="sa-card-title">Historique des connexions</div>
            <div class="sa-card-desc">Voir qui s'est connecté, quand, depuis quelle IP et leur statut actuel (en ligne / hors ligne).</div>
            <div class="sa-card-footer"><span>Sessions utilisateurs</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-blue" onclick="openModal('modal-recherches-hist')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
                <span class="sa-card-badge" style="background:#dbeafe;color:#1e40af;">Recherches</span>
            </div>
            <div class="sa-card-title">Historique des recherches</div>
            <div class="sa-card-desc">Toutes les recherches effectuées, par qui, quand, avec quel résultat.</div>
            <div class="sa-card-footer"><span>Logs de recherche</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-orange" onclick="openModal('modal-imports-hist')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#ffedd5;color:#ea580c;"><i class="fa-solid fa-file-csv"></i></div>
                <span class="sa-card-badge" style="background:#ffedd5;color:#9a3412;">Imports CSV</span>
            </div>
            <div class="sa-card-title">Historique des imports</div>
            <div class="sa-card-desc">Tous les traitements CSV — statuts, erreurs, volumes, téléchargements.</div>
            <div class="sa-card-footer"><span>Traitements batch</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>
    </div>

    {{-- ═══ SECTION 3 — PAIEMENTS ═══ --}}
    <div class="sa-section-title" style="margin-top:28px;">
        <div class="sa-section-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-credit-card"></i></div>
        <h2>Paiements & Transactions</h2>
        <div class="sa-section-divider"></div>
    </div>

    <div class="sa-grid sa-grid-3">
        <div class="sa-card sa-card-green" onclick="openModal('modal-transactions')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-receipt"></i></div>
                <span class="sa-card-badge" style="background:#d1fae5;color:#065f46;">Stripe</span>
            </div>
            <div class="sa-card-title">Transactions & Paiements</div>
            <div class="sa-card-desc">Voir toutes les transactions Stripe, montants, statuts et remboursements.</div>
            <div class="sa-card-footer"><span>Historique financier</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-purple" onclick="openModal('modal-abonnements')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-rotate"></i></div>
                <span class="sa-card-badge" style="background:#ede9fe;color:#5b21b6;">Récurrents</span>
            </div>
            <div class="sa-card-title">Abonnements actifs</div>
            <div class="sa-card-desc">Gérer les abonnements en cours, renouvellements et annulations.</div>
            <div class="sa-card-footer"><span>Plans actifs</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-gold" onclick="openModal('modal-credits-hist')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#fffbeb;color:#f59e0b;"><i class="fa-solid fa-coins"></i></div>
                <span class="sa-card-badge" style="background:#fffbeb;color:#92400e;">Crédits</span>
            </div>
            <div class="sa-card-title">Historique des crédits</div>
            <div class="sa-card-desc">Toutes les attributions, consommations et retraits de crédits.</div>
            <div class="sa-card-footer"><span>Mouvements crédits</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>
    </div>

    {{-- ═══ SECTION 4 — MAINTENANCE BDD ═══ --}}
    <div class="sa-section-title" style="margin-top:28px;">
        <div class="sa-section-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-database"></i></div>
        <h2>Maintenance Base de données</h2>
        <div class="sa-section-divider"></div>
    </div>

    <div class="sa-grid sa-grid-4">
        <div class="sa-card sa-card-danger-zone" onclick="openModal('modal-purge-recherches')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-broom"></i></div>
                <span class="sa-card-badge" style="background:#fee2e2;color:#991b1b;">Purge</span>
            </div>
            <div class="sa-card-title">Purger les recherches</div>
            <div class="sa-card-desc">Supprimer les anciennes recherches pour libérer l'espace en base Railway.</div>
            <div class="sa-card-footer"><span>Libérer espace</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right" style="color:#ef4444;"></i></div></div>
        </div>

        <div class="sa-card sa-card-danger-zone" onclick="openModal('modal-purge-imports')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-file-circle-xmark"></i></div>
                <span class="sa-card-badge" style="background:#fee2e2;color:#991b1b;">Purge CSV</span>
            </div>
            <div class="sa-card-title">Purger les imports CSV</div>
            <div class="sa-card-desc">Vider les colonnes csv_content et xlsx_content des anciens imports terminés.</div>
            <div class="sa-card-footer"><span>Nettoyage LONGTEXT</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right" style="color:#ef4444;"></i></div></div>
        </div>

        <div class="sa-card sa-card-danger-zone" onclick="openModal('modal-purge-sessions')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-cookie-bite"></i></div>
                <span class="sa-card-badge" style="background:#fee2e2;color:#991b1b;">Sessions</span>
            </div>
            <div class="sa-card-title">Vider les sessions</div>
            <div class="sa-card-desc">Supprimer toutes les sessions expirées de la table sessions en base.</div>
            <div class="sa-card-footer"><span>Table sessions</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right" style="color:#ef4444;"></i></div></div>
        </div>

        <div class="sa-card sa-card-danger-zone" onclick="openModal('modal-purge-logs')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-scroll"></i></div>
                <span class="sa-card-badge" style="background:#fee2e2;color:#991b1b;">Logs</span>
            </div>
            <div class="sa-card-title">Vider les logs</div>
            <div class="sa-card-desc">Supprimer les anciens logs applicatifs Laravel pour alléger le stockage.</div>
            <div class="sa-card-footer"><span>storage/logs/</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right" style="color:#ef4444;"></i></div></div>
        </div>
    </div>

    {{-- ═══ SECTION 5 — PERFORMANCES ═══ --}}
    <div class="sa-section-title" style="margin-top:28px;">
        <div class="sa-section-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-gauge-high"></i></div>
        <h2>Contrôle des performances</h2>
        <div class="sa-section-divider"></div>
    </div>

    <div class="sa-grid sa-grid-3">
        <div class="sa-card sa-card-green" onclick="openModal('modal-perf-bdd')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-database"></i></div>
                <span class="sa-card-badge sa-badge-green" id="dbStatusBadge">Vérification…</span>
            </div>
            <div class="sa-card-title">Santé de la base de données</div>
            <div class="sa-card-desc">Taille des tables, nombre de lignes, colonnes volumineuses.</div>
            <div style="margin-top:4px;">
                <div style="font-size:11px;color:#64748b;">Occupation</div>
                <div class="sa-perf-bar"><div class="sa-perf-fill" style="width:0%" id="dbFill"></div></div>
            </div>
            <div class="sa-card-footer"><span>Railway MySQL</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-blue" onclick="openModal('modal-perf-queue')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-list-check"></i></div>
                <span class="sa-card-badge" style="background:#dbeafe;color:#1e40af;" id="queueBadge">Queue</span>
            </div>
            <div class="sa-card-title">File d'attente (Queue)</div>
            <div class="sa-card-desc">Jobs en attente, en cours d'exécution et jobs échoués dans la table jobs.</div>
            <div class="sa-card-footer"><span>Worker Railway</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-orange" onclick="openModal('modal-cache')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#ffedd5;color:#ea580c;"><i class="fa-solid fa-bolt"></i></div>
                <span class="sa-card-badge" style="background:#ffedd5;color:#9a3412;">Cache</span>
            </div>
            <div class="sa-card-title">Gestion du cache</div>
            <div class="sa-card-desc">Vider le cache applicatif, config, routes et vues pour forcer un rechargement propre.</div>
            <div class="sa-card-footer"><span>Artisan cache</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>
    </div>

    {{-- ═══ SECTION 6 — EXTRAS ═══ --}}
    <div class="sa-section-title" style="margin-top:28px;">
        <div class="sa-section-icon" style="background:#fffbeb;color:#d97706;"><i class="fa-solid fa-crown"></i></div>
        <h2>Outils Superadmin</h2>
        <div class="sa-section-divider"></div>
    </div>

    <div class="sa-grid sa-grid-4">
        <div class="sa-card sa-card-orange" onclick="openModal('modal-maintenance')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#ffedd5;color:#ea580c;"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                <span class="sa-card-badge" style="background:#ffedd5;color:#9a3412;">Mode</span>
            </div>
            <div class="sa-card-title">Mode Maintenance</div>
            <div class="sa-card-desc">Activer ou désactiver le mode maintenance de l'application.</div>
            <div class="sa-card-footer"><span>php artisan down/up</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-purple" onclick="openModal('modal-notifications')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-regular fa-bell"></i></div>
                <span class="sa-card-badge" style="background:#ede9fe;color:#5b21b6;">Broadcast</span>
            </div>
            <div class="sa-card-title">Notification globale</div>
            <div class="sa-card-desc">Envoyer une notification à tous les utilisateurs ou un groupe ciblé.</div>
            <div class="sa-card-footer"><span>All users</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-blue" onclick="openModal('modal-user-growth')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa-solid fa-chart-line"></i></div>
                <span class="sa-card-badge" style="background:#dbeafe;color:#1e40af;">Stats</span>
            </div>
            <div class="sa-card-title">Croissance utilisateurs</div>
            <div class="sa-card-desc">Statistiques d'inscription, distribution des plans, santé des comptes.</div>
            <div class="sa-card-footer"><span>6 derniers mois</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>

        <div class="sa-card sa-card-green" onclick="openModal('modal-export')">
            <div class="sa-card-header">
                <div class="sa-card-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-download"></i></div>
                <span class="sa-card-badge" style="background:#d1fae5;color:#065f46;">Export</span>
            </div>
            <div class="sa-card-title">Exports de données</div>
            <div class="sa-card-desc">Exporter les utilisateurs, imports et stats en CSV ou JSON.</div>
            <div class="sa-card-footer"><span>CSV / JSON</span><div class="sa-card-arrow"><i class="fa-solid fa-arrow-right"></i></div></div>
        </div>
    </div>

    <div style="height:40px;"></div>

</div>
</div>

{{-- ═══════════════════════════════════════════════════════
     MODAL INLINE : Tous les utilisateurs
═══════════════════════════════════════════════════════ --}}
<div id="modal-all-users" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-users" style="color:#3b82f6;"></i> Gestion des utilisateurs</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-all-users')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sa-modal-body">
            <p style="color:#64748b;font-size:13px;margin-bottom:16px;">Choisissez une action à effectuer sur les utilisateurs.</p>
            <div class="sa-option-grid">
                <a href="{{ route('admin.superadmin.users.index') }}" class="sa-option-card">
                    <i class="fa-solid fa-list" style="color:#3b82f6;"></i>
                    <div class="opt-title">Tous les comptes</div>
                    <div class="opt-desc">Liste complète superadmin avec toutes les actions</div>
                </a>
                <a href="{{ route('admin.security.users.create') }}" class="sa-option-card">
                    <i class="fa-solid fa-user-plus" style="color:#10b981;"></i>
                    <div class="opt-title">Créer un utilisateur</div>
                    <div class="opt-desc">Nouveau compte manuel</div>
                </a>
                <a href="{{ route('admin.security.blocked.index') }}" class="sa-option-card">
                    <i class="fa-solid fa-ban" style="color:#ef4444;"></i>
                    <div class="opt-title">Identités bloquées</div>
                    <div class="opt-desc">IPs et comptes bannis</div>
                </a>
                <div class="sa-option-card" onclick="closeModal('modal-all-users');openModal('modal-connexions');">
                    <i class="fa-solid fa-right-to-bracket" style="color:#06b6d4;"></i>
                    <div class="opt-title">Connexions récentes</div>
                    <div class="opt-desc">Voir qui est en ligne</div>
                </div>
            </div>
        </div>
        <div class="sa-modal-footer">
            <a href="{{ route('admin.superadmin.users.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-arrow-right"></i> Gestion complète
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-all-users')">Fermer</button>
        </div>
    </div>
</div>

{{-- MODAL INLINE : Connexions (données PHP inline) --}}
<div id="modal-connexions" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(780px,100%);">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-right-to-bracket" style="color:#06b6d4;"></i> Historique des connexions</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-connexions')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sa-modal-body">
            <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                <span class="sa-badge sa-badge-green"><i class="fa-solid fa-circle" style="font-size:7px;"></i> En ligne (< 30 min)</span>
                <span class="sa-badge sa-badge-gray"><i class="fa-regular fa-circle" style="font-size:7px;"></i> Hors ligne</span>
                <span class="sa-badge sa-badge-gold"><i class="fa-solid fa-crown"></i> Superadmin</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="sa-mini-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Plan</th>
                            <th>Dernière connexion</th>
                            <th>IP</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogins as $u)
                        @php $isOnline = $u->last_login_at && $u->last_login_at->diffInMinutes(now()) < 30; @endphp
                        <tr>
                            <td>
                                <div style="font-weight:700;font-size:13px;display:flex;align-items:center;gap:6px;">
                                    @if($u->isSuperAdmin()) 👑 @endif
                                    {{ $u->name }}
                                </div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $u->email }}</div>
                            </td>
                            <td>
                                <span class="sa-badge {{ in_array($u->plan,['premium','enterprise']) ? 'sa-badge-blue' : 'sa-badge-gray' }}">
                                    {{ $u->plan }}
                                </span>
                            </td>
                            <td style="font-size:12px;">
                                {{ optional($u->last_login_at)->format('d/m/Y H:i') ?? '—' }}
                                <div style="font-size:10px;color:#94a3b8;">{{ optional($u->last_login_at)->diffForHumans() ?? '' }}</div>
                            </td>
                            <td><code style="font-size:11px;background:#f1f5f9;padding:3px 6px;border-radius:6px;">{{ $u->last_login_ip ?? '—' }}</code></td>
                            <td>
                                @if($isOnline)
                                    <span class="sa-badge sa-badge-green"><i class="fa-solid fa-circle" style="font-size:7px;animation:pulse 2s infinite;"></i> En ligne</span>
                                @else
                                    <span class="sa-badge sa-badge-gray"><i class="fa-regular fa-circle" style="font-size:7px;"></i> Hors ligne</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Aucune connexion enregistrée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="sa-modal-footer">
            <a href="{{ route('admin.superadmin.users.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-users"></i> Gérer les comptes
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-connexions')">Fermer</button>
        </div>
    </div>
</div>

{{-- MODAL INLINE : Recherches historique --}}
<div id="modal-recherches-hist" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-magnifying-glass-chart" style="color:#3b82f6;"></i> Historique des recherches</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-recherches-hist')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sa-modal-body">
            <div class="sa-option-grid">
                <a href="{{ route('back.recherches.index') }}" class="sa-option-card">
                    <i class="fa-solid fa-list" style="color:#3b82f6;"></i>
                    <div class="opt-title">Voir toutes les recherches</div>
                    <div class="opt-desc">Page dédiée backoffice</div>
                </a>
                <div class="sa-option-card" onclick="closeModal('modal-recherches-hist');openModal('modal-purge-recherches');">
                    <i class="fa-solid fa-broom" style="color:#ef4444;"></i>
                    <div class="opt-title">Purger les recherches</div>
                    <div class="opt-desc">Libérer l'espace en base</div>
                </div>
                <div class="sa-option-card" onclick="closeModal('modal-recherches-hist');openModal('modal-perf-bdd');">
                    <i class="fa-solid fa-database" style="color:#10b981;"></i>
                    <div class="opt-title">Stats base de données</div>
                    <div class="opt-desc">Volume de la table recherches</div>
                </div>
                <div class="sa-option-card" onclick="closeModal('modal-recherches-hist');openModal('modal-user-growth');">
                    <i class="fa-solid fa-chart-line" style="color:#8b5cf6;"></i>
                    <div class="opt-title">Statistiques globales</div>
                    <div class="opt-desc">Croissance et activité</div>
                </div>
            </div>
        </div>
        <div class="sa-modal-footer">
            <a href="{{ route('back.recherches.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-arrow-right"></i> Accéder
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-recherches-hist')">Fermer</button>
        </div>
    </div>
</div>

{{-- MODAL INLINE : Purge DB avec AJAX réel --}}
<div id="modal-purge-recherches" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-broom" style="color:#ef4444;"></i> Purger les recherches</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-recherches')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sa-modal-body">
            <div class="sa-danger-warning">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:18px;flex-shrink:0;margin-top:1px;"></i>
                <div>Action <strong>irréversible</strong>. Les recherches supprimées ne pourront pas être récupérées.</div>
            </div>
            <div class="sa-option-grid">
                @foreach([['7days','+ 7 jours','#f59e0b'],['30days','+ 30 jours','#f97316'],['90days','+ 90 jours','#ef4444'],['all','Tout supprimer','#dc2626']] as $opt)
                <div class="sa-option-card" onclick="setPurgeOption('recherches','{{ $opt[0] }}',this)">
                    <i class="fa-solid fa-calendar" style="color:{{ $opt[2] }};font-size:20px;"></i>
                    <div class="opt-title">{{ $opt[1] }}</div>
                    <div class="opt-desc">Supprimer les recherches de plus de {{ $opt[1] }}</div>
                </div>
                @endforeach
            </div>
            <div id="purge-recherches-confirm" style="display:none;margin-top:16px;">
                <label style="font-size:13px;font-weight:700;color:#334155;">Tapez <strong>CONFIRMER</strong> pour valider :</label>
                <input type="text" class="sa-confirm-input" id="purge-recherches-input" placeholder="CONFIRMER">
            </div>
            <div id="purge-recherches-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>
        </div>
        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-danger sa-btn-sm" id="purge-recherches-btn" disabled onclick="executePurge('recherches')">
                <i class="fa-solid fa-broom"></i> Purger
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-purge-recherches')">Annuler</button>
        </div>
    </div>
</div>

<div id="modal-purge-imports" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-file-circle-xmark" style="color:#ef4444;"></i> Purger les imports CSV</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-imports')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sa-modal-body">
            <div class="sa-danger-warning">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:18px;flex-shrink:0;margin-top:1px;"></i>
                <div>Cette action vide <code>csv_content</code> et <code>xlsx_content</code>. Les fichiers XLSX ne pourront plus être téléchargés.</div>
            </div>
            <div class="sa-option-grid">
                @foreach([['terminated','Imports terminés','#10b981'],['older30','+ 30 jours','#f97316'],['all','Tout vider','#ef4444'],['delete_all','Supprimer les lignes','#dc2626']] as $opt)
                <div class="sa-option-card" onclick="setPurgeOption('imports','{{ $opt[0] }}',this)">
                    <i class="fa-solid fa-file-circle-xmark" style="color:{{ $opt[2] }};font-size:20px;"></i>
                    <div class="opt-title">{{ $opt[1] }}</div>
                </div>
                @endforeach
            </div>
            <div id="purge-imports-confirm" style="display:none;margin-top:16px;">
                <label style="font-size:13px;font-weight:700;color:#334155;">Tapez <strong>CONFIRMER</strong> pour valider :</label>
                <input type="text" class="sa-confirm-input" id="purge-imports-input" placeholder="CONFIRMER">
            </div>
            <div id="purge-imports-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>
        </div>
        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-danger sa-btn-sm" id="purge-imports-btn" disabled onclick="executePurge('imports')">
                <i class="fa-solid fa-broom"></i> Purger
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-purge-imports')">Annuler</button>
        </div>
    </div>
</div>

{{-- MODAL INLINE : Cache avec AJAX réel --}}
<div id="modal-cache" class="sa-modal-overlay">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-bolt" style="color:#f97316;"></i> Gestion du cache</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-cache')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sa-modal-body">
            <p style="color:#64748b;font-size:13px;margin-bottom:16px;">Exécutez des commandes Artisan. Recommandé après chaque déploiement.</p>
            <div class="sa-option-grid">
                @foreach([
                    ['cache:clear',   'Cache applicatif', '#f97316', 'fa-bolt'],
                    ['config:clear',  'Cache config',     '#3b82f6', 'fa-gear'],
                    ['route:clear',   'Cache routes',     '#8b5cf6', 'fa-route'],
                    ['optimize:clear','Tout vider',       '#10b981', 'fa-rocket'],
                ] as $cmd)
                <div class="sa-option-card" onclick="runArtisan('{{ $cmd[0] }}')">
                    <i class="fa-solid {{ $cmd[3] }}" style="color:{{ $cmd[2] }};font-size:20px;"></i>
                    <div class="opt-title">{{ $cmd[1] }}</div>
                    <div class="opt-desc"><code style="font-size:10px;">php artisan {{ $cmd[0] }}</code></div>
                </div>
                @endforeach
            </div>
            <div id="artisan-terminal" style="display:none;margin-top:14px;background:#0f172a;border-radius:12px;padding:14px;font-family:monospace;font-size:12px;color:#94a3b8;">
                <div style="display:flex;gap:6px;margin-bottom:8px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                    <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>
                    <span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                    <span style="color:#475569;font-size:10px;margin-left:4px;">Terminal</span>
                </div>
                <div id="artisan-output"></div>
            </div>
        </div>
        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-cache')">Fermer</button>
        </div>
    </div>
</div>

{{-- MODAL INLINE : Perf BDD avec AJAX réel --}}
<div id="modal-perf-bdd" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(720px,100%);">
        <div class="sa-modal-header">
            <h3><i class="fa-solid fa-database" style="color:#10b981;"></i> Santé de la base de données</h3>
            <button class="sa-modal-close" onclick="closeModal('modal-perf-bdd')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sa-modal-body">
            <div id="db-stats-loading" style="text-align:center;padding:30px;color:#94a3b8;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px;"></i>
                <div style="margin-top:10px;font-size:13px;">Analyse de la base Railway…</div>
            </div>
            <div id="db-stats-content" style="display:none;">
                <div id="db-summary" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;"></div>
                <div style="overflow-x:auto;">
                    <table class="sa-mini-table">
                        <thead><tr>
                            <th>Table</th><th>Lignes</th><th>Taille</th><th>Volume</th>
                        </tr></thead>
                        <tbody id="db-stats-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-green sa-btn-sm" onclick="loadDbStats()">
                <i class="fa-solid fa-rotate"></i> Actualiser
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-perf-bdd')">Fermer</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ÉTAPE 1 — Tous les partials complets
═══════════════════════════════════════════════════════ --}}
@include('back.security.superadmin.partials.modal-admins')
@include('back.security.superadmin.partials.modal-credits')
@include('back.security.superadmin.partials.modal-plans')
@include('back.security.superadmin.partials.modal-imports-hist')
@include('back.security.superadmin.partials.modal-transactions')
@include('back.security.superadmin.partials.modal-abonnements')
@include('back.security.superadmin.partials.modal-credits-hist')
@include('back.security.superadmin.partials.modal-perf-queue')
@include('back.security.superadmin.partials.modal-cache')
@include('back.security.superadmin.partials.modal-notifications')
@include('back.security.superadmin.partials.modal-maintenance')
@include('back.security.superadmin.partials.modal-perf-bdd')
@include('back.security.superadmin.partials.modal-purge-bdd')
@include('back.security.superadmin.partials.modal-purge-sessions')
@include('back.security.superadmin.partials.modal-purge-logs')
@include('back.security.superadmin.partials.modal-purge-cache')
@include('back.security.superadmin.partials.modal-purge-imports')
@include('back.security.superadmin.partials.modal-purge-recherches')
@include('back.security.superadmin.partials.modal-user-growth')
@include('back.security.superadmin.partials.modal-export')

{{-- ═══════════════════════════════════════════════════════
     JAVASCRIPT — Étapes 2 & 3 : AJAX réel sur toutes les actions
═══════════════════════════════════════════════════════ --}}
<script>
// ── Routes Laravel exposées au JS ──
const SA_ROUTES = {
    purgeRecherches: "{{ route('admin.superadmin.purge.recherches') }}",
    purgeImports:    "{{ route('admin.superadmin.purge.imports') }}",
    purgeSessions:   "{{ route('admin.superadmin.purge.sessions') }}",
    purgeLogs:       "{{ route('admin.superadmin.purge.logs') }}",
    clearCache:      "{{ route('admin.superadmin.cache.clear') }}",
    dbStats:         "{{ route('admin.superadmin.metrics.db-stats') }}",
    queueStats:      "{{ route('admin.superadmin.metrics.queue') }}",
    performance:     "{{ route('admin.superadmin.metrics.performance') }}",
};

const SA_CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Helpers fetch ──
function saPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': SA_CSRF },
        body: JSON.stringify(data),
    }).then(r => r.json());
}

function saGet(url) {
    return fetch(url).then(r => r.json());
}

// ── Modal open/close ──
function openModal(id) {
    document.getElementById(id)?.classList.add('active');
    document.body.style.overflow = 'hidden';
    // Auto-chargements
    if (id === 'modal-perf-bdd')   setTimeout(loadDbStats, 300);
    if (id === 'modal-perf-queue') setTimeout(() => { if (typeof loadQueueStats === 'function') loadQueueStats(); }, 300);
    if (id === 'modal-maintenance') setTimeout(() => { if (typeof loadMaintenanceStatus === 'function') loadMaintenanceStatus(); }, 300);
    if (id === 'modal-purge-logs') setTimeout(() => { if (typeof loadLogsInfo === 'function') loadLogsInfo(); }, 300);
}

function closeModal(id) {
    document.getElementById(id)?.classList.remove('active');
    document.body.style.overflow = '';
}

document.querySelectorAll('.sa-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(overlay.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.sa-modal-overlay.active').forEach(m => closeModal(m.id));
});

// ── Horloge ──
function updateHeroTime() {
    const el = document.getElementById('heroTime');
    if (el) el.textContent = new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
}
updateHeroTime();
setInterval(updateHeroTime, 10000);

// ── ÉTAPE 2 : Purge AJAX réel ──
const purgeState = {};

function setPurgeOption(type, value, el) {
    purgeState[type] = value;
    el.closest('.sa-option-grid').querySelectorAll('.sa-option-card').forEach(c => {
        c.style.borderColor = '';
        c.style.background  = '';
    });
    el.style.borderColor = '#ef4444';
    el.style.background  = '#fff5f5';

    const confirmDiv = document.getElementById(`purge-${type}-confirm`);
    const btn        = document.getElementById(`purge-${type}-btn`);
    const input      = document.getElementById(`purge-${type}-input`);

    if (confirmDiv) confirmDiv.style.display = 'block';
    if (btn)        btn.disabled = true;

    if (input) {
        input.value = '';
        input.oninput = () => { if (btn) btn.disabled = input.value.trim() !== 'CONFIRMER'; };
    }
}

function executePurge(type) {
    const val   = purgeState[type];
    const input = document.getElementById(`purge-${type}-input`);
    const btn   = document.getElementById(`purge-${type}-btn`);
    const result= document.getElementById(`purge-${type}-result`);

    if (!val || input?.value?.trim() !== 'CONFIRMER') return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Purge en cours…';

    const routes = {
        recherches: SA_ROUTES.purgeRecherches,
        imports:    SA_ROUTES.purgeImports,
    };

    const body = { confirm: 'CONFIRMER' };
    if (type === 'recherches') body.period = val;
    if (type === 'imports')    body.mode   = val;

    saPost(routes[type], body)
        .then(data => {
            if (result) {
                result.style.display    = 'block';
                result.style.background = data.success ? '#f0fdf4' : '#fff5f5';
                result.style.border     = `1px solid ${data.success ? '#bbf7d0' : '#fecaca'}`;
                result.style.color      = data.success ? '#166534' : '#991b1b';
                result.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${data.message ?? (data.success ? 'Succès' : 'Erreur')}`;
            }
            btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger';
            if (input) { input.value = ''; btn.disabled = true; }
        })
        .catch(() => {
            btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger';
            btn.disabled  = false;
        });
}

// ── ÉTAPE 2 : Artisan AJAX réel ──
function runArtisan(cmd) {
    const terminal = document.getElementById('artisan-terminal');
    const output   = document.getElementById('artisan-output');

    if (terminal) terminal.style.display = 'block';
    if (output)   output.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${cmd}</span>\n<i class="fa-solid fa-circle-notch fa-spin"></i> Exécution…`;

    saPost(SA_ROUTES.clearCache, { command: cmd })
        .then(data => {
            if (output) output.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${cmd}</span>\n<span style="color:${data.success ? '#10b981' : '#ef4444'};">${data.success ? '✓ ' : '✗ '}${data.output || data.message || (data.success ? 'Succès' : 'Erreur')}</span>`;
        })
        .catch(() => {
            if (output) output.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${cmd}</span>\n<span style="color:#ef4444;">✗ Erreur réseau</span>`;
        });
}

// ── ÉTAPE 3 : DB Stats AJAX réel ──
function loadDbStats() {
    const loading = document.getElementById('db-stats-loading');
    const content = document.getElementById('db-stats-content');
    const summary = document.getElementById('db-summary');
    const tbody   = document.getElementById('db-stats-body');
    const badge   = document.getElementById('dbStatusBadge');
    const fill    = document.getElementById('dbFill');

    if (loading) loading.style.display = 'block';
    if (content) content.style.display = 'none';

    saGet(SA_ROUTES.dbStats)
        .then(data => {
            if (loading) loading.style.display = 'none';
            if (content) content.style.display = 'block';

            const totalMb = parseFloat(data.total_size_mb ?? 0);

            // Badge + barre
            if (badge) {
                badge.textContent      = totalMb + ' MB';
                badge.style.background = totalMb > 400 ? '#fee2e2' : '#d1fae5';
                badge.style.color      = totalMb > 400 ? '#991b1b' : '#065f46';
            }
            if (fill) {
                const pct = Math.min(100, (totalMb / 500) * 100);
                fill.style.width = pct + '%';
                fill.className   = 'sa-perf-fill' + (pct > 80 ? ' danger' : pct > 50 ? ' warn' : '');
            }

            // Summary cards
            if (summary) {
                summary.innerHTML = `
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px;text-align:center;">
                        <div style="font-size:20px;font-weight:900;color:#10b981;">${totalMb} MB</div>
                        <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Taille totale</div>
                    </div>
                    <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px;text-align:center;">
                        <div style="font-size:20px;font-weight:900;color:#3b82f6;">${(data.tables??[]).length}</div>
                        <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Tables</div>
                    </div>
                    <div style="background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:12px;text-align:center;">
                        <div style="font-size:13px;font-weight:800;color:#92400e;word-break:break-all;">${data.db_name ?? '—'}</div>
                        <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Database</div>
                    </div>
                `;
            }

            // Tableau tables
            if (tbody) {
                tbody.innerHTML = (data.tables ?? []).map(t => {
                    const mb  = parseFloat(t.size_mb ?? 0);
                    const col = mb > 50 ? '#ef4444' : mb > 10 ? '#f59e0b' : '#10b981';
                    const pct = totalMb > 0 ? Math.min(100, mb / totalMb * 100) : 0;
                    return `
                        <tr>
                            <td><code style="background:#f1f5f9;padding:3px 7px;border-radius:6px;font-size:12px;">${t.table}</code></td>
                            <td style="font-weight:700;">${Number(t.rows ?? 0).toLocaleString()}</td>
                            <td style="font-weight:700;color:${col};">${mb} MB</td>
                            <td>
                                <div style="background:#f1f5f9;border-radius:999px;height:7px;width:100px;overflow:hidden;">
                                    <div style="height:100%;border-radius:999px;background:${col};width:${pct}%;transition:width 1s;"></div>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        })
        .catch(() => {
            if (loading) loading.innerHTML = '<span style="color:#ef4444;">Erreur de connexion à Railway</span>';
        });
}

// ── Auto-load DB stats sur la card ──
document.addEventListener('DOMContentLoaded', () => {
    saGet(SA_ROUTES.dbStats)
        .then(data => {
            const totalMb = parseFloat(data.total_size_mb ?? 0);
            const badge   = document.getElementById('dbStatusBadge');
            const fill    = document.getElementById('dbFill');
            if (badge) {
                badge.textContent      = totalMb + ' MB';
                badge.style.background = totalMb > 400 ? '#fee2e2' : '#d1fae5';
                badge.style.color      = totalMb > 400 ? '#991b1b' : '#065f46';
            }
            if (fill) {
                const pct = Math.min(100, (totalMb / 500) * 100);
                fill.style.width = pct + '%';
                fill.className   = 'sa-perf-fill' + (pct > 80 ? ' danger' : pct > 50 ? ' warn' : '');
            }
        })
        .catch(() => {});

    // Queue badge
    saGet(SA_ROUTES.queueStats)
        .then(data => {
            const badge = document.getElementById('queueBadge');
            if (badge) {
                const failed  = data.failed ?? 0;
                const pending = data.pending ?? 0;
                badge.textContent      = failed > 0 ? `${failed} erreur(s)` : `${pending} en attente`;
                badge.style.background = failed > 0 ? '#fee2e2' : '#dbeafe';
                badge.style.color      = failed > 0 ? '#991b1b' : '#1e40af';
            }
        })
        .catch(() => {});
});
</script>