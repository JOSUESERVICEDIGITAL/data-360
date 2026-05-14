@extends('back.layouts.app')

@section('title', 'Ajouter syndic')

@section('content')
<h1>Ajouter syndic</h1>

<form method="POST" action="{{ route('back.syndics.store') }}" class="form-card">
    @csrf
    @include('back.syndics._form')

    <div class="actions">
        <button class="btn-primary">Enregistrer</button>
        <a href="{{ route('back.syndics.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection