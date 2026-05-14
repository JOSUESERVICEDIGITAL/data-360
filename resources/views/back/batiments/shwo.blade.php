@extends('back.layouts.app')

@section('title', 'Détail bâtiment')

@section('content')
<h1>Bâtiment</h1>

<div class="card">
    <p><strong>Adresse :</strong> {{ $batiment->adresse->adresse_complete ?? '-' }}</p>
    <p><strong>Type :</strong> {{ $batiment->type_batiment }}</p>
    <p><strong>Année :</strong> {{ $batiment->annee_construction }}</p>
    <p><strong>Logements :</strong> {{ $batiment->nombre_logements }}</p>
    <p><strong>Niveaux :</strong> {{ $batiment->nombre_niveaux }}</p>
    <p><strong>DPE :</strong> {{ $batiment->classe_dpe }}</p>
</div>
@endsection