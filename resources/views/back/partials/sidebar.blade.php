{{-- ═══════════════════════════════════════════════════════
     SIDEBAR DATA 360 — Responsive + Superadmin
     ═══════════════════════════════════════════════════════ --}}

<style>
    /* ── Variables ── */
    :root {
        --sb-width: 240px;
        --sb-bg: #0a1628;
        --sb-bg2: #0f1f3d;
        --sb-border: rgba(255, 255, 255, 0.07);
        --sb-text: #94a3b8;
        --sb-text-active: #f1f5f9;
        --sb-accent: #3b82f6;
        --sb-accent-glow: rgba(59, 130, 246, 0.15);
        --sb-gold: #f59e0b;
        --sb-gold-bg: rgba(245, 158, 11, 0.1);
        --sb-gold-border: rgba(245, 158, 11, 0.25);
        --sb-radius: 10px;
        --sb-transition: 0.2s ease;
        --topbar-h: 56px;
    }

    /* ── Overlay mobile ── */
    .sb-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(2px);
        z-index: 998;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sb-overlay.visible {
        display: block;
        opacity: 1;
    }

    /* ── Sidebar ── */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--sb-width);
        background: var(--sb-bg);
        border-right: 1px solid var(--sb-border);
        display: flex;
        flex-direction: column;
        z-index: 999;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            box-shadow 0.3s ease;
    }

    /* ── Header ── */
    .sb-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 18px;
        height: 64px;
        border-bottom: 1px solid var(--sb-border);
        flex-shrink: 0;
    }

    .sb-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .sb-logo-icon {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
    }

    .sb-logo-text {
        font-size: 1rem;
        font-weight: 700;
        color: var(--sb-text-active);
        letter-spacing: -0.01em;
    }

    .sb-logo-text span {
        color: var(--sb-accent);
    }

    /* Bouton fermer (mobile) */
    .sb-close {
        display: none;
        background: none;
        border: none;
        color: var(--sb-text);
        font-size: 1.1rem;
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        transition: color var(--sb-transition), background var(--sb-transition);
    }

    .sb-close:hover {
        color: var(--sb-text-active);
        background: rgba(255, 255, 255, 0.06);
    }

    /* ── Scroll area ── */
    .sb-scroll {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 10px 10px 20px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
    }

    .sb-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .sb-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .sb-scroll::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    /* ── Section label ── */
    .sb-label {
        padding: 16px 8px 6px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: rgba(148, 163, 184, 0.5);
        border-top: 1px solid var(--sb-border);
        margin-top: 6px;
    }

    .sb-label:first-child {
        border-top: none;
        margin-top: 0;
        padding-top: 8px;
    }

    /* ── Links ── */
    .sb-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: var(--sb-radius);
        color: var(--sb-text);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all var(--sb-transition);
        position: relative;
        margin-bottom: 1px;
    }

    .sb-link:hover {
        color: var(--sb-text-active);
        background: rgba(255, 255, 255, 0.06);
    }

    .sb-link.active {
        color: var(--sb-text-active);
        background: var(--sb-accent-glow);
    }

    .sb-link.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 20%;
        height: 60%;
        width: 3px;
        background: var(--sb-accent);
        border-radius: 0 3px 3px 0;
    }

    .sb-link i {
        width: 18px;
        text-align: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    /* ── Superadmin link ── */
    .sb-link.superadmin {
        color: var(--sb-gold);
        background: var(--sb-gold-bg);
        border: 1px solid var(--sb-gold-border);
        margin-top: 2px;
    }

    .sb-link.superadmin:hover {
        background: rgba(245, 158, 11, 0.18);
        color: #fcd34d;
        border-color: rgba(245, 158, 11, 0.45);
        box-shadow: 0 0 12px rgba(245, 158, 11, 0.15);
    }

    .sb-link.superadmin i {
        color: var(--sb-gold);
    }

    /* ── Dropdown ── */
    .sb-dropdown {
        margin-bottom: 1px;
    }

    .sb-dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: var(--sb-radius);
        color: var(--sb-text);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all var(--sb-transition);
        cursor: pointer;
        width: 100%;
        background: none;
        border: none;
        text-align: left;
    }

    .sb-dropdown-toggle:hover {
        color: var(--sb-text-active);
        background: rgba(255, 255, 255, 0.06);
    }

    .sb-dropdown-toggle i {
        width: 18px;
        text-align: center;
        flex-shrink: 0;
    }

    .sb-dropdown-arrow {
        margin-left: auto;
        font-size: 0.7rem;
        transition: transform 0.25s ease;
        opacity: 0.6;
    }

    .sb-dropdown.open .sb-dropdown-arrow {
        transform: rotate(180deg);
    }

    .sb-dropdown-menu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        padding-left: 28px;
    }

    .sb-dropdown.open .sb-dropdown-menu {
        max-height: 400px;
    }

    .sb-dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 8px;
        color: var(--sb-text);
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 500;
        transition: all var(--sb-transition);
        margin-bottom: 1px;
    }

    .sb-dropdown-menu a:hover {
        color: var(--sb-text-active);
        background: rgba(255, 255, 255, 0.05);
    }

    .sb-dropdown-menu i {
        width: 16px;
        text-align: center;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .sb-divider {
        height: 1px;
        background: var(--sb-border);
        margin: 6px 0;
        border: none;
    }

    /* Badge notifications */
    .badge-notif {
        margin-left: auto;
        background: #ef4444;
        color: white;
        font-size: 0.6rem;
        padding: 2px 6px;
        border-radius: 20px;
        font-weight: 700;
        line-height: 1.4;
    }

    /* ── Footer ── */
    .sb-footer {
        padding: 12px 10px;
        border-top: 1px solid var(--sb-border);
        flex-shrink: 0;
    }

    .sb-user {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: var(--sb-radius);
        transition: background var(--sb-transition);
        cursor: pointer;
        text-decoration: none;
    }

    .sb-user:hover {
        background: rgba(255, 255, 255, 0.06);
    }

    .sb-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .sb-user-info {
        flex: 1;
        min-width: 0;
    }

    .sb-user-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--sb-text-active);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sb-user-plan {
        font-size: 0.7rem;
        color: var(--sb-text);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .sb-user-plan.premium {
        color: var(--sb-gold);
    }

    .sb-user-plan.enterprise {
        color: #a78bfa;
    }

    /* ── Topbar (mobile toggle) ── */
    .sb-topbar {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: var(--topbar-h);
        background: var(--sb-bg);
        border-bottom: 1px solid var(--sb-border);
        align-items: center;
        padding: 0 16px;
        gap: 12px;
        z-index: 997;
    }

    .sb-burger {
        background: none;
        border: none;
        color: var(--sb-text-active);
        font-size: 1.1rem;
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background var(--sb-transition);
    }

    .sb-burger:hover {
        background: rgba(255, 255, 255, 0.06);
    }

    .sb-topbar-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--sb-text-active);
    }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: none;
        }

        .sidebar.open {
            transform: translateX(0);
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.4);
        }

        .sb-close {
            display: flex;
        }

        .sb-topbar {
            display: flex;
        }
    }

    /* Décaler le contenu principal sur desktop */
    @media (min-width: 1025px) {
        .main-content-wrapper {
            margin-left: var(--sb-width);
        }
    }
