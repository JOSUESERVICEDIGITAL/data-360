@extends('back.layouts.app')

@section('title', 'Résultat recherche')

@section('content')
<h1>Résultat recherche</h1>

<div class="card">
    <p><strong>Requête :</strong> {{ $recherche->requete }}</p>
    <p><strong>Statut :</strong> {{ $recherche->statut }}</p>
    <p><strong>Message :</strong> {{ $recherche->message }}</p>
</div>

@if($recherche->adresse)
    <div class="card" style="margin-top:20px;">
        <h2>Adresse</h2>
        <p>{{ $recherche->adresse->adresse_complete }}</p>
        <p>{{ $recherche->adresse->code_postal }} {{ $recherche->adresse->ville }}</p>
    </div>
@endif

<div class="actions">
    <a href="{{ route('back.recherches.index') }}" class="btn-secondary">Retour</a>
</div>
@endsection