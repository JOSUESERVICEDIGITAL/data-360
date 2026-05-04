@extends('back.layouts.app')

@section('title', 'Importer CSV')

@section('content')
<h1>Importer un CSV</h1>

<form method="POST" action="{{ route('back.imports.store') }}" enctype="multipart/form-data" class="form-card">
    @csrf

    <div class="form-group">
        <label>Fichier CSV</label>
        <input type="file" name="file" accept=".csv,.txt" required>
    </div>

    <div class="actions">
        <button class="btn-primary">Importer</button>
        <a href="{{ route('back.imports.index') }}" class="btn-secondary">Retour</a>
    </div>
</form>
@endsection