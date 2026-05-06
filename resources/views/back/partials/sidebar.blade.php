<aside class="sidebar">

    <h2 style="margin-bottom:20px;">⚡ Data 360</h2>

    <a href="{{ route('back.dashboard') }}">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>

    <a href="{{ route('back.adresses.index') }}">
        <i class="fa-solid fa-location-dot"></i> Adresses
    </a>

    <a href="{{ route('back.batiments.index') }}">
        <i class="fa-solid fa-building"></i> Bâtiments
    </a>

    <a href="{{ route('back.coproprietes.index') }}">
        <i class="fa-solid fa-city"></i> Copropriétés
    </a>

    <a href="{{ route('back.syndics.index') }}">
        <i class="fa-solid fa-user-tie"></i> Syndics
    </a>

    <a href="{{ route('back.recherches.index') }}">
        <i class="fa-solid fa-magnifying-glass"></i> Recherches
    </a>

    <a href="{{ route('back.imports.index') }}">
        <i class="fa-solid fa-file-csv"></i> Imports
    </a>

    {{-- ========================= --}}
    {{-- SÉCURITÉ / MONÉTISATION --}}
    {{-- ========================= --}}

    <div style="
        margin:20px 0 10px;
        padding-top:15px;
        border-top:1px solid rgba(255,255,255,0.12);
        font-size:11px;
        letter-spacing:1px;
        text-transform:uppercase;
        opacity:.7;
    ">
        Sécurité
    </div>

    <a href="{{ route('admin.security.users.index') }}">
        <i class="fa-solid fa-shield-halved"></i>
        Utilisateurs & Crédits
    </a>

    <a href="{{ route('admin.security.blocked.index') }}">
        <i class="fa-solid fa-ban"></i>
        Identités bloquées
    </a>

</aside>