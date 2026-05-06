@extends('front.layouts.app')

@section('title', 'Accès limité - Data Rocket')

@section('content')
<style>
    .blocked-page {
        min-height: 70vh;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .blocked-card {
        width: 100%;
        max-width: 540px;
        background: white;
        border-radius: 22px;
        padding: 30px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 55px rgba(15, 23, 42, .12);
        text-align: center;
    }

    .blocked-icon {
        width: 70px;
        height: 70px;
        border-radius: 999px;
        background: #fee2e2;
        color: #991b1b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        margin: 0 auto 18px;
    }

    .blocked-card h1 {
        font-size: 26px;
        font-weight: 800;
        margin-bottom: 10px;
        color: #0f172a;
    }

    .blocked-card p {
        color: #64748b;
        line-height: 1.7;
    }

    .blocked-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 22px;
    }

    .blocked-btn {
        background: #0053b3;
        color: white;
        padding: 11px 18px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
    }

    .blocked-btn.secondary {
        background: #0f172a;
    }
</style>

<section class="blocked-page">
    <div class="blocked-card">
        <div class="blocked-icon">⚠️</div>

        <h1>Accès limité</h1>

        <p>
            {{ $message ?? 'Vos recherches gratuites sont épuisées.' }}
        </p>

        <p>
            Recherche demandée : <strong>{{ $q ?? '-' }}</strong>
        </p>

        <div class="blocked-actions">
            <a href="{{ route('login') }}" class="blocked-btn">
                Se connecter
            </a>

            <a href="{{ route('register') }}" class="blocked-btn secondary">
                Créer un compte
            </a>
        </div>
    </div>
</section>
@endsection