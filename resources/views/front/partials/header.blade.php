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

    /*
    |--------------------------------------------------------------------------
    | Notifications Front
    |--------------------------------------------------------------------------
    */
    $frontNotifications = collect();

    if ($user) {
        $frontNotifications = \App\Models\Back\Notification::query()
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $user->id);
            })
            ->latest()
            ->take(6)
            ->get();
    }

    $unreadNotificationsCount = $frontNotifications
        ->where('is_read', false)
        ->count();
@endphp

<header class="header">
    <div class="container header-inner">

        <a href="{{ route('front.home') }}" class="logo" aria-label="Data Rocket - Accueil">
            <img
                src="{{ asset('assets/img/360data.jpeg') }}"
                alt="Data Rocket"
                class="logo-img"
                loading="lazy"
            >
        </a>

        <button
            class="mobile-btn"
            id="mobileBtn"
            aria-label="Menu"
            aria-expanded="false"
            type="button"
        >
            <i class="fa-solid fa-bars"></i>
            <i class="fa-solid fa-xmark close-icon"></i>
        </button>

        <nav class="nav" id="nav" aria-label="Navigation principale">

            <a href="{{ route('front.home') }}#carte" class="nav-link">
                <i class="fa-solid fa-map"></i>
                <span>Carte</span>
            </a>

            @auth

                {{-- =========================
                    NOTIFICATIONS FRONT
                ========================== --}}
                @if($frontNotifications->count() > 0)

                    <div class="front-notifications">

                        <button
                            class="front-notif-btn"
                            id="frontNotifBtn"
                            type="button"
                        >
                            <i class="fa-regular fa-bell"></i>

                            @if($unreadNotificationsCount > 0)
                                <span class="front-notif-badge">
                                    {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                                </span>
                            @endif
                        </button>

                        <div class="front-notif-dropdown" id="frontNotifDropdown">

                            <div class="front-notif-header">
                                <span>Notifications</span>
                            </div>

                            <div class="front-notif-list">

                                @foreach($frontNotifications as $notif)

                                    @php
                                        $notifType = \App\Models\Back\Notification::types()[$notif->type]
                                            ?? \App\Models\Back\Notification::types()['info'];
                                    @endphp

                                    <div class="front-notif-item {{ !$notif->is_read ? 'unread' : '' }}">

                                        <div class="front-notif-icon">
                                            <i
                                                class="{{ $notif->icon ?? $notifType['icon'] }}"
                                                style="color: {{ $notifType['color'] }};"
                                            ></i>
                                        </div>

                                        <div class="front-notif-content">

                                            <div class="front-notif-title">
                                                {{ $notif->title }}
                                            </div>

                                            <div class="front-notif-message">
                                                {{ $notif->message }}
                                            </div>

                                            <div class="front-notif-time">
                                                {{ $notif->created_at->diffForHumans() }}
                                            </div>

                                        </div>

                                        @if($notif->link)
                                            <a
                                                href="{{ $notif->link }}"
                                                class="front-notif-overlay-link"
                                            ></a>
                                        @endif

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                @endif

                {{-- =========================
                    CERCLE CREDITS
                ========================== --}}
                <a
                    href="{{ route('dashboard') }}"
                    class="credits-circle {{ $statusClass }}"
                    id="creditsCircle"
                    title="Mon statut et mes crédits"
                >

                    <div class="circle-inner">

                        <div class="circle-half circle-status">
                            <span class="status-icon">
                                <i class="fa-solid {{ $statusIcon }}"></i>
                            </span>

                            <span class="status-mini-label">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="circle-divider"></div>

                        <div class="circle-half circle-credits">
                            <span class="credits-value" id="userCredits">
                                {{ $credits }}
                            </span>

                            <span class="credits-mini-label">
                                crédits
                            </span>
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

                                <strong id="tooltipCredits">
                                    {{ $credits }}
                                </strong>
                            </div>

                            @if(!$isAdmin)

                                @if(Route::has('front.credits.buy'))

                                    <a
                                        href="{{ route('front.credits.buy') }}"
                                        class="tooltip-buy-link"
                                    >
                                        <i class="fa-solid fa-cart-shopping"></i>
                                        Acheter des crédits
                                    </a>

                                @else

                                    <a
                                        href="{{ route('dashboard') }}"
                                        class="tooltip-buy-link"
                                    >
                                        <i class="fa-solid fa-coins"></i>
                                        Voir mes crédits
                                    </a>

                                @endif

                            @else

                                <a
                                    href="{{ route('admin.security.users.index') }}"
                                    class="tooltip-buy-link"
                                >
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Gestion utilisateurs
                                </a>

                            @endif

                        </div>

                    </div>

                </a>

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
        gap: 1rem;
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
        font-size: 0.95rem;
        white-space: nowrap;
    }

    .nav-link:hover {
        background: #f1f5f9;
        color: #0053b3;
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS FRONT
    |--------------------------------------------------------------------------
    */

    .front-notifications {
        position: relative;
    }

    .front-notif-btn {
        position: relative;
        width: 54px;
        height: 54px;
        border-radius: 50%;
        border: none;
        background: white;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
        cursor: pointer;
        transition: all 0.2s ease;
        color: #0053b3;
        font-size: 1.15rem;
    }

    .front-notif-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(0, 83, 179, 0.22);
    }

    .front-notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: #ef4444;
        color: white;
        font-size: 0.72rem;
        font-weight: 900;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
    }

    .front-notif-dropdown {
        position: absolute;
        top: calc(100% + 14px);
        right: 0;
        width: 360px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 55px rgba(15, 23, 42, 0.18);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.25s ease;
        z-index: 1200;
    }

    .front-notifications.active .front-notif-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .front-notif-header {
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 900;
        color: #0f172a;
        background: #f8fafc;
    }

    .front-notif-list {
        max-height: 420px;
        overflow-y: auto;
    }

    .front-notif-item {
        position: relative;
        display: flex;
        gap: 0.9rem;
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s ease;
    }

    .front-notif-item:hover {
        background: #f8fafc;
    }

    .front-notif-item.unread {
        background: #eff6ff;
    }

    .front-notif-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(15,23,42,.08);
        font-size: 1rem;
    }

    .front-notif-content {
        flex: 1;
    }

    .front-notif-title {
        font-size: 0.9rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .front-notif-message {
        font-size: 0.78rem;
        line-height: 1.45;
        color: #64748b;
    }

    .front-notif-time {
        margin-top: 8px;
        font-size: 0.7rem;
        color: #94a3b8;
    }

    .front-notif-overlay-link {
        position: absolute;
        inset: 0;
        z-index: 5;
    }

    /*
    |--------------------------------------------------------------------------
    | CERCLE CREDITS
    |--------------------------------------------------------------------------
    */

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
    }

    .status-icon {
        font-size: 1rem;
    }

    .status-mini-label {
        font-size: 0.53rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 950;
    }

    .credits-value {
        font-size: 1.1rem;
        font-weight: 950;
    }

    .credits-mini-label {
        font-size: 0.48rem;
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

    .credits-circle:hover .circle-tooltip {
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
        color: white;
    }

    .mobile-btn {
        display: none;
    }

    @media (max-width: 768px) {

        .header-inner {
            padding: 0.75rem 1rem;
        }

        .logo-img {
            height: 62px;
        }

        .mobile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #0053b3;
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
            gap: 1rem;
            transition: right 0.3s ease;
            z-index: 999;
        }

        .nav.active {
            right: 0;
        }

        .front-notif-dropdown {
            width: 100%;
            right: auto;
            left: 0;
        }

        .credits-circle {
            margin: 0 auto;
        }

        .circle-tooltip {
            position: static;
            opacity: 1;
            visibility: visible;
            transform: none;
            margin-top: 14px;
        }
    }
