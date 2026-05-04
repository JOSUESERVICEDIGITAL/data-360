@extends('back.layouts.app')

@section('title', 'Syndics')

@section('content')
<div class="card-header">
    <h1>Syndics</h1>
    <a href="{{ route('back.syndics.create') }}" class="btn-primary">Ajouter</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>SIREN</th>
                <th>SIRET</th>
                <th>Ville</th>
                <th>Copropriétés</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($syndics as $syndic)
            <tr>
                <td>{{ $syndic->nom ?? '-' }}</td>
                <td>{{ $syndic->siren ?? '-' }}</td>
                <td>{{ $syndic->siret ?? '-' }}</td>
                <td>{{ $syndic->ville ?? '-' }}</td>
                <td>{{ $syndic->coproprietes_count ?? 0 }}</td>
                <td>
                    <a href="{{ route('back.syndics.show', $syndic) }}">Voir</a> |
                    <a href="{{ route('back.syndics.edit', $syndic) }}">Modifier</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun syndic.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $syndics->links() }}
</div>
@endsection