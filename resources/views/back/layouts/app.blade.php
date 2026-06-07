<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Back Office - Data 360')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 240px;
            --topbar-h:      56px;
        }

        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
        }

        /* ── Layout wrapper ── */
        .back-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1040;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1),
                        box-shadow 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* ── Main area ── */
        .back-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            /* Décalage desktop */
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }

        .back-content {
            padding: clamp(14px, 3vw, 24px);
            flex: 1;
        }

        /* ── Topbar mobile ── */
        .back-topbar {
            display: none;
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #0a1628;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            height: var(--topbar-h);
            align-items: center;
            padding: 0 16px;
            gap: 12px;
        }

        .topbar-burger {
            background: none;
            border: none;
            color: #f1f5f9;
            font-size: 1.15rem;
            cursor: pointer;
            padding: 7px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
            flex-shrink: 0;
        }
        .topbar-burger:hover { background: rgba(255,255,255,0.08); }

        .topbar-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #f1f5f9;
        }
        .topbar-title span { color: #3b82f6; }

        /* ── Overlay mobile ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(2px);
            z-index: 1039;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.visible {
            display: block;
            opacity: 1;
        }

        /* ── Alerts ── */
        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: 700;
            font-size: 14px;
        }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: 700;
            font-size: 14px;
        }
        .alert-error ul { margin-top: 8px; margin-bottom: 0; }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            /* Sidebar cachée par défaut sur mobile */
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 6px 0 40px rgba(0,0,0,0.35);
            }

            /* Plus de décalage sur mobile */
            .back-main {
                margin-left: 0;
            }

            /* Topbar visible */
            .back-topbar {
                display: flex;
            }
        }
    </style>

    @include('back.partials.styles')
    @stack('styles')
</head>
<body>

    {{-- ── Overlay mobile ── --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="back-wrapper">

        {{-- ── Sidebar ── --}}
        @include('back.partials.sidebar')

        {{-- ── Main ── --}}
        <main class="back-main">

            {{-- Topbar mobile --}}
            <div class="back-topbar" id="backTopbar">
                <button class="topbar-burger" id="sidebarBurger" aria-label="Ouvrir le menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="topbar-title">⚡ Data <span>360</span></span>
            </div>

            @include('back.partials.header')

            <section class="back-content">
                @if(session('success'))
                    <div class="alert-success">
                        <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-xmark" style="margin-right:6px;"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-error">
                        <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>
                        <strong>Erreur :</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
    (function () {
        const sidebar  = document.querySelector('.sidebar');
        const overlay  = document.getElementById('sidebarOverlay');
        const burger   = document.getElementById('sidebarBurger');

        if (!sidebar) return;

        // Trouver le bouton fermer dans la sidebar (généré par sidebar.blade.php)
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

        if (burger)   burger.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay)  overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
    })();
    </script>

    @include('back.partials.scripts')
    @stack('scripts')
</body>
</html>
