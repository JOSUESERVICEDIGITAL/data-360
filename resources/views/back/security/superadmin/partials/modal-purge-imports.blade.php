{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-purge-imports.blade.php
     Usage: @include('back.security.superadmin.partials.modal-purge-imports')
     Description: Purger les imports CSV (csv_content / xlsx_content)
════════════════════════════════════════════════════ --}}

<div id="modal-purge-imports" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(560px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-file-circle-xmark" style="color:#ef4444;"></i>
                Purger les imports CSV
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-imports')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                try {
                    $imp_total    = \App\Models\Back\CsvImport::count();
                    $imp_termine  = \App\Models\Back\CsvImport::where('statut','termine')->count();
                    $imp_old30    = \App\Models\Back\CsvImport::where('created_at','<', now()->subDays(30))->count();
                    $imp_with_csv = \App\Models\Back\CsvImport::whereNotNull('csv_content')->count();
                    $imp_with_xlsx= \App\Models\Back\CsvImport::whereNotNull('xlsx_content')->count();
                } catch(\Throwable) {
                    $imp_total = $imp_termine = $imp_old30 = $imp_with_csv = $imp_with_xlsx = 0;
                }
            @endphp

            {{-- Stats --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;">
                @foreach([
                    [$imp_total,    'Total imports', '#3b82f6', 'fa-file-csv'],
                    [$imp_with_csv, 'Avec CSV',      '#f97316', 'fa-file'],
                    [$imp_with_xlsx,'Avec XLSX',     '#10b981', 'fa-file-excel'],
                ] as $s)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:11px;padding:11px;text-align:center;">
                    <i class="fa-solid {{ $s[3] }}" style="color:{{ $s[2] }};font-size:15px;display:block;margin-bottom:4px;"></i>
                    <div style="font-size:18px;font-weight:900;color:#0f172a;">{{ $s[0] }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:1px;">{{ $s[1] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Warning --}}
            <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:10px;padding:12px;margin-bottom:14px;font-size:12px;color:#991b1b;display:flex;gap:8px;line-height:1.5;">
                <i class="fa-solid fa-triangle-exclamation" style="flex-shrink:0;margin-top:1px;"></i>
                <div>Cette action vide <code>csv_content</code> et <code>xlsx_content</code>. Les fichiers XLSX <strong>ne pourront plus être téléchargés</strong> après la purge.</div>
            </div>

            {{-- Options --}}
            <div style="margin-bottom:14px;">
                <label style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:8px;">Choisissez une option</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @foreach([
                        ['terminated', 'Imports terminés',   '#10b981', $imp_termine,  'Vide uniquement les imports avec statut terminé'],
                        ['older30',    '+ 30 jours',         '#f97316', $imp_old30,    'Vide les imports de plus de 30 jours'],
                        ['all',        'Tout vider',         '#ef4444', $imp_total,    'Vide csv_content et xlsx_content de tous les imports'],
                        ['delete_all', 'Supprimer + 30j',    '#dc2626', $imp_old30,    'Supprime définitivement les lignes > 30 jours'],
                    ] as $opt)
                    <div onclick="selectPurgeImportOption('{{ $opt[0] }}', this)"
                         style="border:2px solid #e2e8f0;border-radius:11px;padding:12px;cursor:pointer;transition:all .2s;text-align:center;"
                         class="purge-import-opt">
                        <i class="fa-solid fa-file-circle-xmark" style="color:{{ $opt[2] }};font-size:18px;display:block;margin-bottom:5px;"></i>
                        <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $opt[1] }}</div>
                        <div style="font-size:10px;color:#64748b;margin-top:2px;">{{ $opt[3] }} import(s)</div>
                        <div style="font-size:9px;color:#94a3b8;margin-top:2px;line-height:1.3;">{{ $opt[4] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Confirmation --}}
            <div id="purge-imports-confirm-section" style="display:none;">
                <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:6px;">
                    Tapez <strong style="color:#ef4444;">CONFIRMER</strong> :
                </label>
                <input type="text" id="purge-imports-confirm-input" placeholder="CONFIRMER"
                       style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;outline:none;box-sizing:border-box;"
                       oninput="document.getElementById('purge-imports-exec-btn').disabled = this.value.trim() !== 'CONFIRMER'">
            </div>

            <div id="purge-imports-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

        </div>

        <div class="sa-modal-footer">
            <button id="purge-imports-exec-btn" class="sa-btn sa-btn-danger sa-btn-sm" disabled onclick="executePurgeImports()">
                <i class="fa-solid fa-broom"></i> Purger
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-purge-imports')">Annuler</button>
        </div>
    </div>
</div>

<script>
let selectedPurgeImportMode = null;

function selectPurgeImportOption(mode, el) {
    selectedPurgeImportMode = mode;
    document.querySelectorAll('.purge-import-opt').forEach(o => { o.style.borderColor = ''; o.style.background = ''; });
    el.style.borderColor = '#ef4444';
    el.style.background  = '#fff5f5';
    document.getElementById('purge-imports-confirm-section').style.display = 'block';
    document.getElementById('purge-imports-exec-btn').disabled = true;
}

function executePurgeImports() {
    const input  = document.getElementById('purge-imports-confirm-input');
    const btn    = document.getElementById('purge-imports-exec-btn');
    const result = document.getElementById('purge-imports-result');

    if (input.value.trim() !== 'CONFIRMER' || !selectedPurgeImportMode) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Purge en cours…';

    fetch("{{ route('admin.superadmin.purge.imports') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ confirm: 'CONFIRMER', mode: selectedPurgeImportMode }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display  = 'block';
        result.style.background = data.success ? '#f0fdf4' : '#fff5f5';
        result.style.border     = `1px solid ${data.success ? '#bbf7d0' : '#fecaca'}`;
        result.style.color      = data.success ? '#166534' : '#991b1b';
        result.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${data.message ?? (data.success ? 'Succès' : 'Erreur')}`;
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger';
        input.value  = '';
        btn.disabled = true;
    })
    .catch(() => {
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger';
        btn.disabled  = false;
    });
}
</script>
