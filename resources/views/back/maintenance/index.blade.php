@extends('back.layouts.app')

@section('title', 'Maintenance DB')

@section('content')

<style>
    .maintenance-page{
        padding: 24px;
    }

    .maintenance-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:24px;
        gap:20px;
        flex-wrap:wrap;
    }

    .maintenance-title h1{
        margin:0;
        font-size:30px;
        font-weight:800;
        color:#111827;
    }

    .maintenance-title p{
        margin-top:8px;
        color:#6b7280;
        font-size:14px;
    }

    .maintenance-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
        gap:20px;
    }

    .maintenance-card{
        background:#fff;
        border-radius:20px;
        padding:24px;
        border:1px solid #e5e7eb;
        box-shadow:0 8px 25px rgba(0,0,0,0.05);
        transition:0.25s ease;
        position:relative;
        overflow:hidden;
    }

    .maintenance-card:hover{
        transform:translateY(-4px);
        box-shadow:0 15px 35px rgba(0,0,0,0.08);
    }

    .maintenance-icon{
        width:60px;
        height:60px;
        border-radius:16px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:24px;
        margin-bottom:18px;
    }

    .icon-red{
        background:#fee2e2;
        color:#dc2626;
    }

    .icon-blue{
        background:#dbeafe;
        color:#2563eb;
    }

    .icon-orange{
        background:#ffedd5;
        color:#ea580c;
    }

    .icon-green{
        background:#dcfce7;
        color:#16a34a;
    }

    .maintenance-card h3{
        margin:0;
        font-size:20px;
        font-weight:700;
        color:#111827;
    }

    .maintenance-card p{
        margin-top:10px;
        color:#6b7280;
        line-height:1.6;
        font-size:14px;
    }

    .maintenance-actions{
        margin-top:24px;
    }

    .btn-maintenance{
        width:100%;
        border:none;
        border-radius:14px;
        padding:14px;
        font-size:14px;
        font-weight:700;
        cursor:pointer;
        transition:0.2s ease;
    }

    .btn-maintenance:hover{
        transform:translateY(-2px);
    }

    .btn-danger{
        background:#dc2626;
        color:#fff;
    }

    .btn-danger:hover{
        background:#b91c1c;
    }

    .btn-warning{
        background:#f59e0b;
        color:#fff;
    }

    .btn-warning:hover{
        background:#d97706;
    }

    .btn-primary{
        background:#2563eb;
        color:#fff;
    }

    .btn-primary:hover{
        background:#1d4ed8;
    }

    .btn-success{
        background:#16a34a;
        color:#fff;
    }

    .btn-success:hover{
        background:#15803d;
    }

    .maintenance-warning{
        margin-top:30px;
        background:#fef3c7;
        border:1px solid #fde68a;
        padding:18px;
        border-radius:16px;
        color:#92400e;
        font-size:14px;
    }

    .maintenance-warning strong{
        display:block;
        margin-bottom:8px;
        font-size:15px;
    }

</style>

<div class="maintenance-page">

    <div class="maintenance-header">
        <div class="maintenance-title">
            <h1>Maintenance Database</h1>
            <p>
                Gérez les tables système, nettoyez les données temporaires
                et optimisez votre plateforme SaaS.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div style="margin-bottom:20px;padding:16px;border-radius:14px;background:#dcfce7;color:#166534;">
            {{ session('success') }}
        </div>
    @endif

    <div class="maintenance-grid">

        {{-- RECHERCHES --}}
        <div class="maintenance-card">

            <div class="maintenance-icon icon-red">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <h3>Réinitialiser les recherches</h3>

            <p>
                Supprime tout l’historique des recherches utilisateurs
                afin de libérer de l’espace sur Railway.
            </p>

            <div class="maintenance-actions">
                <form
                    action="{{ route('back.maintenance.recherches.clear') }}"
                    method="POST"
                    onsubmit="return confirm('Supprimer toutes les recherches ?')"
                >
                    @csrf
                    @method('DELETE')

                    <button class="btn-maintenance btn-danger">
                        Supprimer les recherches
                    </button>
                </form>
            </div>

        </div>

        {{-- CACHE --}}
        <div class="maintenance-card">

            <div class="maintenance-icon icon-blue">
                <i class="fa-solid fa-database"></i>
            </div>

            <h3>Vider le cache</h3>

            <p>
                Nettoie la table cache et les données temporaires
                afin d’éviter les saturations mémoire.
            </p>

            <div class="maintenance-actions">
                <form
                    action="{{ route('back.maintenance.cache.clear') }}"
                    method="POST"
                    onsubmit="return confirm('Vider le cache ?')"
                >
                    @csrf
                    @method('DELETE')

                    <button class="btn-maintenance btn-primary">
                        Nettoyer le cache
                    </button>
                </form>
            </div>

        </div>

        {{-- JOBS --}}
        <div class="maintenance-card">

            <div class="maintenance-icon icon-orange">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>

            <h3>Nettoyer les jobs</h3>

            <p>
                Supprime les jobs bloqués, failed_jobs
                et tâches système inutilisées.
            </p>

            <div class="maintenance-actions">
                <form
                    action="{{ route('back.maintenance.jobs.clear') }}"
                    method="POST"
                    onsubmit="return confirm('Supprimer les jobs système ?')"
                >
                    @csrf
                    @method('DELETE')

                    <button class="btn-maintenance btn-warning">
                        Nettoyer les jobs
                    </button>
                </form>
            </div>

        </div>

        {{-- OPTIMIZE --}}
        <div class="maintenance-card">

            <div class="maintenance-icon icon-green">
                <i class="fa-solid fa-bolt"></i>
            </div>

            <h3>Optimiser Laravel</h3>

            <p>
                Nettoie et reconstruit les caches Laravel
                pour améliorer les performances du SaaS.
            </p>

            <div class="maintenance-actions">
                <form
                    action="{{ route('back.maintenance.optimize') }}"
                    method="POST"
                    onsubmit="return confirm('Optimiser Laravel ?')"
                >
                    @csrf

                    <button class="btn-maintenance btn-success">
                        Optimiser maintenant
                    </button>
                </form>
            </div>

        </div>

    </div>

    <div class="maintenance-warning">
        <strong>⚠ Attention</strong>

        Ces actions sont irréversibles.
        Utilisez uniquement ce module pour la maintenance système
        et l’optimisation de votre plateforme.
    </div>

</div>

@endsection