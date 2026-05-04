<header class="header">
    <div class="container header-inner">
        <a href="{{ route('front.home') }}" class="logo">
            <img src="{{ asset('assets/img/data1.png') }}" alt="Data Rocket" class="logo-img">
        </a>

        <button class="mobile-btn" id="mobileBtn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="nav" id="nav">
            <a href="{{ route('front.home') }}#carte">Carte</a>
            <a href="{{ route('front.demo') }}">Démo</a>

            @auth
                <a href="{{ route('back.dashboard') }}">Back office</a>
            @else
                <a href="{{ route('login') }}">Connexion</a>
                <a href="{{ route('register') }}" class="register-btn">Inscription</a>
            @endauth
        </nav>
    </div>
</header>