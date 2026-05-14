@extends('back.layouts.app')

@section('title', 'Modifier adresse')

@section('content')
<h1>Modifier adresse</h1>

<form method="POST" action="{{ route('back.adresses.update', $adresse) }}" class="form-card">
    @csrf
    @method('PUT')

    @include('back.adresses._form')

    <div class="actions">
        <button class="btn-primary">Mettre à jour</button>
        <a href="{{ route('back.adresses.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection