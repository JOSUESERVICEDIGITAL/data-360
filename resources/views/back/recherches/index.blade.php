@extends('back.layouts.app')

@section('title', 'Recherches')

@section('content')
<div class="card-header">
    <h1>Recherches</h1>
    <a href="{{ route('back.recherches.create') }}" class="btn-primary">Nouvelle recherche</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Requête</th>
                <th>Statut</th>
                <th>Message</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($recherches as $recherche)
            <tr>
                <td>{{ $recherche->requete }}</td>
                <td>{{ $recherche->statut }}</td>
                <td>{{ $recherche->message }}</td>
                <td>{{ $recherche->created_at?->format('d/m/Y H:i') }}</td>
                <td><a href="{{ route('back.recherches.show', $recherche) }}">Voir</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Aucune recherche.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $recherches->links() }}
</div>
@endsection