@extends('back.layouts.app')

@section('title', 'Ajouter une adresse')

@section('content')
<h1>Ajouter une adresse</h1>

<form method="POST" action="{{ route('back.adresses.store') }}" class="form-card">
    @csrf
    @include('back.adresses._form')

    <div class="actions">
        <button class="btn-primary">Enregistrer</button>
        <a href="{{ route('back.adresses.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection