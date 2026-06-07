{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-export.blade.php
     Usage: @include('back.security.superadmin.partials.modal-export')
     Description: Export de données — CSV utilisateurs, imports, etc.
════════════════════════════════════════════════════ --}}

<div id="modal-export" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(600px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-download" style="color:#10b981;"></i>
                Exports de données
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-export')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
                Exportez les données de l'application au format CSV pour analyse ou sauvegarde.
            </p>

            {{-- Export cards --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

                {{-- Utilisateurs --}}
                <div style="border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;transition:all .2s;" class="export-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div style="width:38px;height:38px;border-radius:11px;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:13px;color:#0f172a;">Utilisateurs</div>
                            <div style="font-size:10px;color:#94a3b8;">{{ \App\Models\User::count() }} comptes</div>
                        </div>
                    </div>
                    <p style="font-size:11px;color:#64748b;margin:0 0 12px;line-height:1.5;">Tous les comptes — nom, email, plan, crédits, statut, dates de connexion.</p>
                    <a href="{{ route('admin.superadmin.users.export') }}"
                       class="sa-btn sa-btn-primary sa-btn-sm" style="width:100%;justify-content:center;">
                        <i class="fa-solid fa-download"></i> Exporter CSV
                    </a>
                </div>

                {{-- Imports CSV --}}
                <div style="border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;transition:all .2s;" class="export-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div style="width:38px;height:38px;border-radius:11px;background:#ffedd5;color:#ea580c;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
                            <i class="fa-solid fa-file-csv"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:13px;color:#0f172a;">Imports CSV</div>
                            <div style="font-size:10px;color:#94a3b8;">
                                @php try { echo \App\Models\Back\CsvImport::count() . ' imports'; } catch(\Throwable) { echo '—'; } @endphp
                            </div>
                        </div>
                    </div>
                    <p style="font-size:11px;color:#64748b;margin:0 0 12px;line-height:1.5;">Historique de tous les traitements CSV — statuts, lignes traitées, erreurs.</p>
                    <button class="sa-btn sa-btn-soft sa-btn-sm" style="width:100%;justify-content:center;"
                            onclick="exportImports()">
                        <i class="fa-solid fa-download"></i> Exporter CSV
                    </button>
                </div>

                {{-- Stats DB --}}
                <div style="border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;transition:all .2s;" class="export-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div style="width:38px;height:38px;border-radius:11px;background:#d1fae5;color:#059669;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:13px;color:#0f172a;">Stats base de données</div>
                            <div style="font-size:10px;color:#94a3b8;">Taille + tables</div>
                        </div>
                    </div>
                    <p style="font-size:11px;color:#64748b;margin:0 0 12px;line-height:1.5;">Rapport sur les tables, tailles et volumes de la base Railway.</p>
                    <button class="sa-btn sa-btn-soft sa-btn-sm" style="width:100%;justify-content:center;"
                            onclick="exportDbStats()">
                        <i class="fa-solid fa-download"></i> Exporter JSON
                    </button>
                </div>

                {{-- Rapport complet --}}
                <div style="border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;transition:all .2s;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:rgba(245,158,11,.3);" class="export-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div style="width:38px;height:38px;border-radius:11px;background:rgba(245,158,11,.15);color:#d97706;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:13px;color:#0f172a;">Rapport complet</div>
                            <div style="font-size:10px;color:#92400e;">Superadmin only</div>
                        </div>
                    </div>
                    <p style="font-size:11px;color:#78350f;margin:0 0 12px;line-height:1.5;">Rapport consolidé — utilisateurs + stats + imports + performances.</p>
                    <button class="sa-btn sa-btn-sm" style="width:100%;justify-content:center;background:#f59e0b;color:#78350f;"
                            onclick="exportFullReport()">
                        <i class="fa-solid fa-crown"></i> Rapport complet
                    </button>
                </div>

            </div>

            {{-- Résultat --}}
            <div id="export-result" style="display:none;margin-top:14px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-export')">Fermer</button>
        </div>
    </div>
</div>

<style>
.export-card:hover { border-color:#3b82f6; transform:translateY(-2px); box-shadow:0 6px 20px rgba(59,130,246,.1); }
</style>

<script>
function exportImports() {
    showExportResult('Export imports — fonctionnalité à connecter à une route dédiée.', false);
}

function exportDbStats() {
    fetch("{{ route('admin.superadmin.metrics.db-stats') }}")
        .then(r => r.json())
        .then(data => {
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = `db-stats-${new Date().toISOString().slice(0,10)}.json`;
            a.click();
            URL.revokeObjectURL(url);
            showExportResult('Export DB stats téléchargé avec succès.', true);
        })
        .catch(() => showExportResult('Erreur lors de l\'export.', false));
}

function exportFullReport() {
    Promise.all([
        fetch("{{ route('admin.superadmin.metrics.performance') }}").then(r => r.json()),
        fetch("{{ route('admin.superadmin.metrics.db-stats') }}").then(r => r.json()),
    ])
    .then(([perf, db]) => {
        const report = {
            generated_at: new Date().toISOString(),
            performance: perf,
            database: db,
        };
        const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `data360-report-${new Date().toISOString().slice(0,10)}.json`;
        a.click();
        URL.revokeObjectURL(url);
        showExportResult('Rapport complet téléchargé avec succès.', true);
    })
    .catch(() => showExportResult('Erreur lors de la génération du rapport.', false));
}

function showExportResult(message, success) {
    const div = document.getElementById('export-result');
    div.style.display = 'block';
    div.style.background = success ? '#f0fdf4' : '#fff5f5';
    div.style.border     = `1px solid ${success ? '#bbf7d0' : '#fecaca'}`;
    div.style.color      = success ? '#166534' : '#991b1b';
    div.innerHTML = `<i class="fa-solid ${success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${message}`;
    setTimeout(() => { div.style.display = 'none'; }, 4000);
}
</script>
