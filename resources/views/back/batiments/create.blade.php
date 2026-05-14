@extends('back.layouts.app')

@section('title', 'Ajouter bâtiment')

@section('content')
<h1>Ajouter bâtiment</h1>

<form method="POST" action="{{ route('back.batiments.store') }}" class="form-card">
    @csrf
    @include('back.batiments._form')
    <div class="actions">
        <button class="btn-primary">Enregistrer</button>
        <a href="{{ route('back.batiments.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection