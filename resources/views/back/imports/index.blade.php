@extends('back.layouts.app')

@section('title', 'Imports')

@section('content')
<div class="card-header">
    <h1>Imports CSV</h1>
    <a href="{{ route('back.imports.create') }}" class="btn-primary">Importer</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Fichier</th>
                <th>Statut</th>
                <th>Total lignes</th>
                <th>Lignes traitées</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        @forelse($imports as $import)
            <tr>
                <td>{{ $import->nom_fichier }}</td>
                <td>{{ $import->statut }}</td>
                <td>{{ $import->total_lignes }}</td>
                <td>{{ $import->lignes_traitees }}</td>
                <td>{{ $import->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun import.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $imports->links() }}
</div>
@endsection