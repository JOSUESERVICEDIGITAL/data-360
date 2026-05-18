@extends('back.layouts.app')

@section('title', 'Syndics | Data 360')

@section('content')
<style>
    .syn-page{padding:24px}
    .syn-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:22px}
    .syn-title h1{font-size:30px;font-weight:950;color:#0f172a;margin:0}
    .syn-title p{color:#64748b;margin:6px 0 0}
    .syn-actions{display:flex;gap:10px;flex-wrap:wrap}
    .syn-btn{border:0;border-radius:14px;padding:11px 16px;font-weight:900;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
    .syn-btn-primary{background:#0053b3;color:white}
    .syn-btn-danger{background:#dc2626;color:white}
    .syn-btn-light{background:#f1f5f9;color:#334155}
    .syn-btn-dark{background:#0f172a;color:white}
    .syn-card{background:white;border:1px solid #e2e8f0;border-radius:24px;box-shadow:0 14px 35px rgba(15,23,42,.06);overflow:hidden}
    .syn-toolbar{padding:18px;display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;border-bottom:1px solid #e2e8f0;background:#f8fafc}
    .syn-search{display:flex;gap:10px;flex:1;min-width:280px}
    .syn-search input{width:100%;border:1.5px solid #dbe3ef;border-radius:14px;padding:12px 14px;outline:none}
    .syn-search input:focus{border-color:#0053b3;box-shadow:0 0 0 4px rgba(0,83,179,.10)}
    .syn-stats{display:flex;gap:10px;flex-wrap:wrap}
    .syn-pill{background:white;border:1px solid #e2e8f0;border-radius:999px;padding:9px 13px;font-size:13px;font-weight:800;color:#334155}
    .syn-table-wrap{overflow-x:auto}
    .syn-table{width:100%;border-collapse:collapse}
    .syn-table th{background:white;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.06em;text-align:left;padding:15px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
    .syn-table td{padding:16px 15px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
    .syn-table tr:hover td{background:#f8fafc}
    .syn-main{font-weight:950;color:#0f172a;max-width:360px}
    .syn-muted{font-size:12px;color:#64748b;margin-top:4px}
    .syn-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;background:#e6f0ff;color:#0053b3}
    .syn-badge.dark{background:#f1f5f9;color:#334155}
    .syn-badge.green{background:#dcfce7;color:#166534}
    .syn-badge.orange{background:#ffedd5;color:#9a3412}
    .syn-badge.red{background:#fee2e2;color:#991b1b}
    .syn-capital{font-weight:950;color:#0f172a}
    .syn-empty{text-align:center;padding:55px 20px;color:#64748b}
    .syn-empty i{font-size:42px;color:#cbd5e1;margin-bottom:12px}
    .syn-footer{padding:16px 18px;background:white}
    .syn-menu{position:relative;display:inline-flex;justify-content:flex-end}
    .syn-menu-btn{width:38px;height:38px;border-radius:14px;border:1px solid #e2e8f0;background:white;color:#475569;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
    .syn-menu-btn:hover{background:#eff6ff;color:#0053b3;border-color:#0053b3}
    .syn-menu-dropdown{position:absolute;top:45px;right:0;width:195px;background:white;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 18px 45px rgba(15,23,42,.16);padding:8px;display:none;z-index:200}
    .syn-menu.active .syn-menu-dropdown{display:block}
    .syn-menu-item{width:100%;border:0;background:transparent;display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;color:#334155;text-decoration:none;font-size:13px;font-weight:800;cursor:pointer}
    .syn-menu-item:hover{background:#f1f5f9;color:#0053b3}
    .syn-menu-item.danger{color:#dc2626}
    .syn-menu-item.danger:hover{background:#fef2f2}
    .syn-menu-separator{height:1px;background:#e2e8f0;margin:6px 0}
    .modal-cover{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}
    .modal-cover.active{display:flex}
    .modal-box{background:white;border-radius:24px;max-width:480px;width:100%;padding:24px;box-shadow:0 35px 80px rgba(0,0,0,.25)}
    .modal-box h3{margin:0 0 8px;color:#0f172a;font-size:22px;font-weight:950}
    .modal-box p{color:#64748b;line-height:1.6}
    .modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
    @media(max-width:760px){.syn-header{flex-direction:column}.syn-actions,.syn-search{width:100%}.syn-btn{justify-content:center}.syn-search{flex-direction:column}}
</style>

<div class="syn-page">
    <div class="syn-header">
        <div class="syn-title">
            <h1>Syndics</h1>
            <p>Gestion des syndics, SIREN/SIRET, capitaux sociaux, villes et copropriétés liées.</p>
        </div>

        <div class="syn-actions">
            <a href="{{ route('back.syndics.create') }}" class="syn-btn syn-btn-primary">
                <i class="fa-solid fa-plus"></i> Ajouter
            </a>

            <button type="button" class="syn-btn syn-btn-danger" onclick="openResetModal()">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </button>
        </div>
    </div>

    <div class="syn-card">
        <div class="syn-toolbar">
            <form method="GET" action="{{ route('back.syndics.index') }}" class="syn-search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par nom, SIREN, SIRET, ville...">
                <button class="syn-btn syn-btn-dark" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Rechercher
                </button>
                <a href="{{ route('back.syndics.index') }}" class="syn-btn syn-btn-light">Effacer</a>
            </form>

            <div class="syn-stats">
                <div class="syn-pill">Total : {{ method_exists($syndics, 'total') ? $syndics->total() : $syndics->count() }}</div>
                <div class="syn-pill">Page : {{ method_exists($syndics, 'currentPage') ? $syndics->currentPage() : 1 }}</div>
            </div>
        </div>

        <div class="syn-table-wrap">
            <table class="syn-table">
                <thead>
                    <tr>
                        <th>Syndic</th>
                        <th>SIREN</th>
                        <th>SIRET</th>
                        <th>Ville</th>
                        <th>Capital</th>
                        <th>Copropriétés</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($syndics as $syndic)
                        <tr>
                            <td>
                                <div class="syn-main">{{ $syndic->nom ?? '-' }}</div>
                                <div class="syn-muted">
                                    ID #{{ $syndic->id }}
                                    @if(!empty($syndic->forme_juridique))
                                        · {{ $syndic->forme_juridique }}
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="syn-badge">
                                    <i class="fa-solid fa-id-card"></i>
                                    {{ $syndic->siren ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <span class="syn-badge dark">
                                    {{ $syndic->siret ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <span class="syn-badge green">
                                    <i class="fa-solid fa-location-dot"></i>
                                    {{ $syndic->ville ?? '-' }}
                                </span>
                                @if(!empty($syndic->code_postal))
                                    <div class="syn-muted">{{ $syndic->code_postal }}</div>
                                @endif
                            </td>

                            <td>
                                <div class="syn-capital">{{ $syndic->capital_social ?? '-' }}</div>
                                @if(!empty($syndic->activite))
                                    <div class="syn-muted">{{ $syndic->activite }}</div>
                                @endif
                            </td>

                            <td>
                                <span class="syn-badge orange">
                                    <i class="fa-solid fa-city"></i>
                                    {{ $syndic->coproprietes_count ?? 0 }}
                                </span>
                            </td>

                            <td>
                                <div class="syn-menu">
                                    <button type="button" class="syn-menu-btn" onclick="toggleSynMenu(this)">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                    <div class="syn-menu-dropdown">
                                        <a href="{{ route('back.syndics.show', $syndic) }}" class="syn-menu-item">
                                            <i class="fa-regular fa-eye"></i> Voir
                                        </a>

                                        <a href="{{ route('back.syndics.edit', $syndic) }}" class="syn-menu-item">
                                            <i class="fa-regular fa-pen-to-square"></i> Modifier
                                        </a>

                                        <div class="syn-menu-separator"></div>

                                        <form method="POST" action="{{ route('back.syndics.destroy', $syndic) }}"
                                              onsubmit="return confirm('Supprimer ce syndic ? Cette action est irréversible.');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="syn-menu-item danger">
                                                <i class="fa-regular fa-trash-can"></i> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="syn-empty">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <div>Aucun syndic trouvé.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="syn-footer">
            {{ $syndics->withQueryString()->links() }}
        </div>
    </div>
</div>

<div class="modal-cover" id="resetModal">
    <div class="modal-box">
        <h3>Réinitialiser les syndics ?</h3>
        <p>
            Cette action supprimera tous les syndics enregistrés. Elle est irréversible.
            Utilise-la seulement pour remettre les données syndics à zéro.
        </p>

        <form method="POST" action="{{ route('back.syndics.reset') }}">
            @csrf
            @method('DELETE')

            <div class="modal-actions">
                <button type="button" class="syn-btn syn-btn-light" onclick="closeResetModal()">Annuler</button>
                <button type="submit" class="syn-btn syn-btn-danger">
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

    function toggleSynMenu(button) {
        const current = button.closest('.syn-menu');

        document.querySelectorAll('.syn-menu').forEach(menu => {
            if (menu !== current) {
                menu.classList.remove('active');
            }
        });

        current.classList.toggle('active');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.syn-menu')) {
            document.querySelectorAll('.syn-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeResetModal();

            document.querySelectorAll('.syn-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
</script>
@endsection