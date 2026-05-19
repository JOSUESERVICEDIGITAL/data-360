```blade
@extends('back.layouts.app')

@section('title', 'Maintenance DB')

@section('content')

<style>
    .maintenance-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
        gap:24px;
        margin-top:25px;
    }

    .maintenance-card{
        background:#fff;
        border-radius:22px;
        padding:24px;
        box-shadow:0 10px 30px rgba(15,23,42,.06);
        border:1px solid #edf2f7;
        position:relative;
        overflow:hidden;
    }

    .maintenance-card::before{
        content:'';
        position:absolute;
        top:0;
        left:0;
        width:100%;
        height:5px;
        background:linear-gradient(90deg,#2563eb,#7c3aed);
    }

    .maintenance-title{
        display:flex;
        align-items:center;
        gap:12px;
        margin-bottom:20px;
    }

    .maintenance-icon{
        width:48px;
        height:48px;
        border-radius:14px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
        color:white;
        background:linear-gradient(135deg,#2563eb,#7c3aed);
    }

    .maintenance-title h3{
        margin:0;
        font-size:18px;
        font-weight:700;
        color:#0f172a;
    }

    .maintenance-number{
        font-size:34px;
        font-weight:800;
        color:#111827;
        margin-bottom:8px;
    }

    .maintenance-text{
        color:#64748b;
        font-size:14px;
        margin-bottom:20px;
    }

    .maintenance-actions{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
    }

    .btn-maintenance{
        border:none;
        border-radius:12px;
        padding:12px 16px;
        font-weight:600;
        cursor:pointer;
        transition:.2s;
        font-size:14px;
    }

    .btn-maintenance:hover{
        transform:translateY(-2px);
    }

    .btn-danger{
        background:#ef4444;
        color:white;
    }

    .btn-warning{
        background:#f59e0b;
        color:white;
    }

    .btn-primary{
        background:#2563eb;
        color:white;
    }

    .btn-dark{
        background:#111827;
        color:white;
    }

    .maintenance-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:20px;
        flex-wrap:wrap;
        gap:20px;
    }

    .maintenance-header h1{
        margin:0;
        font-size:30px;
        font-weight:800;
        color:#0f172a;
    }

    .maintenance-alert{
        padding:16px 18px;
        border-radius:14px;
        margin-bottom:20px;
        font-weight:600;
    }

    .maintenance-success{
        background:#dcfce7;
        color:#166534;
    }

    .maintenance-error{
        background:#fee2e2;
        color:#991b1b;
    }

    .maintenance-warning{
        background:#fff7ed;
        color:#9a3412;
        border:1px solid #fdba74;
        margin-top:30px;
    }
</style>

<div class="maintenance-header">
    <div>
        <h1>
            <i class="fa-solid fa-screwdriver-wrench"></i>
            Maintenance Database
        </h1>
    </div>
</div>

@if(session('success'))
    <div class="maintenance-alert maintenance-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="maintenance-alert maintenance-error">
        {{ session('error') }}
    </div>
@endif

<div class="maintenance-grid">

    {{-- RECHERCHES --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <div>
                <h3>Recherches</h3>
            </div>
        </div>

        <div class="maintenance-number">
            {{ number_format($recherchesCount) }}
        </div>

        <div class="maintenance-text">
            Historique des recherches utilisateurs.
        </div>

        <div class="maintenance-actions">

            <form method="POST"
                  action="{{ route('back.maintenance.recherches.clear') }}"
                  onsubmit="return confirm('Supprimer toutes les recherches ?')">
                @csrf
                @method('DELETE')

                <button class="btn-maintenance btn-danger">
                    <i class="fa-solid fa-trash"></i>
                    Vider
                </button>
            </form>

        </div>

    </div>

    {{-- CACHE --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-database"></i>
            </div>

            <div>
                <h3>Cache Laravel</h3>
            </div>
        </div>

        <div class="maintenance-number">
            {{ number_format($cacheCount) }}
        </div>

        <div class="maintenance-text">
            Nettoyage du cache système et base de données.
        </div>

        <div class="maintenance-actions">

            <form method="POST"
                  action="{{ route('back.maintenance.cache.clear') }}"
                  onsubmit="return confirm('Vider le cache ?')">

                @csrf
                @method('DELETE')

                <button class="btn-maintenance btn-warning">
                    <i class="fa-solid fa-broom"></i>
                    Nettoyer
                </button>

            </form>

        </div>

    </div>

    {{-- JOBS --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-gears"></i>
            </div>

            <div>
                <h3>Jobs Queue</h3>
            </div>
        </div>

        <div class="maintenance-number">
            {{ number_format($jobsCount + $failedJobsCount) }}
        </div>

        <div class="maintenance-text">
            Jobs Laravel et erreurs de queue.
        </div>

        <div class="maintenance-actions">

            <form method="POST"
                  action="{{ route('back.maintenance.jobs.clear') }}"
                  onsubmit="return confirm('Supprimer tous les jobs ?')">

                @csrf
                @method('DELETE')

                <button class="btn-maintenance btn-danger">
                    <i class="fa-solid fa-trash"></i>
                    Purger
                </button>

            </form>

        </div>

    </div>

    {{-- OPTIMIZE --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-bolt"></i>
            </div>

            <div>
                <h3>Optimisation Laravel</h3>
            </div>
        </div>

        <div class="maintenance-number">
            ⚡
        </div>

        <div class="maintenance-text">
            Clear cache + refresh optimisations Laravel.
        </div>

        <div class="maintenance-actions">

            <form method="POST"
                  action="{{ route('back.maintenance.optimize') }}"
                  onsubmit="return confirm('Optimiser Laravel ?')">

                @csrf

                <button class="btn-maintenance btn-primary">
                    <i class="fa-solid fa-rocket"></i>
                    Optimiser
                </button>

            </form>

        </div>

    </div>

    {{-- ADRESSES --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-location-dot"></i>
            </div>

            <div>
                <h3>Adresses</h3>
            </div>
        </div>

        <div class="maintenance-number">
            {{ number_format($adressesCount) }}
        </div>

        <div class="maintenance-text">
            Toutes les adresses enregistrées.
        </div>

    </div>

    {{-- BATIMENTS --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-building"></i>
            </div>

            <div>
                <h3>Bâtiments</h3>
            </div>
        </div>

        <div class="maintenance-number">
            {{ number_format($batimentsCount) }}
        </div>

        <div class="maintenance-text">
            Ensemble des bâtiments présents.
        </div>

    </div>

    {{-- COPROPRIETES --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-city"></i>
            </div>

            <div>
                <h3>Copropriétés</h3>
            </div>
        </div>

        <div class="maintenance-number">
            {{ number_format($coproprietesCount) }}
        </div>

        <div class="maintenance-text">
            Gestion des copropriétés.
        </div>

    </div>

    {{-- SYNDICS --}}
    <div class="maintenance-card">

        <div class="maintenance-title">
            <div class="maintenance-icon">
                <i class="fa-solid fa-user-tie"></i>
            </div>

            <div>
                <h3>Syndics</h3>
            </div>
        </div>

        <div class="maintenance-number">
            {{ number_format($syndicsCount) }}
        </div>

        <div class="maintenance-text">
            Syndics enregistrés dans la plateforme.
        </div>

    </div>

</div>

<div class="maintenance-alert maintenance-warning">

    <strong>
        <i class="fa-solid fa-triangle-exclamation"></i>
        Attention :
    </strong>

    Certaines actions sont irréversibles et peuvent supprimer définitivement
    les données de Railway.

</div>

@endsection
```
