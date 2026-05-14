@extends('back.layouts.app')

@section('title', 'Ajouter copropriété')

@section('content')
<h1>Ajouter copropriété</h1>

<form method="POST" action="{{ route('back.coproprietes.store') }}" class="form-card">
    @csrf
    @include('back.coproprietes._form')

    <div class="actions">
        <button class="btn-primary">Enregistrer</button>
        <a href="{{ route('back.coproprietes.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection