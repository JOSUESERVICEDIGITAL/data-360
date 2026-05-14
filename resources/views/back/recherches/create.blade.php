@extends('back.layouts.app')

@section('title', 'Recherche adresse')

@section('content')
<h1>Recherche adresse</h1>

<form method="POST" action="{{ route('back.recherches.search') }}" class="form-card">
    @csrf

    <div class="form-group">
        <label>Adresse à rechercher</label>
        <input name="requete" placeholder="Ex: 15 rue Victor Hugo, 75000 Paris" value="{{ old('requete') }}" required>
    </div>

    <div class="actions">
        <button class="btn-primary">Rechercher</button>
    </div>
</form>
@endsection