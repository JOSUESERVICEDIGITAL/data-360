@extends('back.layouts.app')

@section('title', 'Modifier bâtiment')

@section('content')
<h1>Modifier bâtiment</h1>

<form method="POST" action="{{ route('back.batiments.update', $batiment) }}" class="form-card">
    @csrf
    @method('PUT')
    @include('back.batiments._form')
    <div class="actions">
        <button class="btn-primary">Mettre à jour</button>
        <a href="{{ route('back.batiments.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection