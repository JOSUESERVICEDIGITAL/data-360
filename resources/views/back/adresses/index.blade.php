@extends('back.layouts.app')

@section('title', 'Adresses | Data 360')

@section('content')
<style>
    .addr-page {
        padding: 24px
    }

    .addr-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 22px
    }

    .addr-title h1 {
        font-size: 30px;
        font-weight: 950;
        color: #0f172a;
        margin: 0
    }

    .addr-title p {
        color: #64748b;
        margin: 6px 0 0
    }

    .addr-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap
    }

    .addr-btn {
        border: 0;
        border-radius: 14px;
        padding: 11px 16px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px
    }

    .addr-btn-primary {
        background: #0053b3;
        color: white
    }

    .addr-btn-danger {
        background: #dc2626;
        color: white
    }

    .addr-btn-light {
        background: #f1f5f9;
        color: #334155
    }

    .addr-btn-dark {
        background: #0f172a;
        color: white
    }

    .addr-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, .06);
        overflow: hidden
    }

    .addr-toolbar {
        padding: 18px;
        display: flex;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc
    }

    .addr-search {
        display: flex;
        gap: 10px;
        flex: 1;
        min-width: 280px
    }

    .addr-search input {
        width: 100%;
        border: 1.5px solid #dbe3ef;
        border-radius: 14px;
        padding: 12px 14px;
        outline: none
    }

    .addr-search input:focus {
        border-color: #0053b3;
        box-shadow: 0 0 0 4px rgba(0, 83, 179, .10)
    }

    .addr-stats {
        display: flex;
        gap: 10px;
        flex-wrap: wrap
    }

    .addr-pill {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 9px 13px;
        font-size: 13px;
        font-weight: 800;
        color: #334155
    }

    .addr-table-wrap {
        overflow-x: auto
    }

    .addr-table {
        width: 100%;
        border-collapse: collapse
    }

    .addr-table th {
        background: white;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .06em;
        text-align: left;
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap
    }

    .addr-table td {
        padding: 16px 15px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle
    }

    .addr-table tr:hover td {
        background: #f8fafc
    }

    .addr-main {
        font-weight: 950;
        color: #0f172a
    }

    .addr-muted {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px
    }

    .addr-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 900;
        background: #e6f0ff;
        color: #0053b3
    }

    .addr-actions-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap
    }

    .addr-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        cursor: pointer
    }

    .addr-icon-btn:hover {
        border-color: #0053b3;
        color: #0053b3;
        background: #eff6ff
    }

    .addr-icon-btn.danger:hover {
        border-color: #dc2626;
        color: #dc2626;
        background: #fef2f2
    }

    .addr-empty {
        text-align: center;
        padding: 55px 20px;
        color: #64748b
    }

    .addr-empty i {
        font-size: 42px;
        color: #cbd5e1;
        margin-bottom: 12px
    }

    .addr-footer {
        padding: 16px 18px;
        background: white
    }

    .modal-cover {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px
    }

    .modal-cover.active {
        display: flex
    }

    .modal-box {
        background: white;
        border-radius: 24px;
        max-width: 480px;
        width: 100%;
        padding: 24px;
        box-shadow: 0 35px 80px rgba(0, 0, 0, .25)
    }

    .modal-box h3 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 22px;
        font-weight: 950
    }

    .modal-box p {
        color: #64748b;
        line-height: 1.6
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px
    }

    @media(max-width:760px) {
        .addr-header {
            flex-direction: column
        }

        .addr-actions,
        .addr-search {
            width: 100%
        }

        .addr-btn {
            justify-content: center
        }

        .addr-search {
            flex-direction: column
        }
    }
</style>

<div class="addr-page">
    <div class="addr-header">
        <div class="addr-title">
            <h1>Adresses</h1>
            <p>Gestion des adresses enrichies, sources BAN, coordonnées, bâtiments et recherches associées.</p>
        </div>

        <div class="addr-actions">
            <a href="{{ route('back.adresses.create') }}" class="addr-btn addr-btn-primary">
                <i class="fa-solid fa-plus"></i> Ajouter
            </a>

            <button type="button" class="addr-btn addr-btn-danger" onclick="openResetModal()">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </button>
        </div>
    </div>

    <div class="addr-card">
        <div class="addr-toolbar">
            <form method="GET" action="{{ route('back.adresses.index') }}" class="addr-search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher une adresse, ville, code postal...">
                <button class="addr-btn addr-btn-dark" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Rechercher
                </button>
                <a href="{{ route('back.adresses.index') }}" class="addr-btn addr-btn-light">
                    Effacer
                </a>
            </form>

            <div class="addr-stats">
                <div class="addr-pill">
                    Total : {{ method_exists($adresses, 'total') ? $adresses->total() : $adresses->count() }}
                </div>
                <div class="addr-pill">
                    Page : {{ method_exists($adresses, 'currentPage') ? $adresses->currentPage() : 1 }}
                </div>
            </div>
        </div>

        <div class="addr-table-wrap">
            <table class="addr-table">
                <thead>
                    <tr>
                        <th>Adresse</th>
                        <th>Ville</th>
                        <th>Code postal</th>
                        <th>Coordonnées</th>
                        <th>Source</th>
                        <th style="width:150px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($adresses as $adresse)
                    <tr>
                        <td>
                            <div class="addr-main">{{ $adresse->adresse_complete ?? '-' }}</div>
                            <div class="addr-muted">ID #{{ $adresse->id }}</div>
                        </td>

                        <td>{{ $adresse->ville ?? '-' }}</td>

                        <td>
                            <span class="addr-badge">
                                <i class="fa-solid fa-location-dot"></i>
                                {{ $adresse->code_postal ?? '-' }}
                            </span>
                        </td>

                        <td>
                            <div>{{ $adresse->latitude ?? '-' }}</div>
                            <div class="addr-muted">{{ $adresse->longitude ?? '-' }}</div>
                        </td>

                        <td>
                            <span class="addr-badge">
                                {{ strtoupper($adresse->source ?? 'N/A') }}
                            </span>
                        </td>

                        <td>
                            <div class="addr-menu">
                                <button type="button" class="addr-menu-btn" onclick="toggleAddrMenu(this)">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div class="addr-menu-dropdown">
                                    <a href="{{ route('back.adresses.show', $adresse) }}" class="addr-menu-item">
                                        <i class="fa-regular fa-eye"></i>
                                        Voir
                                    </a>

                                    <a href="{{ route('back.adresses.edit', $adresse) }}" class="addr-menu-item">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                        Modifier
                                    </a>

                                    <div class="addr-menu-separator"></div>

                                    <form method="POST" action="{{ route('back.adresses.destroy', $adresse) }}"
                                        onsubmit="return confirm('Supprimer cette adresse ? Cette action est irréversible.');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="addr-menu-item danger">
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
                        <td colspan="6">
                            <div class="addr-empty">
                                <i class="fa-regular fa-folder-open"></i>
                                <div>Aucune adresse trouvée.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="addr-footer">
            {{ $adresses->withQueryString()->links() }}
        </div>
    </div>
</div>

<div class="modal-cover" id="resetModal">
    <div class="modal-box">
        <h3>Réinitialiser les adresses ?</h3>
        <p>
            Cette action supprimera toutes les adresses enregistrées. Elle est irréversible.
            Utilise-la seulement pour remettre la base proprement à zéro.
        </p>

        <form method="POST" action="{{ route('back.adresses.reset') }}">
            @csrf
            @method('DELETE')

            <div class="modal-actions">
                <button type="button" class="addr-btn addr-btn-light" onclick="closeResetModal()">Annuler</button>
                <button type="submit" class="addr-btn addr-btn-danger">
                    Oui, réinitialiser
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResetModal() {
        document.getElementById('resetModal')?.classList.add('active');
    }

    function closeResetModal() {
        document.getElementById('resetModal')?.classList.remove('active');
    }

    function toggleAddrMenu(button) {
        const current = button.closest('.addr-menu');

        document.querySelectorAll('.addr-menu').forEach(menu => {
            if (menu !== current) {
                menu.classList.remove('active');
            }
        });

        current.classList.toggle('active');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.addr-menu')) {
            document.querySelectorAll('.addr-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeResetModal();

            document.querySelectorAll('.addr-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
</script>
<style>
    .addr-menu {
        position: relative;
        display: inline-flex;
        justify-content: flex-end;
    }

    .addr-menu-btn {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .addr-menu-btn:hover {
        background: #eff6ff;
        color: #0053b3;
        border-color: #0053b3;
    }

    .addr-menu-dropdown {
        position: absolute;
        top: 45px;
        right: 0;
        width: 190px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .16);
        padding: 8px;
        display: none;
        z-index: 200;
    }

    .addr-menu.active .addr-menu-dropdown {
        display: block;
    }

    .addr-menu-item {
        width: 100%;
        border: 0;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 12px;
        color: #334155;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
    }

    .addr-menu-item:hover {
        background: #f1f5f9;
        color: #0053b3;
    }

    .addr-menu-item.danger {
        color: #dc2626;
    }

    .addr-menu-item.danger:hover {
        background: #fef2f2;
    }

    .addr-menu-separator {
        height: 1px;
        background: #e2e8f0;
        margin: 6px 0;
    }
</style>
@endsection