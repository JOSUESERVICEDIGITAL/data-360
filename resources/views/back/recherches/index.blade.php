@extends('back.layouts.app')

@section('title', 'Recherches | Data 360')

@section('content')
<style>
    .rch-page {
        padding: 24px
    }

    .rch-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 22px
    }

    .rch-title h1 {
        font-size: 30px;
        font-weight: 950;
        color: #0f172a;
        margin: 0
    }

    .rch-title p {
        color: #64748b;
        margin-top: 6px
    }

    .rch-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap
    }

    .rch-btn {
        border: 0;
        border-radius: 14px;
        padding: 11px 16px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: .2s;
    }

    .rch-btn-primary {
        background: #0053b3;
        color: white
    }

    .rch-btn-primary:hover {
        background: #003d85
    }

    .rch-btn-danger {
        background: #dc2626;
        color: white
    }

    .rch-btn-danger:hover {
        background: #b91c1c
    }

    .rch-btn-light {
        background: #f1f5f9;
        color: #334155
    }

    .rch-btn-dark {
        background: #0f172a;
        color: white
    }

    .rch-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 14px 35px rgba(15, 23, 42, .06);
    }

    .rch-toolbar {
        padding: 18px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
    }

    .rch-search {
        display: flex;
        gap: 10px;
        flex: 1;
        min-width: 280px;
    }

    .rch-search input {
        width: 100%;
        border: 1.5px solid #dbe3ef;
        border-radius: 14px;
        padding: 12px 14px;
        outline: none;
    }

    .rch-search input:focus {
        border-color: #0053b3;
        box-shadow: 0 0 0 4px rgba(0, 83, 179, .10);
    }

    .rch-stats {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .rch-pill {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 9px 13px;
        font-size: 13px;
        font-weight: 800;
        color: #334155;
    }

    .rch-table-wrap {
        overflow-x: auto
    }

    .rch-table {
        width: 100%;
        border-collapse: collapse;
    }

    .rch-table th {
        background: white;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .06em;
        text-align: left;
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .rch-table td {
        padding: 16px 15px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }

    .rch-table tr:hover td {
        background: #f8fafc;
    }

    .rch-main {
        font-weight: 900;
        color: #0f172a;
        max-width: 420px;
    }

    .rch-muted {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }

    .rch-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 900;
    }

    .rch-status.success {
        background: #dcfce7;
        color: #166534;
    }

    .rch-status.warning {
        background: #fef3c7;
        color: #92400e;
    }

    .rch-status.danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .rch-message {
        max-width: 360px;
        line-height: 1.5;
        color: #475569;
    }

    .rch-empty {
        text-align: center;
        padding: 55px 20px;
        color: #64748b;
    }

    .rch-empty i {
        font-size: 42px;
        color: #cbd5e1;
        margin-bottom: 12px;
    }

    .rch-footer {
        padding: 16px 18px;
        background: white;
    }

    .rch-menu {
        position: relative;
        display: inline-flex;
        justify-content: flex-end;
    }

    .rch-menu-btn {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #475569;
        cursor: pointer;
    }

    .rch-menu-btn:hover {
        background: #eff6ff;
        border-color: #0053b3;
        color: #0053b3;
    }

    .rch-menu-dropdown {
        position: absolute;
        top: 45px;
        right: 0;
        width: 200px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .16);
        padding: 8px;
        display: none;
        z-index: 300;
    }

    .rch-menu.active .rch-menu-dropdown {
        display: block;
    }

    .rch-menu-item {
        width: 100%;
        border: 0;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 12px;
        text-decoration: none;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
    }

    .rch-menu-item:hover {
        background: #f1f5f9;
        color: #0053b3;
    }

    .rch-menu-item.danger {
        color: #dc2626;
    }

    .rch-menu-item.danger:hover {
        background: #fef2f2;
    }

    .rch-separator {
        height: 1px;
        background: #e2e8f0;
        margin: 6px 0;
    }

    .modal-cover {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
    }

    .modal-cover.active {
        display: flex;
    }

    .modal-box {
        background: white;
        border-radius: 24px;
        max-width: 500px;
        width: 100%;
        padding: 24px;
        box-shadow: 0 35px 80px rgba(0, 0, 0, .25);
    }

    .modal-box h3 {
        margin: 0 0 10px;
        color: #0f172a;
        font-size: 22px;
        font-weight: 950;
    }

    .modal-box p {
        color: #64748b;
        line-height: 1.6;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    @media(max-width:760px) {
        .rch-header {
            flex-direction: column
        }

        .rch-actions,
        .rch-search {
            width: 100%
        }

        .rch-search {
            flex-direction: column
        }

        .rch-btn {
            justify-content: center
        }
    }
</style>

<div class="rch-page">

    <div class="rch-header">
        <div class="rch-title">
            <h1>Recherches</h1>
            <p>Historique des recherches immobilières et enrichissements Data 360.</p>
        </div>

        <div class="rch-actions">
            <a href="{{ route('back.recherches.create') }}" class="rch-btn rch-btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nouvelle recherche
            </a>

            <button type="button" class="rch-btn rch-btn-danger" onclick="openResetModal()">
                <i class="fa-solid fa-rotate-left"></i>
                Réinitialiser
            </button>
        </div>
    </div>

    <div class="rch-card">

        <div class="rch-toolbar">

            <form method="GET" action="{{ route('back.recherches.index') }}" class="rch-search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Rechercher une adresse, ville, statut...">

                <button class="rch-btn rch-btn-dark" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Rechercher
                </button>

                <a href="{{ route('back.recherches.index') }}" class="rch-btn rch-btn-light">
                    Effacer
                </a>
            </form>

            <div class="rch-stats">
                <div class="rch-pill">
                    Total :
                    {{ method_exists($recherches, 'total') ? $recherches->total() : $recherches->count() }}
                </div>

                <div class="rch-pill">
                    Page :
                    {{ method_exists($recherches, 'currentPage') ? $recherches->currentPage() : 1 }}
                </div>
            </div>

        </div>

        <div class="rch-table-wrap">

            <table class="rch-table">

                <thead>
                    <tr>
                        <th>Recherche</th>
                        <th>Utilisateur</th>
                        <th>Statut</th>
                        <th></th>Date</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($recherches as $recherche)

                    <tr>

                        <td>
                            <div class="rch-main">
                                {{ $recherche->requete }}
                            </div>

                            <div class="rch-muted">
                                Recherche #{{ $recherche->id }}
                            </div>
                        </td>

                        <td>
                            <div class="rch-main" style="font-size:14px;">
                                {{ $recherche->user?->name ?? 'Anonyme' }}
                            </div>

                            <div class="rch-muted">
                                {{ $recherche->user?->email ?? 'Aucun e-mail' }}
                            </div>
                        </td>

                        <td>

                            @php
                            $status = strtolower($recherche->statut ?? '');
                            @endphp

                            <span class="rch-status
                                    {{ $status === 'trouve' ? 'success' : ($status === 'partiel' ? 'warning' : 'danger') }}">
                                <i class="fa-solid
                                        {{ $status === 'trouve'
                                            ? 'fa-circle-check'
                                            : ($status === 'partiel'
                                                ? 'fa-triangle-exclamation'
                                                : 'fa-circle-xmark') }}">
                                </i>

                                {{ ucfirst($recherche->statut ?? '-') }}
                            </span>

                        </td>

                        <td>
                            <div class="rch-main" style="font-size:14px;">
                                {{ $recherche->created_at?->format('d/m/Y') }}
                            </div>

                            <div class="rch-muted">
                                {{ $recherche->created_at?->format('H:i') }}
                            </div>
                        </td>

                        <td>

                            <div class="rch-menu">

                                <button type="button" class="rch-menu-btn" onclick="toggleRechercheMenu(this)">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div class="rch-menu-dropdown">

                                    <a href="{{ route('back.recherches.show', $recherche) }}"
                                        class="rch-menu-item">
                                        <i class="fa-regular fa-eye"></i>
                                        Voir
                                    </a>

                                    <div class="rch-separator"></div>

                                    <form method="POST"
                                        action="{{ route('back.recherches.destroy', $recherche) }}"
                                        onsubmit="return confirm('Supprimer cette recherche ?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="rch-menu-item danger">
                                            <i class="fa-regular fa-trash-can"></i>
                                            Supprimer
                                        </button>
                                    </form>

                                </div>

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="5">

                            <div class="rch-empty">
                                <i class="fa-regular fa-folder-open"></i>
                                <div>Aucune recherche trouvée.</div>
                            </div>

                        </td>
                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="rch-footer">
            {{ $recherches->withQueryString()->links() }}
        </div>

    </div>

</div>

<div class="modal-cover" id="resetModal">

    <div class="modal-box">

        <h3>Réinitialiser les recherches ?</h3>

        <p>
            Cette action supprimera définitivement toutes les recherches enregistrées.
            Les rapports et historiques liés seront perdus.
        </p>

        <form method="POST" action="{{ route('back.recherches.reset') }}">
            @csrf
            @method('DELETE')

            <div class="modal-actions">

                <button type="button"
                    class="rch-btn rch-btn-light"
                    onclick="closeResetModal()">
                    Annuler
                </button>

                <button type="submit"
                    class="rch-btn rch-btn-danger">
                    Oui, réinitialiser
                </button>

            </div>

        </form>

    </div>

</div>

<script>
    function toggleRechercheMenu(button) {
        const current = button.closest('.rch-menu');

        document.querySelectorAll('.rch-menu').forEach(menu => {
            if (menu !== current) {
                menu.classList.remove('active');
            }
        });

        current.classList.toggle('active');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.rch-menu')) {
            document.querySelectorAll('.rch-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    function openResetModal() {
        document.getElementById('resetModal').classList.add('active');
    }

    function closeResetModal() {
        document.getElementById('resetModal').classList.remove('active');
    }

    document.addEventListener('keydown', function(e) {

        if (e.key === 'Escape') {
            closeResetModal();

            document.querySelectorAll('.rch-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
</script>
@endsection