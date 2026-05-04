@extends('back.layouts.app')

@section('title', 'Modifier syndic')

@section('content')
<h1>Modifier syndic</h1>

<form method="POST" action="{{ route('back.syndics.update', $syndic) }}" class="form-card">
    @csrf
    @method('PUT')
    @include('back.syndics._form')

    <div class="actions">
        <button class="btn-primary">Mettre à jour</button>
        <a href="{{ route('back.syndics.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection