{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-imports-hist.blade.php
     Usage: @include('back.security.superadmin.partials.modal-imports-hist')
     Description: Historique des imports CSV avec stats
════════════════════════════════════════════════════ --}}

<div id="modal-imports-hist" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(820px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-file-csv" style="color:#f97316;"></i>
                Historique des imports CSV
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-imports-hist')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Stats rapides --}}
            @php
                try {
                    $importStats = [
                        'total'     => \App\Models\Back\CsvImport::count(),
                        'termine'   => \App\Models\Back\CsvImport::where('statut','termine')->count(),
                        'en_cours'  => \App\Models\Back\CsvImport::whereIn('statut',['en_attente','en_cours'])->count(),
                        'erreur'    => \App\Models\Back\CsvImport::where('statut','erreur')->count(),
                        'lignes'    => \App\Models\Back\CsvImport::sum('total_lignes'),
                    ];
                } catch (\Throwable) {
                    $importStats = ['total'=>0,'termine'=>0,'en_cours'=>0,'erreur'=>0,'lignes'=>0];
                }
            @endphp

            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:16px;">
                @foreach([
                    ['Total',      $importStats['total'],    '#3b82f6', 'fa-file-csv'],
                    ['Terminés',   $importStats['termine'],  '#10b981', 'fa-circle-check'],
                    ['En cours',   $importStats['en_cours'], '#f59e0b', 'fa-clock'],
                    ['Erreurs',    $importStats['erreur'],   '#ef4444', 'fa-circle-xmark'],
                    ['Lignes tot.',number_format($importStats['lignes'],0,',',' '), '#8b5cf6', 'fa-list'],
                ] as $stat)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:10px;text-align:center;">
                    <i class="fa-solid {{ $stat[3] }}" style="color:{{ $stat[2] }};font-size:16px;display:block;margin-bottom:5px;"></i>
                    <div style="font-size:clamp(14px,2vw,18px);font-weight:900;color:#0f172a;">{{ $stat[1] }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">{{ $stat[0] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Filtre statut --}}
            <div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;">
                @foreach([['all','Tous','#64748b'],['termine','Terminés','#10b981'],['en_cours','En cours','#f59e0b'],['erreur','Erreurs','#ef4444']] as $f)
                <button class="sa-btn sa-btn-soft sa-btn-sm import-filter {{ $loop->first ? 'active-filter' : '' }}"
                        onclick="filterImports('{{ $f[0] }}')"
                        id="import-filter-{{ $f[0] }}"
                        style="{{ $loop->first ? 'border:1.5px solid '.$f[2].';color:'.$f[2].';' : '' }}">
                    {{ $f[1] }}
                </button>
                @endforeach
            </div>

            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:12px;min-width:600px;">
                    <thead>
                        <tr>
                            @foreach(['#','Utilisateur','Fichier','Lignes','Statut','XLSX','Date','Actions'] as $h)
                            <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap;">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="imports-table-body">
                        @php
                            try {
                                $imports = \App\Models\Back\CsvImport::with('user')->latest()->take(30)->get();
                            } catch (\Throwable) { $imports = collect(); }
                        @endphp
                        @forelse($imports as $import)
                        <tr class="import-row" data-statut="{{ $import->statut }}">
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;color:#94a3b8;font-size:11px;">#{{ $import->id }}</td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                <div style="font-weight:700;font-size:12px;">{{ optional($import->user)->name ?? '—' }}</div>
                                <div style="font-size:10px;color:#94a3b8;">ID #{{ $import->user_id }}</div>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                <div style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;" title="{{ $import->filename_original }}">
                                    {{ $import->filename_original ?? '—' }}
                                </div>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-weight:800;">
                                {{ $import->lignes_traitees ?? 0 }} / {{ $import->total_lignes ?? 0 }}
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                @php
                                    $sColors = ['termine'=>['sa-badge-green','Terminé'],
                                                'en_cours'=>['sa-badge-blue','En cours'],
                                                'en_attente'=>['sa-badge-gray','En attente'],
                                                'erreur'=>['sa-badge-red','Erreur']];
                                    $sc = $sColors[$import->statut] ?? ['sa-badge-gray', $import->statut];
                                @endphp
                                <span class="sa-badge {{ $sc[0] }}">{{ $sc[1] }}</span>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                @if($import->xlsx_content)
                                    <span class="sa-badge sa-badge-green"><i class="fa-solid fa-check"></i> Dispo</span>
                                @else
                                    <span class="sa-badge sa-badge-gray">—</span>
                                @endif
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;white-space:nowrap;">
                                {{ optional($import->created_at)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                @if($import->statut === 'termine' && $import->xlsx_content)
                                    <a href="{{ route('front.csv.download', $import->id) }}"
                                       class="sa-btn sa-btn-soft sa-btn-sm" target="_blank" title="Télécharger">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                @endif
                                @if($import->erreur_message)
                                    <button class="sa-btn sa-btn-soft sa-btn-sm" title="{{ $import->erreur_message }}"
                                            onclick="alert('{{ addslashes($import->erreur_message) }}')">
                                        <i class="fa-solid fa-circle-info" style="color:#ef4444;"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">Aucun import trouvé</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="openModal('modal-purge-bdd');closeModal('modal-imports-hist');">
                <i class="fa-solid fa-broom"></i> Purger les imports
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-imports-hist')">Fermer</button>
        </div>
    </div>
</div>

<script>
function filterImports(statut) {
    document.querySelectorAll('.import-filter').forEach(btn => {
        btn.style.border = '';
        btn.style.color  = '';
    });

    const colors = { all:'#64748b', termine:'#10b981', en_cours:'#f59e0b', erreur:'#ef4444' };
    const btn = document.getElementById('import-filter-' + statut);
    if (btn) { btn.style.border = `1.5px solid ${colors[statut]}`; btn.style.color = colors[statut]; }

    document.querySelectorAll('.import-row').forEach(row => {
        if (statut === 'all') {
            row.style.display = '';
        } else {
            row.style.display = row.dataset.statut === statut ? '' : 'none';
        }
    });
}
</script>
