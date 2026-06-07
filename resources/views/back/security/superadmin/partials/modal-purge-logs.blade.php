{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-purge-logs.blade.php
     Usage: @include('back.security.superadmin.partials.modal-purge-logs')
     Description: Vider les logs Laravel
════════════════════════════════════════════════════ --}}

<div id="modal-purge-logs" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(540px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-scroll" style="color:#ef4444;"></i>
                Vider les logs Laravel
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-logs')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Info logs --}}
            <div id="logs-info-loading" style="text-align:center;padding:16px;color:#94a3b8;">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Chargement des logs…
            </div>

            <div id="logs-info-content" style="display:none;">
                <div id="logs-stats-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;"></div>

                <div style="overflow-x:auto;margin-bottom:16px;">
                    <table id="logs-files-table" style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead><tr>
                            <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Fichier</th>
                            <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Taille</th>
                            <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Modifié</th>
                            <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Niveau</th>
                        </tr></thead>
                        <tbody id="logs-files-body"></tbody>
                    </table>
                </div>
            </div>

            {{-- Warning --}}
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px;margin-bottom:14px;font-size:12px;color:#92400e;display:flex;gap:8px;line-height:1.5;">
                <i class="fa-solid fa-triangle-exclamation" style="flex-shrink:0;margin-top:1px;"></i>
                <div>Les logs sont utiles pour le <strong>débogage en production</strong>. Cette opération vide le fichier principal et supprime les anciens fichiers. Irréversible.</div>
            </div>

            {{-- Confirmation --}}
            <div>
                <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:6px;">
                    Tapez <strong style="color:#ef4444;">CONFIRMER</strong> pour valider :
                </label>
                <input type="text" id="logs-confirm-input" placeholder="CONFIRMER"
                       style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .2s;"
                       oninput="document.getElementById('logs-purge-btn').disabled = this.value.trim() !== 'CONFIRMER'">
            </div>

            <div id="logs-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

        </div>

        <div class="sa-modal-footer">
            <button id="logs-purge-btn" class="sa-btn sa-btn-danger sa-btn-sm" disabled onclick="executePurgeLogs()">
                <i class="fa-solid fa-broom"></i> Vider les logs
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="loadLogsInfo()">
                <i class="fa-solid fa-rotate"></i> Actualiser
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-purge-logs')">Annuler</button>
        </div>
    </div>
</div>

<script>
let logsInfoLoaded = false;

function loadLogsInfo() {
    document.getElementById('logs-info-loading').style.display = 'block';
    document.getElementById('logs-info-content').style.display = 'none';

    fetch("{{ route('admin.superadmin.metrics.logs-info') }}")
        .then(r => r.json())
        .then(data => {
            document.getElementById('logs-info-loading').style.display = 'none';
            document.getElementById('logs-info-content').style.display = 'block';
            renderLogsInfo(data);
            logsInfoLoaded = true;
        })
        .catch(() => {
            document.getElementById('logs-info-loading').innerHTML = '<span style="color:#ef4444;">Erreur de chargement</span>';
        });
}

function renderLogsInfo(data) {
    const files    = data.files ?? [];
    const totalKb  = data.total_kb ?? 0;

    document.getElementById('logs-stats-grid').innerHTML = `
        <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;">
            <i class="fa-solid fa-files" style="color:#f97316;font-size:18px;display:block;margin-bottom:5px;"></i>
            <div style="font-size:20px;font-weight:900;color:#0f172a;">${files.length}</div>
            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Fichiers log</div>
        </div>
        <div style="background:${totalKb > 5000 ? '#fff5f5' : '#f8fafc'};border:1px solid ${totalKb > 5000 ? '#fecaca' : '#e2e8f0'};border-radius:12px;padding:12px;text-align:center;">
            <i class="fa-solid fa-weight-hanging" style="color:${totalKb > 5000 ? '#ef4444' : '#3b82f6'};font-size:18px;display:block;margin-bottom:5px;"></i>
            <div style="font-size:20px;font-weight:900;color:${totalKb > 5000 ? '#ef4444' : '#0f172a'};">${totalKb.toFixed(0)} KB</div>
            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Taille totale</div>
        </div>
    `;

    const sizeColor = (kb) => kb > 5000 ? '#ef4444' : kb > 1000 ? '#f59e0b' : '#10b981';

    document.getElementById('logs-files-body').innerHTML = files.length > 0
        ? files.map(f => `
            <tr>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                    <code style="background:#f1f5f9;padding:2px 7px;border-radius:5px;font-size:10px;">${f.name}</code>
                </td>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-weight:700;color:${sizeColor(f.size_kb)};">
                    ${f.size_kb} KB
                </td>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;">${f.modified}</td>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                    <span style="background:${f.size_kb > 2000 ? '#fee2e2' : '#f1f5f9'};color:${f.size_kb > 2000 ? '#991b1b' : '#475569'};border-radius:999px;padding:2px 7px;font-size:10px;font-weight:700;">
                        ${f.size_kb > 2000 ? 'Volumineux' : 'Normal'}
                    </span>
                </td>
            </tr>
        `).join('')
        : `<tr><td colspan="4" style="text-align:center;padding:20px;color:#94a3b8;">Aucun fichier log</td></tr>`;
}

function executePurgeLogs() {
    const input  = document.getElementById('logs-confirm-input');
    const btn    = document.getElementById('logs-purge-btn');
    const result = document.getElementById('logs-result');

    if (input.value.trim() !== 'CONFIRMER') return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Suppression…';

    fetch("{{ route('admin.superadmin.purge.logs') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ confirm: 'CONFIRMER' }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        result.style.background = data.success ? '#f0fdf4' : '#fff5f5';
        result.style.border     = `1px solid ${data.success ? '#bbf7d0' : '#fecaca'}`;
        result.style.color      = data.success ? '#166534' : '#991b1b';
        result.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${data.message}`;
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Vider les logs';
        input.value  = '';
        btn.disabled = true;
        setTimeout(() => { logsInfoLoaded = false; loadLogsInfo(); }, 1000);
    })
    .catch(() => {
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Vider les logs';
        btn.disabled  = false;
    });
}
</script>
