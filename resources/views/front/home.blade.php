@extends('front.layouts.app')

@section('title', 'Data Rocket - Données des adresses françaises')

@section('content')

<style>
    :root {
        --blue-deep:   #002952;
        --blue-main:   #0053b3;
        --blue-hover:  #003d85;
        --blue-light:  #e1eeff;
        --blue-glow:   rgba(0, 83, 179, 0.12);
        --gray-text:   #4a5568;
        --gray-border: #e2e8f0;
        --radius-pill: 48px;
        --shadow-sm:   0 2px 8px rgba(0,0,0,0.06);
        --shadow-md:   0 4px 20px rgba(0,83,179,0.15);
    }

    .hero {
        background: linear-gradient(135deg, #f0f7ff 0%, #e1eeff 100%);
        padding: 4rem 1.5rem;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(0,83,179,0.05) 0%, transparent 70%);
        pointer-events: none;
    }

    .hero .container {
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .hero h1 {
        font-size: clamp(2rem, 5vw, 3rem);
        font-weight: 800;
        color: var(--blue-deep);
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .hero p {
        font-size: clamp(1rem, 2.5vw, 1.2rem);
        color: var(--gray-text);
        margin-bottom: 2rem;
        line-height: 1.5;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .search-form {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
        max-width: 700px;
        margin: 0 auto;
        align-items: flex-start;
    }

    .search-box {
        flex: 1;
        min-width: 250px;
        position: relative;
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
        pointer-events: none;
    }

    .search-box input {
        width: 100%;
        height: 52px;
        padding: 0 1rem 0 2.8rem;
        font-size: 1rem;
        border: 2px solid var(--gray-border);
        border-radius: var(--radius-pill);
        background: white;
        transition: all 0.2s ease;
        outline: none;
        box-sizing: border-box;
    }

    .search-box input:focus {
        border-color: var(--blue-main);
        box-shadow: 0 0 0 3px var(--blue-glow);
    }

    .btn-primary {
        background: var(--blue-main);
        color: white;
        border: none;
        height: 52px;
        padding: 0 1.8rem;
        border-radius: var(--radius-pill);
        font-weight: 500;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-sizing: border-box;
        text-decoration: none;
    }

    .btn-primary:hover {
        background: var(--blue-hover);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn-primary:disabled {
        opacity: 0.8;
        cursor: not-allowed;
        transform: none;
    }

    .btn-primary .spinner {
        display: inline-block;
        width: 18px; height: 18px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Toggle recherche avancée ── */
    .advanced-toggle-wrap {
        margin-top: 1.25rem;
        text-align: center;
    }

    .advanced-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--blue-main);
        padding: 0.3rem 0.6rem;
        border-radius: 8px;
        transition: background 0.15s, color 0.15s;
        letter-spacing: 0.01em;
    }

    .advanced-toggle:hover {
        background: var(--blue-glow);
        color: var(--blue-hover);
    }

    .advanced-toggle .chevron {
        display: inline-block;
        transition: transform 0.3s ease;
        font-style: normal;
        font-size: 0.8rem;
        line-height: 1;
    }

    .advanced-toggle.open .chevron {
        transform: rotate(180deg);
    }

    /* ── Bandeau premium lock ── */
    .premium-lock-banner {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 1.5px solid #f59e0b;
        border-radius: 12px;
        padding: 0.6rem 1.2rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #92400e;
        margin-top: 1rem;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .premium-lock-banner:hover {
        background: linear-gradient(135deg, #fde68a, #fcd34d);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        color: #78350f;
    }

    .premium-lock-banner i {
        font-size: 1rem;
    }

    /* ── Panneau CSV ── */
    .advanced-panel {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.45s cubic-bezier(0.4,0,0.2,1),
                    opacity 0.35s ease,
                    transform 0.35s ease;
        opacity: 0;
        transform: translateY(-8px);
    }

    .advanced-panel.open {
        max-height: 800px;
        opacity: 1;
        transform: translateY(0);
    }

    .advanced-panel-inner {
        max-width: 700px;
        margin: 1.25rem auto 0;
        background: white;
        border: 2px solid var(--blue-light);
        border-radius: 20px;
        padding: 1.75rem 2rem;
        box-shadow: var(--shadow-sm);
        text-align: left;
    }

    .advanced-panel-inner h3 {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--blue-deep);
        margin: 0 0 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .advanced-panel-inner h3 i {
        color: var(--blue-main);
        font-size: 1rem;
    }

    .advanced-panel-inner .subtitle {
        font-size: 0.88rem;
        color: #718096;
        margin: 0 0 1.25rem;
        line-height: 1.5;
    }

    .drop-zone {
        border: 2px dashed #b5cef5;
        border-radius: 14px;
        padding: 2rem 1.5rem;
        text-align: center;
        cursor: pointer;
        background: #f7fbff;
        transition: all 0.2s ease;
        position: relative;
    }

    .drop-zone:hover,
    .drop-zone.dragover {
        border-color: var(--blue-main);
        background: var(--blue-light);
    }

    .drop-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }

    .drop-zone .dz-icon {
        font-size: 2.2rem;
        color: #90b8e8;
        margin-bottom: 0.6rem;
        display: block;
        transition: color 0.2s;
    }

    .drop-zone:hover .dz-icon,
    .drop-zone.dragover .dz-icon { color: var(--blue-main); }

    .drop-zone .dz-label {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--blue-deep);
        margin-bottom: 0.25rem;
    }

    .drop-zone .dz-hint {
        font-size: 0.82rem;
        color: #94a3b8;
    }

    .file-selected {
        display: none;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.9rem;
        background: #eef6ff;
        border: 1.5px solid #c3dcfc;
        border-radius: 10px;
        padding: 0.65rem 1rem;
    }

    .file-selected.visible { display: flex; }
    .file-selected i { color: var(--blue-main); font-size: 1.2rem; flex-shrink: 0; }
    .file-selected .file-name {
        font-size: 0.9rem; font-weight: 600; color: var(--blue-deep);
        flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .file-selected .file-size { font-size: 0.8rem; color: #718096; flex-shrink: 0; }

    .file-remove {
        background: none; border: none; cursor: pointer;
        color: #94a3b8; font-size: 1rem; padding: 0 0.25rem;
        transition: color 0.15s; flex-shrink: 0;
    }
    .file-remove:hover { color: #e53e3e; }

    .csv-format-hint {
        margin-top: 1rem; font-size: 0.82rem; color: #718096;
        display: flex; align-items: flex-start; gap: 0.4rem; line-height: 1.5;
    }
    .csv-format-hint i { color: #90b8e8; margin-top: 0.1rem; flex-shrink: 0; }

    .panel-footer {
        display: flex; align-items: center; justify-content: space-between;
        margin-top: 1.25rem; flex-wrap: wrap; gap: 0.75rem;
    }

    .btn-csv-submit {
        background: var(--blue-main); color: white; border: none;
        height: 46px; padding: 0 1.6rem; border-radius: var(--radius-pill);
        font-weight: 600; font-size: 0.95rem; cursor: pointer;
        transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .btn-csv-submit:hover:not(:disabled) {
        background: var(--blue-hover); transform: translateY(-1px); box-shadow: var(--shadow-md);
    }
    .btn-csv-submit:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-csv-submit .spinner {
        display: none; width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,0.3); border-radius: 50%;
        border-top-color: white; animation: spin 0.8s linear infinite;
    }
    .btn-csv-submit.loading .spinner { display: inline-block; }
    .btn-csv-submit.loading .btn-csv-text { opacity: 0.7; }

    .csv-template-link {
        font-size: 0.83rem; color: var(--blue-main); text-decoration: none;
        display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 500;
        transition: color 0.15s;
    }
    .csv-template-link:hover { color: var(--blue-hover); text-decoration: underline; }

    .csv-debug-panel {
        background: #0f172a; border-radius: 12px; padding: 12px 16px;
        margin-bottom: 1rem; font-family: monospace; font-size: 0.78rem;
        color: #94a3b8; display: none;
    }

    .section { padding: 4rem 1.5rem; animation: fadeInUp 0.6s ease-out forwards; }
    .container { max-width: 1200px; margin: 0 auto; }
    .center-text { text-align: center; }
    .section h2 { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 700; color: var(--blue-main); margin-bottom: 1rem; }
    .section p { font-size: 1.1rem; color: var(--gray-text); max-width: 700px; margin-left: auto; margin-right: auto; line-height: 1.6; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 640px) {
        .hero { padding: 2.5rem 1rem; }
        .search-form { flex-direction: column; align-items: stretch; }
        .btn-primary { width: 100%; justify-content: center; }
        .section { padding: 2.5rem 1rem; }
        .advanced-panel-inner { padding: 1.25rem 1.1rem; }
        .panel-footer { flex-direction: column; align-items: stretch; }
        .btn-csv-submit { justify-content: center; }
    }
</style>

<section class="hero" id="carte">
    <div class="container">
        <h1>Toutes les données des adresses françaises</h1>
        <p>
            Accédez à des informations détaillées sur les bâtiments, copropriétés,
            syndics, SIREN, niveaux, logements et années de construction.
        </p>

        {{-- ── Recherche simple ── --}}
        <form method="GET" action="{{ route('front.recherche') }}" class="search-form" id="searchForm">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ old('q', request('q')) }}"
                    placeholder="Saisir une adresse..."
                    required
                    autocomplete="off"
                    id="searchInput"
                >
            </div>
            <button type="submit" class="btn-primary" id="searchBtn">
                <i class="fa-solid fa-arrow-right"></i>
                <span class="btn-text">Tester une adresse</span>
            </button>
        </form>

      {{-- ── Recherche avancée — visible uniquement pour premium/enterprise ── --}}
        @php
            $hasPremiumAccess      = Auth::check() && in_array(Auth::user()->plan, ['premium', 'enterprise']);
            $advancedSearchEnabled = \App\Models\AppSetting::isEnabled('advanced_search_enabled');
        @endphp

        @if($hasPremiumAccess && $advancedSearchEnabled)
            {{-- Toggle accordéon --}}
            <div class="advanced-toggle-wrap">
                <button type="button" class="advanced-toggle" id="advancedToggle" aria-expanded="false" aria-controls="advancedPanel">
                    <i class="fa-solid fa-sliders"></i>
                    Recherche avancée — traiter plusieurs adresses
                    <span class="chevron" aria-hidden="true">▾</span>
                </button>
            </div>

            {{-- Panneau CSV --}}
            <div class="advanced-panel" id="advancedPanel" role="region" aria-labelledby="advancedToggle">
                <div class="advanced-panel-inner">
                    <h3>
                        <i class="fa-solid fa-file-csv"></i>
                        Import CSV — traitement en masse
                    </h3>
                    <p class="subtitle">
                        Importez un fichier CSV contenant vos adresses pour les enrichir en une seule opération.
                        Résultats téléchargeables une fois le traitement terminé.
                    </p>

                    {{-- Debug panel --}}
                    <div class="csv-debug-panel" id="csvDebugPanel">
                        <div style="color:#38bdf8; font-weight:700; margin-bottom:6px;">🛠 Debug CSV</div>
                        <div style="margin-bottom:3px;">
                            Route POST : <span style="color:#fbbf24;">{{ route('front.recherche.csv') }}</span>
                        </div>
                        <div id="dbgFile" style="margin-bottom:3px;">Fichier : <span style="color:#f1f5f9;">—</span></div>
                        <div id="dbgStatus" style="margin-bottom:3px;">Statut : <span style="color:#f1f5f9;">En attente</span></div>
                        <div style="margin-bottom:3px;">
                            CSRF : <span style="color:#fbbf24;">{{ substr(csrf_token(), 0, 16) }}…</span>
                        </div>
                        <div style="margin-bottom:3px;">
                            Auth : <span style="color:#4ade80;">✅ Connecté ({{ Auth::user()->email }}) — Plan : {{ Auth::user()->plan }}</span>
                        </div>
                        <div id="dbgLog" style="margin-top:8px; border-top:1px solid #1e293b; padding-top:8px; color:#64748b;">Log : —</div>
                    </div>

                    <button type="button" id="toggleDebug" style="
                        background:none; border:1px solid #e2e8f0; border-radius:8px;
                        padding:4px 10px; font-size:0.75rem; color:#94a3b8;
                        cursor:pointer; margin-bottom:1rem;
                    ">🛠 Afficher debug</button>

                    @if(session('success'))
                        <div style="background:#dcfce7; border:1px solid #86efac; border-radius:10px; padding:10px 14px; margin-bottom:1rem; color:#15803d; font-size:0.88rem;">
                            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->has('csv_file'))
                        <div style="background:#fee2e2; border:1px solid #fca5a5; border-radius:10px; padding:10px 14px; margin-bottom:1rem; color:#b91c1c; font-size:0.88rem;">
                            <i class="fa-solid fa-circle-xmark"></i> {{ $errors->first('csv_file') }}
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('front.recherche.csv') }}"
                        enctype="multipart/form-data"
                        id="csvForm"
                    >
                        @csrf

                        <div class="drop-zone" id="dropZone">
                            <input type="file" name="csv_file" id="csvFileInput" accept=".csv,text/csv">
                            <span class="dz-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                            <div class="dz-label">Glissez votre fichier CSV ici</div>
                            <div class="dz-hint">ou cliquez pour parcourir — CSV uniquement, max 10 Mo</div>
                        </div>

                        <div class="file-selected" id="fileSelectedInfo">
                            <i class="fa-solid fa-file-csv"></i>
                            <span class="file-name" id="fileName">—</span>
                            <span class="file-size" id="fileSize"></span>
                            <button type="button" class="file-remove" id="fileRemove" title="Retirer le fichier">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <p class="csv-format-hint">
                            <i class="fa-solid fa-circle-info"></i>
                            Le fichier doit contenir une colonne <strong>adresse</strong> (ou <em>address</em>).
                            Colonnes supplémentaires conservées dans le résultat exporté.
                        </p>

                        <div class="panel-footer">
                            <button type="submit" class="btn-csv-submit" id="csvSubmitBtn" disabled>
                                <span class="spinner"></span>
                                <i class="fa-solid fa-rocket btn-csv-icon"></i>
                                <span class="btn-csv-text">Lancer le traitement</span>
                            </button>
                            <a href="{{ route('front.recherche.csv.template') }}" class="csv-template-link" download>
                                <i class="fa-solid fa-download"></i>
                                Télécharger un modèle CSV
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        @elseif($hasPremiumAccess && !$advancedSearchEnabled)
            {{-- Premium mais fonctionnalité désactivée par le superadmin --}}
            <div class="advanced-toggle-wrap">
                <span class="premium-lock-banner" style="cursor:default; opacity:0.65;">
                    <i class="fa-solid fa-wrench"></i>
                    Recherche avancée temporairement indisponible
                </span>
            </div>

        @elseif(Auth::check())
            {{-- Utilisateur connecté mais plan FREE → bandeau upgrade --}}
            <div class="advanced-toggle-wrap">
                <a href="{{ route('front.credits.buy') }}" class="premium-lock-banner">
                    <i class="fa-solid fa-crown"></i>
                    Recherche avancée — disponible en Premium
                    <i class="fa-solid fa-arrow-right" style="font-size:0.8rem;"></i>
                </a>
            </div>

        @else
            {{-- Non connecté → invitation à se connecter --}}
            <div class="advanced-toggle-wrap">
                <a href="{{ route('login') }}" class="premium-lock-banner">
                    <i class="fa-solid fa-lock"></i>
                    Recherche avancée — connectez-vous pour accéder
                    <i class="fa-solid fa-arrow-right" style="font-size:0.8rem;"></i>
                </a>
            </div>
        @endif

    </div>
</section>

<section class="section">
    <div class="container center-text">
        <h2>Transformez vos Données en Opportunités avec le puissant Data 360</h2>
        <p>
            La solution de prospection intelligente basée sur l'adresse :
            bâtiment, copropriété, syndic, SIREN et potentiel commercial.
        </p>
    </div>
</section>

{{-- ── UN SEUL BLOC JAVASCRIPT ── --}}
<script>
(function () {

    // ── 1. Recherche simple : loader ────────────────────────────
    const searchForm = document.getElementById('searchForm');
    const searchBtn  = document.getElementById('searchBtn');
    const btnText    = searchBtn.querySelector('.btn-text');
    const arrowIcon  = searchBtn.querySelector('.fa-arrow-right');

    searchForm.addEventListener('submit', function (e) {
        if (searchBtn.disabled) { e.preventDefault(); return; }
        searchBtn.disabled = true;
        if (arrowIcon) arrowIcon.style.display = 'none';
        const spinner = document.createElement('span');
        spinner.className = 'spinner';
        searchBtn.insertBefore(spinner, btnText);
        btnText.textContent = 'Recherche en cours…';
    });

    // ── 2. Accordéon recherche avancée (premium only) ───────────
    const toggle = document.getElementById('advancedToggle');
    const panel  = document.getElementById('advancedPanel');

    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            const isOpen = panel.classList.toggle('open');
            toggle.classList.toggle('open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    }

    // ── 3. Debug panel ──────────────────────────────────────────
    const debugPanel  = document.getElementById('csvDebugPanel');
    const toggleDebug = document.getElementById('toggleDebug');
    const dbgFile     = document.getElementById('dbgFile');
    const dbgStatus   = document.getElementById('dbgStatus');
    const dbgLog      = document.getElementById('dbgLog');
    let   debugOn     = false;

    if (toggleDebug) {
        toggleDebug.addEventListener('click', function () {
            debugOn = !debugOn;
            debugPanel.style.display = debugOn ? 'block' : 'none';
            toggleDebug.textContent  = debugOn ? '🛠 Masquer debug' : '🛠 Afficher debug';
        });
    }

    function log(msg, color) {
        if (!dbgLog) return;
        const t = new Date().toLocaleTimeString();
        dbgLog.innerHTML = '<span style="color:' + (color || '#94a3b8') + '">[' + t + '] ' + msg + '</span>';
        console.log('[CSV]', msg);
    }

    // ── 4. Upload CSV ───────────────────────────────────────────
    const dropZone   = document.getElementById('dropZone');
    const fileInput  = document.getElementById('csvFileInput');
    const fileInfo   = document.getElementById('fileSelectedInfo');
    const fileNameEl = document.getElementById('fileName');
    const fileSizeEl = document.getElementById('fileSize');
    const fileRemove = document.getElementById('fileRemove');
    const csvSubmit  = document.getElementById('csvSubmitBtn');
    const csvForm    = document.getElementById('csvForm');

    if (!csvForm) return; // pas premium → rien à initialiser

    function fmt(bytes) {
        if (bytes < 1024)    return bytes + ' o';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
        return (bytes / 1048576).toFixed(2) + ' Mo';
    }

    function applyFile(file) {
        if (!file) return;
        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = fmt(file.size);
        fileInfo.classList.add('visible');
        csvSubmit.disabled = false;
        if (dbgFile) dbgFile.innerHTML = 'Fichier : <span style="color:#4ade80">' + file.name + ' (' + fmt(file.size) + ')</span>';
        if (dbgStatus) dbgStatus.innerHTML = 'Statut : <span style="color:#fbbf24">Fichier prêt ✅</span>';
        log('Fichier sélectionné : ' + file.name, '#4ade80');
    }

    function clearFile() {
        fileInput.value = '';
        fileInfo.classList.remove('visible');
        fileNameEl.textContent = '—';
        fileSizeEl.textContent = '';
        csvSubmit.disabled = true;
        if (dbgFile) dbgFile.innerHTML = 'Fichier : <span style="color:#f1f5f9">—</span>';
        if (dbgStatus) dbgStatus.innerHTML = 'Statut : <span style="color:#f1f5f9">En attente</span>';
        log('Fichier retiré', '#f87171');
    }

    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) applyFile(this.files[0]);
    });

    fileRemove.addEventListener('click', clearFile);

    ['dragenter', 'dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', function (e) {
        var file = e.dataTransfer.files[0];
        if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
            var dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            applyFile(file);
            log('Fichier déposé par drag & drop', '#38bdf8');
        } else {
            log('❌ Fichier refusé — pas un CSV', '#f87171');
        }
    });

    // ── 5. Submit CSV ───────────────────────────────────────────
    csvForm.addEventListener('submit', function (e) {
        if (csvSubmit.disabled) {
            e.preventDefault();
            log('❌ Soumission bloquée — bouton désactivé', '#f87171');
            return;
        }
        if (!fileInput.files || !fileInput.files[0]) {
            e.preventDefault();
            log('❌ Aucun fichier sélectionné', '#f87171');
            return;
        }
        log('🚀 Envoi vers ' + csvForm.action, '#fbbf24');
        if (dbgStatus) dbgStatus.innerHTML = 'Statut : <span style="color:#fbbf24">⏳ Envoi en cours…</span>';
        csvSubmit.disabled = true;
        csvSubmit.classList.add('loading');
        var icon = csvSubmit.querySelector('.btn-csv-icon');
        if (icon) icon.style.display = 'none';
    });

})();
</script>

@endsection
