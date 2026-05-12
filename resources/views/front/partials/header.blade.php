@php
    $user = auth()->user();

    $credits = (int) ($user->credits ?? 0);

    $isAdmin = $user && (bool) $user->is_admin;
    $isPremium = $user && ($user->plan === 'premium');
    $isEnterprise = $user && ($user->plan === 'enterprise');

    if ($isAdmin) {
        $statusLabel = 'Admin';
        $statusClass = 'admin';
        $statusIcon = 'fa-crown';
    } elseif ($isEnterprise) {
        $statusLabel = 'Entreprise';
        $statusClass = 'enterprise';
        $statusIcon = 'fa-building-shield';
    } elseif ($isPremium) {
        $statusLabel = 'Premium';
        $statusClass = 'premium';
        $statusIcon = 'fa-gem';
    } else {
        $statusLabel = 'Free';
        $statusClass = 'free';
        $statusIcon = 'fa-user';
    }
@endphp

<header class="header">
    <div class="container header-inner">

        <a href="{{ route('front.home') }}" class="logo" aria-label="Data Rocket - Accueil">
            <img src="{{ asset('assets/img/360data.jpeg') }}" alt="Data Rocket" class="logo-img" loading="lazy">
        </a>

        <button class="mobile-btn" id="mobileBtn" aria-label="Menu" aria-expanded="false" type="button">
            <i class="fa-solid fa-bars"></i>
            <i class="fa-solid fa-xmark close-icon"></i>
        </button>

        <nav class="nav" id="nav" aria-label="Navigation principale">
            <a href="{{ route('front.home') }}#carte" class="nav-link">
                <i class="fa-solid fa-map"></i>
                <span>Carte</span>
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="credits-circle {{ $statusClass }}" id="creditsCircle" title="Mon statut et mes crédits">
                    <div class="circle-inner">

                        <div class="circle-half circle-status">
                            <span class="status-icon">
                                <i class="fa-solid {{ $statusIcon }}"></i>
                            </span>
                            <span class="status-mini-label">{{ $statusLabel }}</span>
                        </div>

                        <div class="circle-divider"></div>

                        <div class="circle-half circle-credits">
                            <span class="credits-value" id="userCredits">{{ $credits }}</span>
                            <span class="credits-mini-label">crédits</span>
                        </div>

                    </div>

                    <div class="circle-tooltip" id="creditsTooltip">
                        <div class="tooltip-content">
                            <div class="tooltip-title">
                                <i class="fa-solid fa-wallet"></i>
                                Mon compte
                            </div>

                            <div class="tooltip-row">
                                <span>Statut</span>
                                <strong>
                                    <i class="fa-solid {{ $statusIcon }}"></i>
                                    {{ $statusLabel }}
                                </strong>
                            </div>

                            <div class="tooltip-row">
                                <span>Crédits restants</span>
                                <strong id="tooltipCredits">{{ $credits }}</strong>
                            </div>

                            @if(!$isAdmin)
                                @if(Route::has('front.credits.buy'))
                                    <a href="{{ route('front.credits.buy') }}" class="tooltip-buy-link">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                        Acheter des crédits
                                    </a>
                                @else
                                    <a href="{{ route('dashboard') }}" class="tooltip-buy-link">
                                        <i class="fa-solid fa-coins"></i>
                                        Voir mes crédits
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('admin.security.users.index') }}" class="tooltip-buy-link">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Gestion utilisateurs
                                </a>
                            @endif
                        </div>
                    </div>
                </a>
            @else
               
            @endauth
        </nav>
    </div>
</header>

