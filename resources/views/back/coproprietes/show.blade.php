@extends('back.layouts.app')

@section('title', 'Détail copropriété')

@section('content')
<h1>{{ $copropriete->nom_copropriete ?? 'Copropriété' }}</h1>

<div class="card">
    <p><strong>Adresse :</strong> {{ $copropriete->adresse->adresse_complete ?? '-' }}</p>
    <p><strong>Bâtiment :</strong> {{ $copropriete->batiment?->id ? '#'.$copropriete->batiment->id : '-' }}</p>
    <p><strong>Immatriculation :</strong> {{ $copropriete->numero_immatriculation ?? '-' }}</p>
    <p><strong>SIREN :</strong> {{ $copropriete->siren_copropriete ?? '-' }}</p>
    <p><strong>Lots total :</strong> {{ $copropriete->nombre_lots_total ?? '-' }}</p>
    <p><strong>Lots habitation :</strong> {{ $copropriete->nombre_lots_habitation ?? '-' }}</p>
    <p><strong>Nombre bâtiments :</strong> {{ $copropriete->nombre_batiments ?? '-' }}</p>
    <p><strong>Statut :</strong> {{ $copropriete->statut ?? '-' }}</p>

    <h3>Syndics associés</h3>
    <ul>
        @forelse($copropriete->syndics as $syndic)
            <li>{{ $syndic->nom }} — SIREN : {{ $syndic->siren ?? '-' }}</li>
        @empty
            <li>Aucun syndic associé.</li>
        @endforelse
    </ul>

    <div class="actions">
        <a href="{{ route('back.coproprietes.edit', $copropriete) }}" class="btn-primary">Modifier</a>
        <a href="{{ route('back.coproprietes.index') }}" class="btn-secondary">Retour</a>
    </div>
</div>
@endsection