</style>

<script>
    (function () {

        const mobileBtn = document.getElementById('mobileBtn');
        const nav = document.getElementById('nav');
        const header = document.querySelector('.header');

        const notifBtn = document.getElementById('frontNotifBtn');
        const notifWrapper = document.querySelector('.front-notifications');

        /*
        |--------------------------------------------------------------------------
        | HEADER SCROLL
        |--------------------------------------------------------------------------
        */

        window.addEventListener('scroll', () => {

            if (!header) return;

            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | MOBILE MENU
        |--------------------------------------------------------------------------
        */

        if (mobileBtn) {

            mobileBtn.addEventListener('click', function () {

                nav.classList.toggle('active');

            });

        }

        /*
        |--------------------------------------------------------------------------
        | NOTIFICATIONS DROPDOWN
        |--------------------------------------------------------------------------
        */

        if (notifBtn && notifWrapper) {

            notifBtn.addEventListener('click', function (e) {

                e.preventDefault();
                e.stopPropagation();

                notifWrapper.classList.toggle('active');

            });

            document.addEventListener('click', function (e) {

                if (!notifWrapper.contains(e.target)) {
                    notifWrapper.classList.remove('active');
                }

            });

        }

    })();

    /*
    |--------------------------------------------------------------------------
    | UPDATE CREDITS LIVE
    |--------------------------------------------------------------------------
    */

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