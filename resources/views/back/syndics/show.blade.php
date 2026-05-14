@extends('back.layouts.app')

@section('title', 'Détail syndic')

@section('content')
<h1>{{ $syndic->nom ?? 'Syndic' }}</h1>

<div class="card">
    <p><strong>SIREN :</strong> {{ $syndic->siren ?? '-' }}</p>
    <p><strong>SIRET :</strong> {{ $syndic->siret ?? '-' }}</p>
    <p><strong>Forme juridique :</strong> {{ $syndic->forme_juridique ?? '-' }}</p>
    <p><strong>Activité :</strong> {{ $syndic->activite ?? '-' }}</p>
    <p><strong>Adresse :</strong> {{ $syndic->adresse_complete ?? '-' }}</p>
    <p><strong>Ville :</strong> {{ $syndic->code_postal }} {{ $syndic->ville }}</p>
    <p><strong>Téléphone :</strong> {{ $syndic->telephone ?? '-' }}</p>
    <p><strong>Email :</strong> {{ $syndic->email ?? '-' }}</p>

    <h3>Copropriétés liées</h3>
    <ul>
        @forelse($syndic->coproprietes as $copropriete)
            <li>{{ $copropriete->nom_copropriete ?? 'Copropriété' }} — {{ $copropriete->siren_copropriete ?? '-' }}</li>
        @empty
            <li>Aucune copropriété liée.</li>
        @endforelse
    </ul>

    <div class="actions">
        <a href="{{ route('back.syndics.edit', $syndic) }}" class="btn-primary">Modifier</a>
        <a href="{{ route('back.syndics.index') }}" class="btn-secondary">Retour</a>
    </div>
</div>
@endsection