@extends('back.layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1>Tableau de bord</h1>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Adresses</h3>
        <strong>{{ $totalAdresses ?? 0 }}</strong>
    </div>

    <div class="stat-card">
        <h3>Bâtiments</h3>
        <strong>{{ $totalBatiments ?? 0 }}</strong>
    </div>

    <div class="stat-card">
        <h3>Copropriétés</h3>
        <strong>{{ $totalCoproprietes ?? 0 }}</strong>
    </div>

    <div class="stat-card">
        <h3>Syndics</h3>
        <strong>{{ $totalSyndics ?? 0 }}</strong>
    </div>

    <div class="stat-card">
        <h3>Recherches</h3>
        <strong>{{ $totalRecherches ?? 0 }}</strong>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Dernières recherches</h2>
        <a href="{{ route('back.recherches.create') }}" class="btn-primary">Nouvelle recherche</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Adresse</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($dernieresRecherches ?? []) as $recherche)
                <tr>
                    <td>{{ $recherche->requete }}</td>
                    <td>{{ $recherche->statut }}</td>
                    <td>{{ $recherche->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Aucune recherche.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection