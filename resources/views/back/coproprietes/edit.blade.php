@extends('back.layouts.app')

@section('title', 'Modifier copropriété')

@section('content')
<h1>Modifier copropriété</h1>

<form method="POST" action="{{ route('back.coproprietes.update', $copropriete) }}" class="form-card">
    @csrf
    @method('PUT')
    @include('back.coproprietes._form')

    <div class="actions">
        <button class="btn-primary">Mettre à jour</button>
        <a href="{{ route('back.coproprietes.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection