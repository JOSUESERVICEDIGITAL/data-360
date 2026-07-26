@extends('back.layouts.app')
@section('title', 'Imports CSV | Data 360')
@section('content')

    <style>
        .ci-page {
            padding: 24px
        }

        .ci-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 22px
        }

        .ci-title h1 {
            font-size: 28px;
            font-weight: 950;
            color: #0f172a;
            margin: 0
        }

        .ci-title p {
            color: #64748b;
            margin-top: 6px;
            font-size: .9rem
        }

        .ci-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .06)
        }

        .ci-toolbar {
            padding: 16px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap
        }

        .ci-pill {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 800;
            color: #334155
        }

        .ci-table {
            width: 100%;
            border-collapse: collapse
        }

        .ci-table th {
            background: white;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap
        }

        .ci-table td {
            padding: 15px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle
        }

        .ci-table tr:hover td {
            background: #f8fafc
        }

        .ci-table tr.selected td {
            background: #eff6ff
        }

        .ci-name {
            font-weight: 800;
            color: #0f172a;
            font-size: .9rem
        }

        .ci-sub {
            font-size: .75rem;
            color: #64748b;
            margin-top: 3px
        }

        .ci-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: .72rem;
            font-weight: 800
        }

        .ci-badge.termine {
            background: #dcfce7;
            color: #15803d
        }

        .ci-badge.en_cours {
            background: #fff7ed;
            color: #92400e
        }

        .ci-badge.erreur {
            background: #fee2e2;
            color: #991b1b
        }

        .ci-badge.en_attente {
            background: #f1f5f9;
            color: #475569
        }

        .ci-progress {
            width: 100%;
            background: #e2e8f0;
            border-radius: 999px;
            height: 6px;
            margin-top: 6px
        }

        .ci-progress-bar {
            height: 6px;
            border-radius: 999px;
            transition: width .3s
        }

        .ci-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 12px;
            font-size: .78rem;
            font-weight: 800;
            text-decoration: none;
            transition: all .2s;
            border: none;
            cursor: pointer;
            white-space: nowrap
        }

        .ci-btn-primary {
            background: #0053b3;
            color: white
        }

        .ci-btn-primary:hover {
            background: #003d85;
            transform: translateY(-1px)
        }

        .ci-btn-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca
        }

        .ci-btn-danger:hover {
            background: #fecaca
        }

        .ci-btn-disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: .6
        }

        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 4px 10px 4px 5px;
            max-width: 200px
        }

        .user-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0053b3, #0ea5e9);
            color: white;
            font-size: 10px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-transform: uppercase
        }

        .user-email {
            font-size: .72rem;
            font-weight: 700;
            color: #334155;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .ci-empty {
            text-align: center;
            padding: 55px 20px;
            color: #64748b
        }

        .ci-empty i {
            font-size: 40px;
            color: #cbd5e1;
            display: block;
            margin-bottom: 12px
        }

        /* Bulk bar */
        .ci-bulk-bar {
            display: none;
            background: #eff6ff;
            border: 1.5px solid #bfdbfe;
            border-radius: 14px;
            padding: 10px 16px;
            margin-bottom: 14px;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap
        }

        .ci-bulk-bar.visible {
            display: flex
        }

        .ci-bulk-count {
            font-size: .85rem;
            font-weight: 800;
            color: #1e40af
        }

        .ci-per-page select {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
            background: white;
            color: #334155
        }

        input[type=checkbox] {
            width: 16px;
            height: 16px;
            accent-color: #0053b3;
            cursor: pointer
        }

        /* ── Pagination custom ── */
        .ci-footer {
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border-top: 1px solid #f1f5f9
        }

        .ci-footer-info {
            font-size: .82rem;
            color: #64748b
        }

        .ci-pagination {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap
        }

        .ci-page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            border-radius: 10px;
            font-size: .82rem;
            font-weight: 800;
            text-decoration: none;
            transition: all .2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #334155;
            cursor: pointer;
        }

        .ci-page-btn:hover {
            background: #e6f0ff;
            border-color: #0053b3;
            color: #0053b3;
            transform: translateY(-1px)
        }

        .ci-page-btn.active {
            background: #0053b3;
            border-color: #0053b3;
            color: white;
            box-shadow: 0 3px 8px rgba(0, 83, 179, .25)
        }

        .ci-page-btn.disabled {
            background: #f8fafc;
            color: #cbd5e1;
            cursor: not-allowed;
            pointer-events: none
        }

        .ci-page-btn.dots {
            border: none;
            background: none;
            cursor: default;
            pointer-events: none;
            color: #94a3b8
        }

        @media(max-width:760px) {
            .ci-header {
                flex-direction: column
            }

            th.col-user,
            td.col-user {
                display: none
            }

            .ci-footer {
                flex-direction: column;
                align-items: flex-start
            }
        }
    </style>

    <div class="ci-page">

        {{-- En-tête --}}
        <div class="ci-header">
            <div class="ci-title">
                <h1><i class="fa-solid fa-file-csv" style="color:#0053b3;"></i> Imports CSV</h1>
                <p>Tous les fichiers CSV traités par la recherche avancée — tous utilisateurs confondus.</p>
            </div>
            <form method="POST" action="{{ route('back.csv-imports.reset') }}"
                onsubmit="return confirm('Supprimer TOUS les imports CSV ? Cette action est irréversible.')">
                @csrf @method('DELETE')
                <button type="submit" class="ci-btn ci-btn-danger">
                    <i class="fa-solid fa-rotate-left"></i> Tout réinitialiser
                </button>
            </form>
        </div>

        {{-- Barre actions groupées --}}
        <div class="ci-bulk-bar" id="bulkBar">
            <span class="ci-bulk-count" id="bulkCount">0 sélectionné(s)</span>
            <form method="POST" action="{{ route('back.csv-imports.bulk-delete') }}" id="bulkDeleteForm"
                onsubmit="return confirmBulkDelete()">
                @csrf @method('DELETE')
                <div id="bulkHiddenInputs"></div>
                <button type="submit" class="ci-btn ci-btn-danger">
                    <i class="fa-solid fa-trash"></i> Supprimer la sélection
                </button>
            </form>
        </div>

        <div class="ci-card">

            {{-- Toolbar --}}
            <div class="ci-toolbar">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <div class="ci-pill">Total : {{ $imports->total() }}</div>
                    <div class="ci-pill" style="color:#15803d;">✓ Terminés : {{ $importStats['termine'] }}</div>
                    <div class="ci-pill" style="color:#92400e;">⏳ En cours : {{ $importStats['en_cours'] }}</div>
                    <div class="ci-pill" style="color:#991b1b;">✗ Erreurs : {{ $importStats['erreur'] }}</div>
                </div>
                <form method="GET" action="{{ route('back.csv-imports.index') }}" class="ci-per-page"
                    style="display:flex;align-items:center;gap:10px;">
                    <label style="font-size:.8rem;font-weight:700;color:#64748b;">Afficher :</label>
                    <select name="per_page" onchange="this.form.submit()">
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200 / page</option>
                    </select>
                </form>
            </div>

            {{-- Tableau --}}
            <div style="overflow-x:auto;">
                <table class="ci-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" id="selectAll" title="Tout sélectionner">
                            </th>
                            <th>#</th>
                            <th>Fichier</th>
                            <th class="col-user">Utilisateur</th>
                            <th>Progression</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th style="width:200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($imports as $import)
                            <tr id="row-{{ $import->id }}">
                                <td>
                                    <input type="checkbox" class="row-check" value="{{ $import->id }}"
                                        onchange="updateBulkBar()">
                                </td>
                                <td>
                                    <span
                                        style="font-size:.8rem;color:#94a3b8;font-weight:700;">#{{ $import->id }}</span>
                                </td>
                                <td>
                                    <div class="ci-name">
                                        {{ $import->filename_original ?? ($import->nom_fichier ?? 'Import #' . $import->id) }}
                                    </div>
                                    <div class="ci-sub">
                                        {{ $import->total_lignes ?? 0 }} adresses
                                        @if ($import->lignes_traitees)
                                            · {{ $import->lignes_traitees }} traitées
                                        @endif
                                    </div>
                                </td>
                                <td class="col-user">
                                    @if ($import->user)
                                        <div class="user-chip" title="{{ $import->user->email }}">
                                            <div class="user-avatar">{{ strtoupper(substr($import->user->email, 0, 1)) }}
                                            </div>
                                            <span class="user-email">{{ $import->user->email }}</span>
                                        </div>
                                        @if ($import->user->name)
                                            <div class="ci-sub" style="padding-left:4px;margin-top:4px;">
                                                {{ $import->user->name }}</div>
                                        @endif
                                    @else
                                        <span style="font-size:.75rem;color:#94a3b8;font-style:italic;">Anonyme</span>
                                    @endif
                                </td>
                                <td style="min-width:140px;">
                                    @php $progress = (int)($import->progress ?? 0); @endphp
                                    <div style="font-size:.8rem;font-weight:700;color:#334155;margin-bottom:4px;">
                                        {{ $progress }}%
                                        @if ($import->lignes_traitees && $import->total_lignes)
                                            <span style="color:#94a3b8;font-weight:400;">
                                                ({{ $import->lignes_traitees }}/{{ $import->total_lignes }})
                                            </span>
                                        @endif
                                    </div>
                                    <div class="ci-progress">
                                        <div class="ci-progress-bar"
                                            style="width:{{ $progress }}%;background:{{ $import->statut === 'erreur' ? '#ef4444' : ($import->statut === 'termine' ? '#15803d' : '#0053b3') }};">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php $s = $import->statut ?? 'en_attente'; @endphp
                                    <span class="ci-badge {{ $s }}">
                                        <i
                                            class="fa-solid {{ $s === 'termine'
                                                ? 'fa-circle-check'
                                                : ($s === 'en_cours'
                                                    ? 'fa-rotate fa-spin'
                                                    : ($s === 'erreur'
                                                        ? 'fa-circle-xmark'
                                                        : 'fa-clock')) }}"></i>
                                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                                    </span>
                                    @if ($s === 'erreur' && $import->erreur_message)
                                        <div class="ci-sub" style="color:#b91c1c;margin-top:4px;max-width:200px;">
                                            {{ Str::limit($import->erreur_message, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="ci-name" style="font-size:.85rem;">
                                        {{ $import->created_at?->format('d/m/Y') }}</div>
                                    <div class="ci-sub">{{ $import->created_at?->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        @if ($import->statut === 'termine' && (!empty($import->xlsx_content) || !empty($import->filename_result)))
                                            <a href="{{ route('back.csv-imports.download', [
    'systeme' => $import->systeme,
    'id' => $import->id,
]) }}"
                                                class="ci-btn ci-btn-primary">
                                                <i class="fa-solid fa-download"></i> Télécharger
                                            </a>
                                        @elseif($import->statut === 'termine')
                                            {{-- Terminé mais fichier perdu (ancien import Railway) --}}
                                            <span class="ci-btn ci-btn-disabled" title="Fichier supprimé du serveur">
                                                <i class="fa-solid fa-file-slash"></i> Expiré
                                            </span>
                                        @else
                                            <span class="ci-btn ci-btn-disabled">
                                                <i class="fa-solid fa-clock"></i>
                                                {{ $import->statut === 'erreur' ? 'Erreur' : 'En attente' }}
                                            </span>
                                        @endif
                                        @if($import->systeme === 'nouveau')
<form method="POST" action="{{ route('back.csv-imports.destroy', $import) }}"
      onsubmit="return confirm('Supprimer cet import ?')">
    @csrf
    @method('DELETE')

    <button type="submit" class="ci-btn ci-btn-danger">
        <i class="fa-solid fa-trash"></i>
    </button>
</form>
@endif
                                           
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="ci-empty">
                                        <i class="fa-regular fa-folder-open"></i>
                                        Aucun import CSV trouvé.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ══ FOOTER avec pagination custom ════════════════════ --}}
            <div class="ci-footer">

                {{-- Info --}}
                <div class="ci-footer-info">
                    @if ($imports->total() > 0)
                        Affichage de <strong>{{ $imports->firstItem() }}</strong>
                        à <strong>{{ $imports->lastItem() }}</strong>
                        sur <strong>{{ $imports->total() }}</strong> imports
                    @endif
                </div>

                {{-- Pagination numérotée custom --}}
                @if ($imports->hasPages())
                    @php
                        $current = $imports->currentPage();
                        $last = $imports->lastPage();
                        $baseUrl = $imports->withQueryString()->url(1);
                        // Fenêtre de pages à afficher (2 de chaque côté de la page courante)
                        $window = 2;
                        $start = max(1, $current - $window);
                        $end = min($last, $current + $window);
                    @endphp
                    <nav class="ci-pagination">

                        {{-- ← Première --}}
                        @if ($current > 1)
                            <a href="{{ $imports->withQueryString()->url(1) }}" class="ci-page-btn"
                                title="Première page">
                                «
                            </a>
                            <a href="{{ $imports->withQueryString()->previousPageUrl() }}" class="ci-page-btn">
                                ‹ Précédent
                            </a>
                        @else
                            <span class="ci-page-btn disabled">«</span>
                            <span class="ci-page-btn disabled">‹ Précédent</span>
                        @endif

                        {{-- Ellipsis début --}}
                        @if ($start > 1)
                            <a href="{{ $imports->withQueryString()->url(1) }}" class="ci-page-btn">1</a>
                            @if ($start > 2)
                                <span class="ci-page-btn dots">…</span>
                            @endif
                        @endif

                        {{-- Pages numérotées --}}
                        @for ($page = $start; $page <= $end; $page++)
                            @if ($page === $current)
                                <span class="ci-page-btn active">{{ $page }}</span>
                            @else
                                <a href="{{ $imports->withQueryString()->url($page) }}" class="ci-page-btn">
                                    {{ $page }}
                                </a>
                            @endif
                        @endfor

                        {{-- Ellipsis fin --}}
                        @if ($end < $last)
                            @if ($end < $last - 1)
                                <span class="ci-page-btn dots">…</span>
                            @endif
                            <a href="{{ $imports->withQueryString()->url($last) }}"
                                class="ci-page-btn">{{ $last }}</a>
                        @endif

                        {{-- → Suivant / Dernière --}}
                        @if ($imports->hasMorePages())
                            <a href="{{ $imports->withQueryString()->nextPageUrl() }}" class="ci-page-btn">
                                Suivant ›
                            </a>
                            <a href="{{ $imports->withQueryString()->url($last) }}" class="ci-page-btn"
                                title="Dernière page">
                                »
                            </a>
                        @else
                            <span class="ci-page-btn disabled">Suivant ›</span>
                            <span class="ci-page-btn disabled">»</span>
                        @endif

                    </nav>
                @endif

            </div>
        </div>
    </div>

    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });

        function updateBulkBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            const bar = document.getElementById('bulkBar');
            const count = document.getElementById('bulkCount');
            const inputs = document.getElementById('bulkHiddenInputs');

            count.textContent = checked.length + ' sélectionné(s)';
            bar.classList.toggle('visible', checked.length > 0);

            inputs.innerHTML = '';
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                inputs.appendChild(input);
            });

            document.querySelectorAll('.row-check').forEach(cb => {
                document.getElementById('row-' + cb.value)?.classList.toggle('selected', cb.checked);
            });

            const all = document.querySelectorAll('.row-check');
            document.getElementById('selectAll').indeterminate = checked.length > 0 && checked.length < all.length;
            document.getElementById('selectAll').checked = all.length > 0 && checked.length === all.length;
        }

        function confirmBulkDelete() {
            const n = document.querySelectorAll('.row-check:checked').length;
            return confirm('Supprimer ' + n + ' import(s) ? Cette action est irréversible.');
        }
    </script>

@endsection
