@extends('front.layouts.app')

@section('title', 'Data Rocket - Données des adresses françaises')

@section('content')

<section class="hero" id="carte">
    <div class="container">
        <h1>Toutes les données des adresses françaises</h1>
        <p>
            Accédez à des informations détaillées sur les bâtiments, copropriétés,
            syndics, SIREN, niveaux, logements et années de construction.
        </p>

        <form method="GET" action="{{ route('front.recherche') }}">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Saisir une adresse..."
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Tester une adresse
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

<!-- <section class="section" id="demo">
    <div class="container two-col">
        <div class="img-placeholder">
            Carte interactive avec données bâtiment, copropriété et syndic
        </div>

        <div>
            <h2>Visualisez vos Opportunités sur une Carte Interactive Avancée</h2>
            <p>Analysez chaque adresse avec des données enrichies :</p>

            <ul>
                <li>Année de construction</li>
                <li>Nombre de niveaux</li>
                <li>Nombre de logements</li>
                <li>Copropriété et immatriculation</li>
                <li>Syndic associé et SIREN</li>
                <li>DPE et données énergétiques</li>
            </ul>

            <a href="{{ route('front.demo') }}" class="btn btn-outline">
                Voir la démo
            </a>
        </div>
    </div>
</section> -->

<!-- <section class="section soft-bg">
    <div class="container">
        <div class="center-text">
            <h2>Adaptez Data Rocket à vos Stratégies Commerciales</h2>
            <p>
                Ciblez les immeubles collectifs, les résidences, les syndics
                et les opportunités de rénovation.
            </p>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-building"></i></div>
                <h3>Bâtiments</h3>
                <p>Type, niveaux, logements, année et potentiel travaux.</p>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-city"></i></div>
                <h3>Copropriétés</h3>
                <p>Nom résidence, immatriculation, lots et logements.</p>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-user-tie"></i></div>
                <h3>Syndics</h3>
                <p>Syndics associés, SIREN, SIRET et adresse entreprise.</p>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
                <h3>Prospection</h3>
                <p>Repérez rapidement les meilleures opportunités.</p>
            </div>
        </div>
    </div>
</section> -->

<!-- <section class="cta" id="inscription">
    <div class="container">
        <h2>Prêt à lancer votre moteur d’adresse ?</h2>
        <p>
            Recherchez une adresse et obtenez une fiche claire comme Data Rocket.
        </p>

        <div class="cta-actions">
            <a href="{{ route('register') }}" class="btn btn-white">Créer un compte</a>
            <a href="{{ route('front.demo') }}" class="btn btn-white-outline">Demander une démo</a>
        </div>
    </div>
</section> -->

@endsection