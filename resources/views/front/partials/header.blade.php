<header class="header">
    <div class="container header-inner">
        <a href="{{ route('front.home') }}" class="logo" aria-label="Data Rocket - Accueil">
            <img src="{{ asset('assets/img/360data.jpeg') }}" alt="Data Rocket" class="logo-img" loading="lazy">
        </a>

        <button class="mobile-btn" id="mobileBtn" aria-label="Menu" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
            <i class="fa-solid fa-xmark close-icon"></i>
        </button>

        <nav class="nav" id="nav" aria-label="Navigation principale">
            <a href="{{ route('front.home') }}#carte" class="nav-link">
                <i class="fa-solid fa-map"></i>
                <span>Carte</span>
            </a>
            
          
        </nav>
    </div>
</header>

<style>
    /* Header Styles */
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
        padding: 0.9rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
    }

    /* Logo */
    .logo {
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }

    .logo:hover {
        transform: scale(1.02);
    }

    .logo-img {
        height: 80px;
        width: auto;
        object-fit: contain;
        border-radius: 8px;
    }

    /* Navigation Desktop */
    .nav {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        color: #1e293b;
        text-decoration: none;
        font-weight: 500;
        border-radius: 48px;
        transition: all 0.2s ease;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .nav-link i {
        font-size: 1.1rem;
        transition: transform 0.2s ease;
    }

    .nav-link:hover {
        background: #f1f5f9;
        color: #0053b3;
    }

    .nav-link:hover i {
        transform: translateY(-2px);
    }

    .btn-signup {
        background: #0053b3;
        color: white;
        padding: 0.6rem 1.4rem;
        border-radius: 48px;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        margin-left: 0.5rem;
    }

    .btn-signup:hover {
        background: #003d85;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 83, 179, 0.3);
        color: white;
    }

    .logout-form {
        margin: 0;
    }

    .nav-logout {
        color: #dc2626;
    }

    .nav-logout:hover {
        background: #fef2f2;
        color: #b91c1c;
    }

    /* Mobile Button */
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

    /* Responsive Mobile */
    @media (max-width: 768px) {
        .header-inner {
            padding: 0.8rem 1.2rem;
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
            width: 80%;
            max-width: 320px;
            height: 100vh;
            background: white;
            flex-direction: column;
            justify-content: flex-start;
            padding: 5rem 1.5rem 2rem;
            gap: 0.8rem;
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
        }

        .btn-signup {
            width: 100%;
            justify-content: center;
            margin-left: 0;
            margin-top: 0.5rem;
            padding: 0.9rem;
        }

        /* Overlay */
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

    /* Desktop nav reste visible */
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
    (function() {
        // Éléments DOM
        const mobileBtn = document.getElementById('mobileBtn');
        const nav = document.getElementById('nav');
        
        // Créer l'overlay si nécessaire
        let overlay = document.querySelector('.nav-overlay');
        if (!overlay && window.innerWidth <= 768) {
            overlay = document.createElement('div');
            overlay.className = 'nav-overlay';
            document.body.appendChild(overlay);
        }

        // Header scroll effect
        const header = document.querySelector('.header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Toggle mobile menu
        function toggleMenu(force) {
            const isOpen = force !== undefined ? force : !nav.classList.contains('active');
            
            if (isOpen) {
                nav.classList.add('active');
                mobileBtn.classList.add('active');
                if (overlay) overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                mobileBtn.setAttribute('aria-expanded', 'true');
            } else {
                nav.classList.remove('active');
                mobileBtn.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
                mobileBtn.setAttribute('aria-expanded', 'false');
            }
        }

        if (mobileBtn) {
            mobileBtn.addEventListener('click', () => toggleMenu());
        }

        if (overlay) {
            overlay.addEventListener('click', () => toggleMenu(false));
        }

        // Fermer le menu au resize > mobile
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 768 && nav.classList.contains('active')) {
                    toggleMenu(false);
                    if (overlay) overlay.classList.remove('active');
                }
                
                // Recréer l'overlay si nécessaire
                const existingOverlay = document.querySelector('.nav-overlay');
                if (window.innerWidth <= 768 && !existingOverlay && !overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'nav-overlay';
                    document.body.appendChild(overlay);
                    overlay.addEventListener('click', () => toggleMenu(false));
                } else if (window.innerWidth > 768 && existingOverlay) {
                    existingOverlay.remove();
                    overlay = null;
                }
            }, 100);
        });

        // Fermer le menu lors du clic sur un lien
        document.querySelectorAll('.nav-link, .btn-signup').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleMenu(false);
                }
            });
        });
    })();
</script>