<style>
    .header {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .header.scrolled {
        background: white;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
    }

    .header-inner {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0.85rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
    }

    .logo {
        flex-shrink: 0;
        transition: transform 0.2s ease;
        display: inline-flex;
        align-items: center;
    }

    .logo:hover {
        transform: scale(1.02);
    }

    .logo-img {
        height: 76px;
        width: auto;
        object-fit: contain;
        border-radius: 10px;
    }

    .nav {
        margin-left: auto;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1.2rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.15rem;
        color: #1e293b;
        text-decoration: none;
        font-weight: 700;
        border-radius: 48px;
        transition: all 0.2s ease;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
        white-space: nowrap;
    }

    .nav-link i {
        font-size: 1.05rem;
        transition: transform 0.2s ease;
    }

    .nav-link:hover {
        background: #f1f5f9;
        color: #0053b3;
    }

    .nav-link:hover i {
        transform: translateY(-2px);
    }

    .credits-circle {
        position: relative;
        width: 70px;
        height: 70px;
        cursor: pointer;
        flex-shrink: 0;
        text-decoration: none;
        display: inline-flex;
    }

    .circle-inner {
        width: 100%;
        height: 100%;
        position: relative;
        border-radius: 50%;
        overflow: hidden;
        background: white;
        border: 3px solid white;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .credits-circle:hover .circle-inner {
        transform: scale(1.055);
        box-shadow: 0 16px 38px rgba(0, 83, 179, 0.28);
    }

    .circle-half {
        position: absolute;
        width: 100%;
        height: 50%;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
    }

    .circle-status {
        top: 0;
        flex-direction: column;
        gap: 1px;
        color: white;
    }

    .circle-credits {
        bottom: 0;
        flex-direction: column;
        gap: 0;
        background: #f8fafc;
        color: #0053b3;
    }

    .circle-divider {
        position: absolute;
        top: 50%;
        left: 9%;
        width: 82%;
        height: 2px;
        background: rgba(255,255,255,.85);
        z-index: 3;
        transform: translateY(-50%);
        border-radius: 999px;
        box-shadow: 0 1px 0 rgba(15,23,42,.08);
    }

    .status-icon {
        font-size: 1rem;
        line-height: 1;
    }

    .status-mini-label {
        font-size: 0.53rem;
        line-height: 1;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 950;
    }

    .credits-value {
        font-size: 1.1rem;
        font-weight: 950;
        line-height: 1;
    }

    .credits-mini-label {
        font-size: 0.48rem;
        line-height: 1;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        font-weight: 950;
        margin-top: 2px;
    }

    .credits-circle.admin .circle-status {
        background: linear-gradient(135deg, #7c2d12 0%, #f59e0b 100%);
    }

    .credits-circle.premium .circle-status {
        background: linear-gradient(135deg, #581c87 0%, #a855f7 100%);
    }

    .credits-circle.enterprise .circle-status {
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
    }

    .credits-circle.free .circle-status {
        background: linear-gradient(135deg, #0053b3 0%, #1d4ed8 100%);
    }

    .credits-circle.admin .circle-credits {
        color: #b45309;
        background: #fff7ed;
    }

    .credits-circle.premium .circle-credits {
        color: #7e22ce;
        background: #faf5ff;
    }

    .credits-circle.enterprise .circle-credits {
        color: #0f172a;
        background: #f8fafc;
    }

    .credits-circle.free .circle-credits {
        color: #0053b3;
        background: #eff6ff;
    }

    .circle-tooltip {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        background: white;
        border-radius: 18px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
        border: 1px solid #e2e8f0;
        padding: 0.9rem;
        min-width: 230px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: all 0.2s ease;
        z-index: 100;
    }

    .circle-tooltip::before {
        content: "";
        position: absolute;
        top: -7px;
        right: 26px;
        width: 14px;
        height: 14px;
        background: white;
        border-left: 1px solid #e2e8f0;
        border-top: 1px solid #e2e8f0;
        transform: rotate(45deg);
    }

    .credits-circle:hover .circle-tooltip,
    .credits-circle:focus-within .circle-tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .tooltip-content {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .tooltip-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #0f172a;
        font-size: 0.86rem;
        font-weight: 950;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .tooltip-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
        gap: 1rem;
        white-space: nowrap;
        color: #64748b;
    }

    .tooltip-row strong {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #0053b3;
        font-weight: 950;
    }

    .tooltip-buy-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: #0053b3;
        color: white;
        padding: 0.6rem 0.9rem;
        border-radius: 999px;
        text-decoration: none;
        font-size: 0.78rem;
        font-weight: 900;
        transition: all 0.2s;
        margin-top: 0.25rem;
    }

    .tooltip-buy-link:hover {
        background: #003d85;
        transform: translateY(-1px);
        color: white;
    }

    .mobile-btn {
        display: none;
        background: none;
        border: none;
        font-size: 1.8rem;
        cursor: pointer;
        color: #0053b3;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        transition: all 0.2s ease;
        position: relative;
        z-index: 1001;
    }

    .mobile-btn:hover {
        background: #f1f5f9;
    }

    .close-icon {
        display: none;
    }

    .mobile-btn.active .fa-bars {
        display: none;
    }

    .mobile-btn.active .close-icon {
        display: inline-block;
    }

    @media (max-width: 768px) {
        .header-inner {
            padding: 0.75rem 1rem;
            gap: 1rem;
        }

        .logo-img {
            height: 62px;
        }

        .mobile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: 82%;
            max-width: 320px;
            height: 100vh;
            background: white;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            padding: 5.2rem 1.5rem 2rem;
            gap: 0.9rem;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }

        .nav.active {
            right: 0;
        }

        .nav-link {
            width: 100%;
            justify-content: flex-start;
            padding: 0.9rem 1.2rem;
            font-size: 1rem;
            background: #f8fafc;
        }

        .credits-circle {
            width: 74px;
            height: 74px;
            margin: 0 auto;
        }

        .circle-tooltip {
            position: static;
            opacity: 1;
            visibility: visible;
            transform: none;
            min-width: 100%;
            margin-top: 14px;
            display: block;
        }

        .circle-tooltip::before {
            display: none;
        }

        .nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 998;
        }

        .nav-overlay.active {
            opacity: 1;
            visibility: visible;
        }
    }

    @media (min-width: 769px) {
        .nav {
            position: static !important;
            width: auto !important;
            height: auto !important;
            background: none !important;
            padding: 0 !important;
            box-shadow: none !important;
            flex-direction: row !important;
        }
    }
</style>

<script>
    (function () {
        const mobileBtn = document.getElementById('mobileBtn');
        const nav = document.getElementById('nav');
        const header = document.querySelector('.header');

        let overlay = document.querySelector('.nav-overlay');

        function ensureOverlay() {
            overlay = document.querySelector('.nav-overlay');

            if (!overlay && window.innerWidth <= 768) {
                overlay = document.createElement('div');
                overlay.className = 'nav-overlay';
                document.body.appendChild(overlay);
                overlay.addEventListener('click', () => toggleMenu(false));
            }
        }

        ensureOverlay();

        window.addEventListener('scroll', () => {
            if (!header) return;

            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        function toggleMenu(force) {
            if (!nav || !mobileBtn) return;

            const isOpen = force !== undefined ? force : !nav.classList.contains('active');

            if (isOpen) {
                nav.classList.add('active');
                mobileBtn.classList.add('active');
                ensureOverlay();

                if (overlay) {
                    overlay.classList.add('active');
                }

                document.body.style.overflow = 'hidden';
                mobileBtn.setAttribute('aria-expanded', 'true');
            } else {
                nav.classList.remove('active');
                mobileBtn.classList.remove('active');

                if (overlay) {
                    overlay.classList.remove('active');
                }

                document.body.style.overflow = '';
                mobileBtn.setAttribute('aria-expanded', 'false');
            }
        }

        if (mobileBtn) {
            mobileBtn.addEventListener('click', () => toggleMenu());
        }

        let resizeTimer;

        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);

            resizeTimer = setTimeout(function () {
                if (window.innerWidth > 768) {
                    toggleMenu(false);

                    const existingOverlay = document.querySelector('.nav-overlay');
                    if (existingOverlay) {
                        existingOverlay.remove();
                    }

                    overlay = null;
                } else {
                    ensureOverlay();
                }
            }, 120);
        });

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleMenu(false);
                }
            });
        });
    })();

    function updateUserCredits(newCredits) {
        const creditsElement = document.getElementById('userCredits');
        const tooltipCredits = document.getElementById('tooltipCredits');

        if (creditsElement) {
            creditsElement.textContent = newCredits;
        }

        if (tooltipCredits) {
            tooltipCredits.textContent = newCredits;
        }
    }
</script>