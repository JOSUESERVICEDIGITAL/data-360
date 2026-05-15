@extends('front.layouts.app')

@section('title', 'Data Rocket - Données des adresses françaises')

@section('content')

<style>
    /* Hero Section Fluid */
    .hero {
        background: linear-gradient(135deg, #f0f7ff 0%, #e1eeff 100%);
        padding: 4rem 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
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
        color: #002952;
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .hero p {
        font-size: clamp(1rem, 2.5vw, 1.2rem);
        color: #4a5568;
        margin-bottom: 2rem;
        line-height: 1.5;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Search Form - Alignement parfait */
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
        border: 2px solid #e2e8f0;
        border-radius: 48px;
        background: white;
        transition: all 0.2s ease;
        outline: none;
        box-sizing: border-box;
    }

    .search-box input:focus {
        border-color: #0053b3;
        box-shadow: 0 0 0 3px rgba(0, 83, 179, 0.1);
    }

    .btn-primary {
        background: #0053b3;
        color: white;
        border: none;
        height: 52px;
        padding: 0 1.8rem;
        border-radius: 48px;
        font-weight: 500;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-sizing: border-box;
    }

    .btn-primary:hover {
        background: #003d85;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 83, 179, 0.3);
    }

    /* Loader spinner */
    .btn-primary .spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .btn-primary:disabled {
        opacity: 0.8;
        cursor: not-allowed;
        transform: none;
    }

    /* Section Générale */
    .section {
        padding: 4rem 1.5rem;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .center-text {
        text-align: center;
    }

    .section h2 {
        font-size: clamp(1.5rem, 4vw, 2rem);
        font-weight: 700;
        color: #0053b3;
        margin-bottom: 1rem;
    }

    .section p {
        font-size: 1.1rem;
        color: #4a5568;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    /* Animations fluides */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero, .section {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .hero {
            padding: 2.5rem 1rem;
        }

        .search-form {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-primary {
            width: 100%;
            justify-content: center;
        }

        .section {
            padding: 2.5rem 1rem;
        }
    }
</style>

<section class="hero" id="carte">
    <div class="container">
        <h1>Toutes les données des adresses françaises</h1>
        <p>
            Accédez à des informations détaillées sur les bâtiments, copropriétés,
            syndics, SIREN, niveaux, logements et années de construction.
        </p>

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
                <i class="fa-solid fa-arrow-right" style="margin-right: 0.5rem;"></i>
                <span class="btn-text">Tester une adresse</span>
            </button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container center-text">
        <h2>Transformez vos Données en Opportunités avec Data 360</h2>
        <p>
            La solution de prospection intelligente basée sur l’adresse :
            bâtiment, copropriété, syndic, SIREN et potentiel commercial.
        </p>
    </div>
</section>

<script>
    (function() {
        const form = document.getElementById('searchForm');
        const submitBtn = document.getElementById('searchBtn');
        const btnTextSpan = submitBtn.querySelector('.btn-text');
        const originalText = btnTextSpan.textContent;
        const arrowIcon = submitBtn.querySelector('.fa-arrow-right');

        form.addEventListener('submit', function(e) {
            // Désactiver le bouton pour éviter les doubles soumissions
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }

            // Remplacer le contenu du bouton par un loader
            submitBtn.disabled = true;
            // Supprimer l'icône flèche si elle existe, ou la remplacer
            if (arrowIcon) arrowIcon.style.display = 'none';
            // Ajouter le spinner
            const spinner = document.createElement('span');
            spinner.className = 'spinner';
            submitBtn.insertBefore(spinner, btnTextSpan);
            btnTextSpan.textContent = 'Recherche en cours...';
        });
    })();
</script>

@endsection
