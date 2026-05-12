@extends('back.layouts.app')

@section('title', 'Créer une notification')

@section('content')
<div class="sec-page" style="max-width: 800px; margin: 0 auto;">
    <div class="sec-header">
        <div class="sec-title">
            <h1>➕ Créer une notification</h1>
            <p>Envoyez une notification aux utilisateurs</p>
        </div>
        <a href="{{ route('back.notifications.index') }}" class="sec-btn gray">← Retour</a>
    </div>

    <div class="sec-card">
        <form method="POST" action="{{ route('back.notifications.store') }}">
            @csrf

            <div class="form-group">
                <label>Type de notification</label>
                <select name="type" class="form-input" required>
                    <option value="info" {{ old('type') == 'info' ? 'selected' : '' }}>ℹ️ Info</option>
                    <option value="success" {{ old('type') == 'success' ? 'selected' : '' }}>✅ Succès</option>
                    <option value="warning" {{ old('type') == 'warning' ? 'selected' : '' }}>⚠️ Alerte</option>
                    <option value="danger" {{ old('type') == 'danger' ? 'selected' : '' }}>🔴 Danger</option>
                    <option value="admin" {{ old('type') == 'admin' ? 'selected' : '' }}>👑 Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="title" class="form-input" value="{{ old('title') }}" required>
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" class="form-input" rows="4" required>{{ old('message') }}</textarea>
            </div>

            <div class="form-group">
                <label>Lien (optionnel)</label>
                <input type="url" name="link" class="form-input" value="{{ old('link') }}" placeholder="https://...">
            </div>

            <div class="form-group">
                <label>Icône (optionnel)</label>
                <input type="text" name="icon" class="form-input" value="{{ old('icon') }}" placeholder="fa-solid fa-bell">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_global" value="1" {{ old('is_global') ? 'checked' : '' }}>
                        🌍 Notification globale (tous les utilisateurs)
                    </label>
                </div>
            </div>

            <div class="form-group" id="userSelect" style="display: {{ old('is_global') ? 'none' : 'block' }};">
                <label>Utilisateur destinataire</label>
                <select name="user_id" class="form-input">
                    <option value="">-- Sélectionner un utilisateur --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Date d'expiration (optionnelle)</label>
                <input type="datetime-local" name="expires_at" class="form-input" value="{{ old('expires_at') }}">
            </div>

            <div class="form-actions">
                <button type="submit" class="sec-btn green">📨 Envoyer la notification</button>
                <a href="{{ route('back.notifications.index') }}" class="sec-btn gray">Annuler</a>
            </div>
        </form>
    </div>
</div>

<style>
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1e293b; }
    .form-input { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; }
    .form-input:focus { outline: none; border-color: #0053b3; }
    .form-row { display: flex; gap: 1rem; }
    .form-actions { display: flex; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
</style>

<script>
    document.querySelector('input[name="is_global"]').addEventListener('change', function() {
        const userSelect = document.getElementById('userSelect');
        userSelect.style.display = this.checked ? 'none' : 'block';
    });
</script>
@endsection