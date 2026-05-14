@extends('back.layouts.app')

@section('title', 'Bâtiments')

@section('content')
<div class="card-header">
    <h1>Bâtiments</h1>
    <a href="{{ route('back.batiments.create') }}" class="btn-primary">Ajouter</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Adresse</th>
                <th>Type</th>
                <th>Année</th>
                <th>Logements</th>
                <th>Niveaux</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($batiments as $batiment)
            <tr>
                <td>{{ $batiment->adresse->adresse_complete ?? '-' }}</td>
                <td>{{ $batiment->type_batiment }}</td>
                <td>{{ $batiment->annee_construction }}</td>
                <td>{{ $batiment->nombre_logements }}</td>
                <td>{{ $batiment->nombre_niveaux }}</td>
                <td>
                    <a href="{{ route('back.batiments.show', $batiment) }}">Voir</a> |
                    <a href="{{ route('back.batiments.edit', $batiment) }}">Modifier</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun bâtiment.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $batiments->links() }}
</div>
@endsection