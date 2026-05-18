@extends('back.layouts.app')

@section('title', 'Bâtiments | Data 360')

@section('content')
<style>
    .bld-page{padding:24px}
    .bld-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:22px}
    .bld-title h1{font-size:30px;font-weight:950;color:#0f172a;margin:0}
    .bld-title p{color:#64748b;margin:6px 0 0}
    .bld-actions{display:flex;gap:10px;flex-wrap:wrap}
    .bld-btn{border:0;border-radius:14px;padding:11px 16px;font-weight:900;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
    .bld-btn-primary{background:#0053b3;color:white}
    .bld-btn-danger{background:#dc2626;color:white}
    .bld-btn-light{background:#f1f5f9;color:#334155}
    .bld-btn-dark{background:#0f172a;color:white}
    .bld-card{background:white;border:1px solid #e2e8f0;border-radius:24px;box-shadow:0 14px 35px rgba(15,23,42,.06);overflow:hidden}
    .bld-toolbar{padding:18px;display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;border-bottom:1px solid #e2e8f0;background:#f8fafc}
    .bld-search{display:flex;gap:10px;flex:1;min-width:280px}
    .bld-search input{width:100%;border:1.5px solid #dbe3ef;border-radius:14px;padding:12px 14px;outline:none}
    .bld-search input:focus{border-color:#0053b3;box-shadow:0 0 0 4px rgba(0,83,179,.10)}
    .bld-stats{display:flex;gap:10px;flex-wrap:wrap}
    .bld-pill{background:white;border:1px solid #e2e8f0;border-radius:999px;padding:9px 13px;font-size:13px;font-weight:800;color:#334155}
    .bld-table-wrap{overflow-x:auto}
    .bld-table{width:100%;border-collapse:collapse}
    .bld-table th{background:white;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.06em;text-align:left;padding:15px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
    .bld-table td{padding:16px 15px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
    .bld-table tr:hover td{background:#f8fafc}
    .bld-main{font-weight:950;color:#0f172a;max-width:420px}
    .bld-muted{font-size:12px;color:#64748b;margin-top:4px}
    .bld-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;background:#e6f0ff;color:#0053b3}
    .bld-badge.dark{background:#f1f5f9;color:#334155}
    .bld-badge.green{background:#dcfce7;color:#166534}
    .bld-badge.orange{background:#ffedd5;color:#9a3412}
    .bld-score{width:54px;height:54px;border-radius:18px;background:linear-gradient(135deg,#0053b3,#003d85);color:white;display:flex;align-items:center;justify-content:center;font-weight:950}
    .bld-empty{text-align:center;padding:55px 20px;color:#64748b}
    .bld-empty i{font-size:42px;color:#cbd5e1;margin-bottom:12px}
    .bld-footer{padding:16px 18px;background:white}
    .bld-menu{position:relative;display:inline-flex;justify-content:flex-end}
    .bld-menu-btn{width:38px;height:38px;border-radius:14px;border:1px solid #e2e8f0;background:white;color:#475569;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
    .bld-menu-btn:hover{background:#eff6ff;color:#0053b3;border-color:#0053b3}
    .bld-menu-dropdown{position:absolute;top:45px;right:0;width:190px;background:white;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 18px 45px rgba(15,23,42,.16);padding:8px;display:none;z-index:200}
    .bld-menu.active .bld-menu-dropdown{display:block}
    .bld-menu-item{width:100%;border:0;background:transparent;display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;color:#334155;text-decoration:none;font-size:13px;font-weight:800;cursor:pointer}
    .bld-menu-item:hover{background:#f1f5f9;color:#0053b3}
    .bld-menu-item.danger{color:#dc2626}
    .bld-menu-item.danger:hover{background:#fef2f2}
    .bld-menu-separator{height:1px;background:#e2e8f0;margin:6px 0}
    .modal-cover{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}
    .modal-cover.active{display:flex}
    .modal-box{background:white;border-radius:24px;max-width:480px;width:100%;padding:24px;box-shadow:0 35px 80px rgba(0,0,0,.25)}
    .modal-box h3{margin:0 0 8px;color:#0f172a;font-size:22px;font-weight:950}
    .modal-box p{color:#64748b;line-height:1.6}
    .modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
    @media(max-width:760px){.bld-header{flex-direction:column}.bld-actions,.bld-search{width:100%}.bld-btn{justify-content:center}.bld-search{flex-direction:column}}
</style>

<div class="bld-page">
    <div class="bld-header">
        <div class="bld-title">
            <h1>Bâtiments</h1>
            <p>Suivi des bâtiments enrichis : adresse, type, logements, niveaux, DPE, chauffage et score d’opportunité.</p>
        </div>

        <div class="bld-actions">
            <a href="{{ route('back.batiments.create') }}" class="bld-btn bld-btn-primary">
                <i class="fa-solid fa-plus"></i> Ajouter
            </a>

            <button type="button" class="bld-btn bld-btn-danger" onclick="openResetModal()">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </button>
        </div>
    </div>

    <div class="bld-card">
        <div class="bld-toolbar">
            <form method="GET" action="{{ route('back.batiments.index') }}" class="bld-search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par adresse, identifiant BDNB, type...">
                <button class="bld-btn bld-btn-dark" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Rechercher
                </button>
                <a href="{{ route('back.batiments.index') }}" class="bld-btn bld-btn-light">Effacer</a>
            </form>

            <div class="bld-stats">
                <div class="bld-pill">Total : {{ method_exists($batiments, 'total') ? $batiments->total() : $batiments->count() }}</div>
                <div class="bld-pill">Page : {{ method_exists($batiments, 'currentPage') ? $batiments->currentPage() : 1 }}</div>
            </div>
        </div>

        <div class="bld-table-wrap">
            <table class="bld-table">
                <thead>
                    <tr>
                        <th>Adresse</th>
                        <th>Type</th>
                        <th>Année</th>
                        <th>Logements</th>
                        <th>Niveaux</th>
                        <th>DPE</th>
                        <th>Score</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($batiments as $batiment)
                        <tr>
                            <td>
                                <div class="bld-main">{{ $batiment->adresse->adresse_complete ?? '-' }}</div>
                                <div class="bld-muted">
                                    ID #{{ $batiment->id }}
                                    @if(!empty($batiment->identifiant_bdnb))
                                        · BDNB {{ $batiment->identifiant_bdnb }}
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="bld-badge">
                                    <i class="fa-solid fa-building"></i>
                                    {{ ucfirst($batiment->type_batiment ?? 'inconnu') }}
                                </span>
                            </td>

                            <td>{{ $batiment->annee_construction ?? '-' }}</td>

                            <td>
                                <span class="bld-badge green">
                                    <i class="fa-solid fa-door-open"></i>
                                    {{ $batiment->nombre_logements ?? 0 }}
                                </span>
                            </td>

                            <td>
                                <span class="bld-badge dark">
                                    <i class="fa-solid fa-layer-group"></i>
                                    {{ $batiment->nombre_niveaux ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <span class="bld-badge {{ in_array($batiment->classe_dpe ?? null, ['E','F','G']) ? 'orange' : 'green' }}">
                                    {{ $batiment->classe_dpe ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <div class="bld-score">
                                    {{ (int) ($batiment->score_opportunite ?? 0) }}
                                </div>
                            </td>

                            <td>
                                <div class="bld-menu">
                                    <button type="button" class="bld-menu-btn" onclick="toggleBldMenu(this)">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                    <div class="bld-menu-dropdown">
                                        <a href="{{ route('back.batiments.show', $batiment) }}" class="bld-menu-item">
                                            <i class="fa-regular fa-eye"></i> Voir
                                        </a>

                                        <a href="{{ route('back.batiments.edit', $batiment) }}" class="bld-menu-item">
                                            <i class="fa-regular fa-pen-to-square"></i> Modifier
                                        </a>

                                        <div class="bld-menu-separator"></div>

                                        <form method="POST" action="{{ route('back.batiments.destroy', $batiment) }}"
                                              onsubmit="return confirm('Supprimer ce bâtiment ? Cette action est irréversible.');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="bld-menu-item danger">
                                                <i class="fa-regular fa-trash-can"></i> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="bld-empty">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <div>Aucun bâtiment trouvé.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bld-footer">
            {{ $batiments->withQueryString()->links() }}
        </div>
    </div>
</div>

<div class="modal-cover" id="resetModal">
    <div class="modal-box">
        <h3>Réinitialiser les bâtiments ?</h3>
        <p>
            Cette action supprimera tous les bâtiments enregistrés. Elle est irréversible.
            Utilise-la seulement pour remettre les données bâtiments à zéro.
        </p>

        <form method="POST" action="{{ route('back.batiments.reset') }}">
            @csrf
            @method('DELETE')

            <div class="modal-actions">
                <button type="button" class="bld-btn bld-btn-light" onclick="closeResetModal()">Annuler</button>
                <button type="submit" class="bld-btn bld-btn-danger">
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

    function toggleBldMenu(button) {
        const current = button.closest('.bld-menu');

        document.querySelectorAll('.bld-menu').forEach(menu => {
            if (menu !== current) {
                menu.classList.remove('active');
            }
        });

        current.classList.toggle('active');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.bld-menu')) {
            document.querySelectorAll('.bld-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeResetModal();

            document.querySelectorAll('.bld-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
</script>
@endsection