{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-purge-bdd.blade.php
     Usage: @include('back.security.superadmin.partials.modal-purge-bdd')
     Description: Purge complète BDD — recherches, imports, sessions, logs
════════════════════════════════════════════════════ --}}

<div id="modal-purge-bdd" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(680px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-database" style="color:#ef4444;"></i>
                Centre de maintenance BDD
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-bdd')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Warning global --}}
            <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:13px 15px;margin-bottom:18px;display:flex;gap:10px;font-size:12px;color:#991b1b;line-height:1.5;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;flex-shrink:0;margin-top:1px;"></i>
                <div><strong>Zone dangereuse.</strong> Toutes ces opérations sont irréversibles. Assurez-vous d'avoir une sauvegarde Railway avant de procéder.</div>
            </div>

            {{-- Tabs --}}
            <div style="display:flex;gap:4px;margin-bottom:16px;background:#f8fafc;border-radius:12px;padding:4px;" id="purgeTabs">
                <button class="purge-tab active" onclick="switchPurgeTab('recherches')" id="tab-recherches">
                    <i class="fa-solid fa-magnifying-glass"></i> Recherches
                </button>
                <button class="purge-tab" onclick="switchPurgeTab('imports')" id="tab-imports">
                    <i class="fa-solid fa-file-csv"></i> Imports CSV
                </button>
                <button class="purge-tab" onclick="switchPurgeTab('sessions')" id="tab-sessions">
                    <i class="fa-solid fa-cookie-bite"></i> Sessions
                </button>
                <button class="purge-tab" onclick="switchPurgeTab('logs')" id="tab-logs">
                    <i class="fa-solid fa-scroll"></i> Logs
                </button>
            </div>

            {{-- Tab: Recherches --}}
            <div id="purge-tab-recherches" class="purge-tab-content">
                <div style="font-size:13px;color:#475569;margin-bottom:14px;">Supprime les anciennes recherches de la table <code>recherches</code> pour libérer l'espace Railway.</div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:14px;">
                    @foreach([['7days','7 jours','#f59e0b'],['30days','30 jours','#f97316'],['90days','90 jours','#ef4444'],['all','Tout supprimer','#dc2626']] as $opt)
                    <div class="purge-option" onclick="selectPurgeOption('recherches','{{ $opt[0] }}',this)"
                         style="border:2px solid #e2e8f0;border-radius:12px;padding:14px;cursor:pointer;transition:all .2s;text-align:center;">
                        <i class="fa-solid fa-calendar" style="font-size:20px;color:{{ $opt[2] }};margin-bottom:6px;display:block;"></i>
                        <div style="font-weight:800;font-size:13px;color:#0f172a;">{{ $opt[1] }}</div>
                        <div style="font-size:10px;color:#64748b;margin-top:3px;">Plus de {{ $opt[1] }}</div>
                    </div>
                    @endforeach
                </div>
                <div id="confirm-recherches" style="display:none;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:5px;">Tapez <strong>CONFIRMER</strong> :</label>
                    <input type="text" id="input-recherches" class="sa-confirm-input" placeholder="CONFIRMER"
                           oninput="checkPurgeConfirm('recherches')">
                </div>
            </div>

            {{-- Tab: Imports --}}
            <div id="purge-tab-imports" class="purge-tab-content" style="display:none;">
                <div style="font-size:13px;color:#475569;margin-bottom:14px;">Vide les colonnes <code>csv_content</code> et <code>xlsx_content</code> pour libérer l'espace de stockage LONGTEXT.</div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:14px;">
                    @foreach([['terminated','Imports terminés','#10b981','Statut terminé uniquement'],['older30','+ 30 jours','#f97316','Imports anciens'],['all','Vider tout','#ef4444','Tous les imports'],['delete_all','Supprimer lignes','#dc2626','Suppression définitive']] as $opt)
                    <div class="purge-option" onclick="selectPurgeOption('imports','{{ $opt[0] }}',this)"
                         style="border:2px solid #e2e8f0;border-radius:12px;padding:14px;cursor:pointer;transition:all .2s;text-align:center;">
                        <i class="fa-solid fa-file-csv" style="font-size:20px;color:{{ $opt[2] }};margin-bottom:6px;display:block;"></i>
                        <div style="font-weight:800;font-size:13px;color:#0f172a;">{{ $opt[1] }}</div>
                        <div style="font-size:10px;color:#64748b;margin-top:3px;">{{ $opt[3] }}</div>
                    </div>
                    @endforeach
                </div>
                <div id="confirm-imports" style="display:none;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:5px;">Tapez <strong>CONFIRMER</strong> :</label>
                    <input type="text" id="input-imports" class="sa-confirm-input" placeholder="CONFIRMER"
                           oninput="checkPurgeConfirm('imports')">
                </div>
            </div>

            {{-- Tab: Sessions --}}
            <div id="purge-tab-sessions" class="purge-tab-content" style="display:none;">
                <div style="font-size:13px;color:#475569;margin-bottom:14px;">Supprime les sessions expirées de la table <code>sessions</code>. Uniquement les sessions de plus de 24h.</div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:13px;margin-bottom:14px;font-size:12px;color:#166534;">
                    <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>
                    Cette opération est sûre — elle ne déconnecte pas les utilisateurs actuellement en ligne.
                </div>
                <div id="confirm-sessions" style="display:block;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:5px;">Tapez <strong>CONFIRMER</strong> :</label>
                    <input type="text" id="input-sessions" class="sa-confirm-input" placeholder="CONFIRMER"
                           oninput="checkPurgeConfirm('sessions')">
                </div>
            </div>

            {{-- Tab: Logs --}}
            <div id="purge-tab-logs" class="purge-tab-content" style="display:none;">
                <div style="font-size:13px;color:#475569;margin-bottom:14px;">Vide le fichier <code>storage/logs/laravel.log</code> et supprime les anciens fichiers de log.</div>
                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:13px;margin-bottom:14px;font-size:12px;color:#92400e;">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>
                    Les logs peuvent être utiles pour le débogage. Exportez-les avant de vider si nécessaire.
                </div>
                <div id="confirm-logs" style="display:block;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:5px;">Tapez <strong>CONFIRMER</strong> :</label>
                    <input type="text" id="input-logs" class="sa-confirm-input" placeholder="CONFIRMER"
                           oninput="checkPurgeConfirm('logs')">
                </div>
            </div>

            {{-- Résultat --}}
            <div id="purge-result" style="display:none;margin-top:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;color:#166534;"></div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-danger" id="purge-execute-btn" disabled onclick="executePurgeBdd()">
                <i class="fa-solid fa-broom"></i> Exécuter la purge
            </button>
            <button class="sa-btn sa-btn-soft" onclick="closeModal('modal-purge-bdd')">Annuler</button>
        </div>
    </div>
</div>

<style>
.purge-tab {
    flex:1; border:none; background:transparent; border-radius:9px;
    padding:8px 6px; font-size:11px; font-weight:700; cursor:pointer;
    color:#64748b; transition:all .2s; display:flex; align-items:center;
    justify-content:center; gap:5px; white-space:nowrap;
}
.purge-tab.active { background:white; color:#0053b3; box-shadow:0 1px 4px rgba(15,23,42,.08); }
.purge-option.selected { border-color:#ef4444 !important; background:#fff5f5; }
.sa-confirm-input { width:100%; border:1.5px solid #e2e8f0; border-radius:10px; padding:9px 12px; font-size:13px; outline:none; box-sizing:border-box; transition:border-color .2s; }
.sa-confirm-input:focus { border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.08); }
</style>

<script>
let currentPurgeTab    = 'recherches';
let currentPurgeOption = {};

function switchPurgeTab(tab) {
    currentPurgeTab = tab;

    document.querySelectorAll('.purge-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab)?.classList.add('active');

    document.querySelectorAll('.purge-tab-content').forEach(c => c.style.display = 'none');
    document.getElementById('purge-tab-' + tab).style.display = 'block';

    document.getElementById('purge-result').style.display = 'none';
    document.getElementById('purge-execute-btn').disabled = true;
}

function selectPurgeOption(type, value, el) {
    currentPurgeOption[type] = value;

    el.closest('.purge-tab-content').querySelectorAll('.purge-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');

    const confirmDiv = document.getElementById('confirm-' + type);
    if (confirmDiv) confirmDiv.style.display = 'block';

    document.getElementById('purge-execute-btn').disabled = true;
}

function checkPurgeConfirm(type) {
    const input = document.getElementById('input-' + type);
    const btn   = document.getElementById('purge-execute-btn');
    if (btn) btn.disabled = (input?.value?.trim() !== 'CONFIRMER');
}

function executePurgeBdd() {
    const tab    = currentPurgeTab;
    const option = currentPurgeOption[tab];
    const input  = document.getElementById('input-' + tab)?.value?.trim();

    if (input !== 'CONFIRMER') return;

    const routes = {
        recherches: "{{ route('admin.superadmin.purge.recherches') }}",
        imports:    "{{ route('admin.superadmin.purge.imports') }}",
        sessions:   "{{ route('admin.superadmin.purge.sessions') }}",
        logs:       "{{ route('admin.superadmin.purge.logs') }}",
    };

    const body = { confirm: 'CONFIRMER' };
    if (tab === 'recherches') body.period = option;
    if (tab === 'imports')    body.mode   = option;

    const btn = document.getElementById('purge-execute-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Exécution…';

    fetch(routes[tab], {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(data => {
        const resultDiv = document.getElementById('purge-result');
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.style.background = '#f0fdf4';
            resultDiv.style.borderColor = '#bbf7d0';
            resultDiv.style.color = '#166534';
            resultDiv.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + (data.message ?? 'Opération réussie.');
        } else {
            resultDiv.style.background = '#fff5f5';
            resultDiv.style.borderColor = '#fecaca';
            resultDiv.style.color = '#991b1b';
            resultDiv.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Erreur : ' + (data.message ?? 'Une erreur est survenue.');
        }
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Exécuter la purge';
    })
    .catch(() => {
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Exécuter la purge';
    });
}
</script>
