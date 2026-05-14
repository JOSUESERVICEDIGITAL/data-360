@extends('back.layouts.app')

@section('title', 'Adresses')

@section('content')
<div class="card-header">
    <h1>Adresses</h1>
    <a href="{{ route('back.adresses.create') }}" class="btn-primary">Ajouter</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Adresse</th>
                <th>Ville</th>
                <th>Code postal</th>
                <th>Source</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($adresses as $adresse)
                <tr>
                    <td>{{ $adresse->adresse_complete }}</td>
                    <td>{{ $adresse->ville }}</td>
                    <td>{{ $adresse->code_postal }}</td>
                    <td>{{ $adresse->source }}</td>
                    <td>
                        <a href="{{ route('back.adresses.show', $adresse) }}">Voir</a> |
                        <a href="{{ route('back.adresses.edit', $adresse) }}">Modifier</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Aucune adresse.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $adresses->links() }}
</div>
@endsection