</style>

{{-- ── Topbar mobile ── --}}
<div class="sb-topbar" id="sbTopbar">
    <button class="sb-burger" id="sbBurger" aria-label="Ouvrir le menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <span class="sb-topbar-title">⚡ Data <span style="color:#3b82f6;">360</span></span>
</div>

{{-- ── Overlay mobile ── --}}
<div class="sb-overlay" id="sbOverlay"></div>

{{-- ── Sidebar ── --}}
<aside class="sidebar" id="sidebar">

    {{-- Header --}}
    <div class="sb-header">
        <a href="#" class="sb-logo">
            <div class="sb-logo-icon"><i class="fa-solid fa-bolt"></i></div>
            <span class="sb-logo-text">Data <span>360</span></span>
        </a>
        <button class="sb-close" id="sbClose" aria-label="Fermer le menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Scroll area --}}
    <div class="sb-scroll">

        @auth

            @if (auth()->user()->is_admin)
                {{-- ── Admin menu ── --}}
                <div class="sb-label">Principal</div>

                <a href="{{ route('back.dashboard') }}" class="sb-link">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
                <a href="{{ route('back.adresses.index') }}" class="sb-link">
                    <i class="fa-solid fa-location-dot"></i> Adresses
                </a>
                <a href="{{ route('back.batiments.index') }}" class="sb-link">
                    <i class="fa-solid fa-building"></i> Bâtiments
                </a>
                <a href="{{ route('back.coproprietes.index') }}" class="sb-link">
                    <i class="fa-solid fa-city"></i> Copropriétés
                </a>
                <a href="{{ route('back.syndics.index') }}" class="sb-link">
                    <i class="fa-solid fa-user-tie"></i> Syndics
                </a>
                <a href="{{ route('back.recherches.index') }}" class="sb-link">
                    <i class="fa-solid fa-magnifying-glass"></i> Recherches
                </a>
                <a href="{{ route('back.imports.index') }}" class="sb-link">
                    <i class="fa-solid fa-file-csv"></i> Imports
                </a>

                <div class="sb-label">Sécurité</div>

                <a href="{{ route('admin.security.users.index') }}" class="sb-link">
                    <i class="fa-solid fa-shield-halved"></i> Utilisateurs & Crédits
                </a>
                <a href="{{ route('admin.security.blocked.index') }}" class="sb-link">
                    <i class="fa-solid fa-ban"></i> Identités bloquées
                </a>
                <a href="{{ route('back.maintenance.index') }}" class="sb-link">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Maintenance DB
                </a>
                <a href="{{ route('back.csv-imports.index') }}">
                    <i class="fa-solid fa-file-csv"></i> Imports CSV
                </a>
                {{-- ── Section Superadmin — visible uniquement pour les superadmins ── --}}
                @if (auth()->user()->isSuperAdmin())
                    <div class="sb-label">Superadmin</div>
                    <a href="{{ route('admin.superadmin.index') }}" class="sb-link superadmin">
                        <i class="fa-solid fa-crown"></i>
                        Panneau Superadmin
                    </a>
                @endif

                {{-- ── Notifications dropdown ── --}}
                <div class="sb-label">Notifications</div>

                <div class="sb-dropdown" id="notifDropdown">
                    <button class="sb-dropdown-toggle" onclick="toggleDropdown('notifDropdown')">
                        <i class="fa-regular fa-bell"></i>
                        <span>Notifications</span>
                        <span class="badge-notif" id="sidebarNotifBadge" style="display:none;">0</span>
                        <i class="fa-solid fa-chevron-down sb-dropdown-arrow"></i>
                    </button>
                    <div class="sb-dropdown-menu">
                        <a href="{{ route('back.notifications.index') }}">
                            <i class="fa-regular fa-list"></i> Toutes les notifications
                        </a>
                        <a href="{{ route('back.notifications.create') }}">
                            <i class="fa-solid fa-plus"></i> Créer une notification
                        </a>
                        <a href="#" id="sidebarMarkAllRead">
                            <i class="fa-regular fa-check-circle"></i> Tout marquer comme lu
                        </a>
                        <hr class="sb-divider">
                        <a href="{{ route('back.notifications.index') }}?is_global=1">
                            <i class="fa-solid fa-globe"></i> Notifications globales
                        </a>
                        <a href="#" id="sidebarUnreadCount">
                            <i class="fa-regular fa-envelope"></i> Non lues
                            <span class="badge-notif" id="sidebarNotifBadge2">0</span>
                        </a>
                    </div>
                </div>
            @else
                {{-- ── Menu utilisateur non-admin ── --}}
                <div class="sb-label">Mon espace</div>

                <a href="{{ route('dashboard') }}" class="sb-link">
                    <i class="fa-solid fa-house"></i> Mon espace
                </a>
                <a href="{{ route('front.home') }}" class="sb-link">
                    <i class="fa-solid fa-magnifying-glass"></i> Nouvelle recherche
                </a>
                <a href="#" class="sb-link">
                    <i class="fa-solid fa-coins"></i> Mes crédits
                </a>
                <a href="#" class="sb-link">
                    <i class="fa-solid fa-clock-rotate-left"></i> Mes recherches
                </a>
                <a href="#" class="sb-link">
                    <i class="fa-solid fa-user"></i> Mon profil
                </a>
                <a href="{{ route('notifications.index') }}" class="sb-link">
                    <i class="fa-regular fa-bell"></i> Mes notifications
                    <span id="userNotifBadge" class="badge-notif" style="display:none;">0</span>
                </a>
            @endif
        @endauth

    </div>

    {{-- ── Footer user ── --}}
    @auth
        <div class="sb-footer">
            <a href="#" class="sb-user">
                <div class="sb-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="sb-user-info">
                    <div class="sb-user-name">{{ auth()->user()->name }}</div>
                    <div class="sb-user-plan {{ auth()->user()->plan }}">
                        @if (auth()->user()->isSuperAdmin())
                            <i class="fa-solid fa-crown" style="color:#f59e0b;font-size:0.65rem;"></i> Superadmin
                        @elseif(auth()->user()->plan === 'enterprise')
                            <i class="fa-solid fa-star" style="color:#a78bfa;font-size:0.65rem;"></i> Enterprise
                        @elseif(auth()->user()->plan === 'premium')
                            <i class="fa-solid fa-bolt" style="color:#f59e0b;font-size:0.65rem;"></i> Premium
                        @else
                            <i class="fa-solid fa-user" style="font-size:0.65rem;"></i> Free
                        @endif
                    </div>
                </div>
                <i class="fa-solid fa-ellipsis-vertical" style="color:rgba(148,163,184,0.4);font-size:0.8rem;"></i>
            </a>
        </div>
    @endauth

</aside>

<script>
    (function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sbOverlay');
        const burger = document.getElementById('sbBurger');
        const closeBtn = document.getElementById('sbClose');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('visible');
            document.body.style.overflow = '';
        }

        if (burger) burger.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        // Fermer sur ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
    })();

    function toggleDropdown(id) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('open');
    }
</script>
