@extends('back.layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="sec-page">
    <div class="sec-header">
        <div class="sec-title">
            <h1>🔔 Gestion des notifications</h1>
            <p>Créez et gérez les notifications pour les utilisateurs</p>
        </div>
        <a href="{{ route('back.notifications.create') }}" class="sec-btn green">
            ➕ Nouvelle notification
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="sec-card">
        <form method="GET" class="sec-search">
            <select name="type" class="sec-input" style="width: auto;">
                <option value="">Tous les types</option>
                <option value="admin" {{ request('type') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Succès</option>
                <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Alerte</option>
                <option value="danger" {{ request('type') == 'danger' ? 'selected' : '' }}>Danger</option>
            </select>
            <select name="user_id" class="sec-input" style="width: auto;">
                <option value="">Tous les utilisateurs</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
            <select name="is_global" class="sec-input" style="width: auto;">
                <option value="">Toutes</option>
                <option value="1" {{ request('is_global') == '1' ? 'selected' : '' }}>Globales</option>
                <option value="0" {{ request('is_global') == '0' ? 'selected' : '' }}>Personnalisées</option>
            </select>
            <button class="sec-btn" type="submit">Filtrer</button>
            <a href="{{ route('back.notifications.index') }}" class="sec-btn gray">Réinitialiser</a>
        </form>
    </div>

    <div class="sec-card">
        <div class="sec-table-wrap">
            <table class="sec-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Titre</th>
                        <th>Destinataire</th>
                        <th>Statut</th>
                        <th>Créée le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notif)
                        @php
                            $typeStyles = [
                                'admin' => ['color' => '#8b5cf6', 'icon' => 'fa-solid fa-shield-halved'],
                                'info' => ['color' => '#3b82f6', 'icon' => 'fa-solid fa-circle-info'],
                                'success' => ['color' => '#10b981', 'icon' => 'fa-solid fa-circle-check'],
                                'warning' => ['color' => '#f59e0b', 'icon' => 'fa-solid fa-triangle-exclamation'],
                                'danger' => ['color' => '#ef4444', 'icon' => 'fa-solid fa-circle-exclamation'],
                            ];
                            $style = $typeStyles[$notif->type] ?? $typeStyles['info'];
                        @endphp
                        <tr>
                            <td>{{ $notif->id }}</td>
                            <td>
                                <span class="badge" style="background: {{ $style['color'] }}20; color: {{ $style['color'] }}">
                                    <i class="{{ $style['icon'] }}"></i>
                                    {{ ucfirst($notif->type) }}
                                </span>
                            </td>
                            <td>{{ $notif->title }}</td>
                            <td>
                                @if($notif->is_global)
                                    <span class="badge blue">🌍 Tous les utilisateurs</span>
                                @else
                                    {{ $notif->user?->name ?? '-' }}
                                @endif
                            </td>
                            <td>
                                @if($notif->is_read)
                                    <span class="badge gray">✓ Lu</span>
                                @else
                                    <span class="badge warning">● Non lu</span>
                                @endif
                            </td>
                            <td>{{ $notif->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="actions" style="gap: 0.5rem;">
                                    <a href="{{ route('back.notifications.edit', $notif) }}" class="sec-btn blue">✏️</a>
                                    <form method="POST" action="{{ route('back.notifications.destroy', $notif) }}" onsubmit="return confirm('Supprimer ?')">
                                        @csrf @method('DELETE')
                                        <button class="sec-btn red">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center;">Aucune notification</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $notifications->links() }}
    </div>
</div>
@endsection