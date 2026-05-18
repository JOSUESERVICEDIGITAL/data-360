@extends('back.layouts.app')

@section('title', 'Copropriétés')

@section('content')
<div class="card-header">
    <h1>Copropriétés</h1>
    <a href="{{ route('back.coproprietes.create') }}" class="btn-primary">Ajouter </a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Immatriculation</th>
                <th>SIREN</th>
                <th>Lots</th>
                <th>Syndics</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($coproprietes as $copropriete)
            <tr>
                <td>{{ $copropriete->nom_copropriete ?? '-' }}</td>
                <td>{{ $copropriete->numero_immatriculation ?? '-' }}</td>
                <td>{{ $copropriete->siren_copropriete ?? '-' }}</td>
                <td>{{ $copropriete->nombre_lots_total ?? '-' }}</td>
                <td>{{ $copropriete->syndics->pluck('nom')->join(', ') ?: '-' }}</td>
                <td>
                    <a href="{{ route('back.coproprietes.show', $copropriete) }}">Voir</a> |
                    <a href="{{ route('back.coproprietes.edit', $copropriete) }}">Modifier</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucune copropriété.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $coproprietes->links() }}
</div>
@endsection