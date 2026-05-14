@extends('back.layouts.app')

@section('title', 'Détail adresse')

@section('content')
<h1>{{ $adresse->adresse_complete }}</h1>

<div class="card">
    <p><strong>Ville :</strong> {{ $adresse->ville }}</p>
    <p><strong>Code postal :</strong> {{ $adresse->code_postal }}</p>
    <p><strong>Coordonnées :</strong> {{ $adresse->latitude }}, {{ $adresse->longitude }}</p>

    <div class="actions">
        <a href="{{ route('back.adresses.edit', $adresse) }}" class="btn-primary">Modifier</a>
        <a href="{{ route('back.adresses.index') }}" class="btn-secondary">Retour</a>
    </div>
</div>
@endsection