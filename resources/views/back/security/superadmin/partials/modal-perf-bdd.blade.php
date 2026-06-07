{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-perf-bdd.blade.php
     Usage: @include('back.security.superadmin.partials.modal-perf-bdd')
════════════════════════════════════════════════════ --}}

<div id="modal-perf-bdd" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(800px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-gauge-high" style="color:#10b981;"></i>
                Performances & Monitoring
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-perf-bdd')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Tabs monitoring --}}
            <div style="display:flex;gap:4px;background:#f8fafc;border-radius:12px;padding:4px;margin-bottom:16px;" id="perfTabs">
                @foreach([['db','Base de données','fa-database'],['queue','Queue Worker','fa-list-check'],['system','Système','fa-server'],['logs','Logs','fa-scroll']] as $tab)
                <button class="perf-tab {{ $loop->first ? 'active' : '' }}" onclick="switchPerfTab('{{ $tab[0] }}')" id="perf-tab-{{ $tab[0] }}">
                    <i class="fa-solid {{ $tab[2] }}"></i> {{ $tab[1] }}
                </button>
                @endforeach
            </div>

            {{-- Tab DB --}}
            <div id="perf-content-db" class="perf-content">
                <div id="db-loading" style="text-align:center;padding:30px;color:#94a3b8;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size:22px;"></i>
                    <div style="margin-top:8px;font-size:13px;">Analyse de la base de données…</div>
                </div>
                <div id="db-content" style="display:none;">
                    <div id="db-summary" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;"></div>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:12px;">
                            <thead>
                                <tr>
                                    <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Table</th>
                                    <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Lignes</th>
                                    <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Taille</th>
                                    <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">Volume</th>
                                </tr>
                            </thead>
                            <tbody id="db-tables-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Tab Queue --}}
            <div id="perf-content-queue" class="perf-content" style="display:none;">
                <div id="queue-loading" style="text-align:center;padding:30px;color:#94a3b8;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size:22px;"></i>
                    <div style="margin-top:8px;font-size:13px;">Chargement de la queue…</div>
                </div>
                <div id="queue-content" style="display:none;">
                    <div id="queue-summary" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;"></div>
                    <div id="queue-failed-section" style="display:none;">
                        <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Jobs échoués récents</div>
                        <div id="queue-failed-list"></div>
                    </div>
                </div>
            </div>

            {{-- Tab System --}}
            <div id="perf-content-system" class="perf-content" style="display:none;">
                <div id="system-loading" style="text-align:center;padding:30px;color:#94a3b8;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size:22px;"></i>
                    <div style="margin-top:8px;font-size:13px;">Analyse système…</div>
                </div>
                <div id="system-content" style="display:none;"></div>
            </div>

            {{-- Tab Logs --}}
            <div id="perf-content-logs" class="perf-content" style="display:none;">
                <div id="logs-loading" style="text-align:center;padding:30px;color:#94a3b8;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size:22px;"></i>
                    <div style="margin-top:8px;font-size:13px;">Analyse des logs…</div>
                </div>
                <div id="logs-content" style="display:none;"></div>
            </div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-green sa-btn-sm" onclick="reloadCurrentPerfTab()">
                <i class="fa-solid fa-rotate"></i> Actualiser
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-perf-bdd')">Fermer</button>
        </div>
    </div>
</div>

<style>
.perf-tab { flex:1; border:none; background:transparent; border-radius:9px; padding:8px 6px; font-size:11px; font-weight:700; cursor:pointer; color:#64748b; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:5px; white-space:nowrap; }
.perf-tab.active { background:white; color:#0053b3; box-shadow:0 1px 4px rgba(15,23,42,.08); }
</style>

<script>
let currentPerfTab = 'db';
let perfLoaded = {};

function switchPerfTab(tab) {
    currentPerfTab = tab;
    document.querySelectorAll('.perf-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('perf-tab-' + tab)?.classList.add('active');
    document.querySelectorAll('.perf-content').forEach(c => c.style.display = 'none');
    document.getElementById('perf-content-' + tab).style.display = 'block';
    if (!perfLoaded[tab]) loadPerfTab(tab);
}

function reloadCurrentPerfTab() {
    perfLoaded[currentPerfTab] = false;
    loadPerfTab(currentPerfTab);
}

function loadPerfTab(tab) {
    perfLoaded[tab] = true;

    const loadingEl = document.getElementById(tab + '-loading');
    const contentEl = document.getElementById(tab + '-content');
    if (loadingEl) loadingEl.style.display = 'block';
    if (contentEl) contentEl.style.display = 'none';

    const routes = {
        db:     "{{ route('admin.superadmin.metrics.db-stats') }}",
        queue:  "{{ route('admin.superadmin.metrics.queue') }}",
        system: "{{ route('admin.superadmin.metrics.performance') }}",
        logs:   "{{ route('admin.superadmin.metrics.logs-info') }}",
    };

    fetch(routes[tab])
        .then(r => r.json())
        .then(data => {
            if (loadingEl) loadingEl.style.display = 'none';
            if (contentEl) contentEl.style.display = 'block';
            renderPerfTab(tab, data);
        })
        .catch(() => {
            if (loadingEl) loadingEl.innerHTML = '<span style="color:#ef4444;">Erreur de chargement</span>';
        });
}

function renderPerfTab(tab, data) {
    if (tab === 'db') {
        const summary = document.getElementById('db-summary');
        const tbody   = document.getElementById('db-tables-body');
        const totalMb = data.total_size_mb ?? 0;
        const maxMb   = 500;

        summary.innerHTML = `
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;font-weight:900;color:#10b981;">${totalMb} MB</div>
                <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Taille totale</div>
                <div style="background:#f1f5f9;border-radius:999px;height:6px;margin-top:8px;overflow:hidden;">
                    <div style="height:100%;border-radius:999px;background:${totalMb > 400 ? '#ef4444' : totalMb > 200 ? '#f59e0b' : '#10b981'};width:${Math.min(100, (totalMb/maxMb)*100)}%;transition:width 1s;"></div>
                </div>
            </div>
            <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:20px;font-weight:900;color:#3b82f6;">${(data.tables??[]).length}</div>
                <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Tables</div>
            </div>
            <div style="background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:13px;font-weight:800;color:#92400e;word-break:break-all;">${data.db_name ?? '—'}</div>
                <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Base Railway</div>
            </div>
        `;

        tbody.innerHTML = (data.tables ?? []).map(t => `
            <tr>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;"><code style="background:#f1f5f9;padding:2px 7px;border-radius:5px;font-size:11px;">${t.table}</code></td>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-weight:700;font-size:12px;">${Number(t.rows ?? 0).toLocaleString()}</td>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;">${t.size_mb ?? 0} MB</td>
                <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                    <div style="background:#f1f5f9;border-radius:999px;height:6px;width:100px;overflow:hidden;">
                        <div style="height:100%;border-radius:999px;background:${parseFloat(t.size_mb??0)>50?'#ef4444':parseFloat(t.size_mb??0)>10?'#f59e0b':'#10b981'};width:${Math.min(100,parseFloat(t.size_mb??0)/Math.max(parseFloat(data.total_size_mb||1),1)*100)}%;transition:width 1s;"></div>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    if (tab === 'queue') {
        const summary = document.getElementById('queue-summary');
        summary.innerHTML = `
            <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:22px;font-weight:900;color:#3b82f6;">${data.pending ?? 0}</div>
                <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">En attente</div>
            </div>
            <div style="background:${(data.failed??0)>0?'#fff5f5':'#f0fdf4'};border:1px solid ${(data.failed??0)>0?'#fecaca':'#bbf7d0'};border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:22px;font-weight:900;color:${(data.failed??0)>0?'#ef4444':'#10b981'};">${data.failed ?? 0}</div>
                <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Échoués</div>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:14px;font-weight:900;color:#0f172a;">${(data.by_queue??[]).length} queue(s)</div>
                <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px;">Canaux actifs</div>
            </div>
        `;
    }

    if (tab === 'system') {
        const content = document.getElementById('system-content');
        content.innerHTML = `
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
                ${[
                    ['PHP', data.php_version, '#3b82f6', 'fa-code'],
                    ['Laravel', data.laravel_version, '#ef4444', 'fa-laravel'],
                    ['Mémoire', (data.memory_mb ?? 0) + ' MB', '#10b981', 'fa-memory'],
                    ['Mémoire peak', (data.memory_peak ?? 0) + ' MB', '#f97316', 'fa-chart-bar'],
                    ['Environnement', data.env ?? '—', data.env === 'production' ? '#10b981' : '#f59e0b', 'fa-server'],
                    ['Log size', (data.log_size_mb ?? 0) + ' MB', '#8b5cf6', 'fa-scroll'],
                ].map(([label, val, color, icon]) => `
                    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:14px;display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:${color}15;color:${color};display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;">
                            <i class="fa-solid ${icon}"></i>
                        </div>
                        <div>
                            <div style="font-size:14px;font-weight:900;color:#0f172a;">${val}</div>
                            <div style="font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;">${label}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    if (tab === 'logs') {
        const content  = document.getElementById('logs-content');
        const files    = data.files ?? [];
        const totalKb  = data.total_kb ?? 0;
        content.innerHTML = `
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:12px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;font-weight:700;color:#92400e;"><i class="fa-solid fa-scroll" style="margin-right:6px;"></i>${files.length} fichier(s) — ${totalKb} KB total</span>
                <button class="sa-btn sa-btn-danger sa-btn-sm" onclick="openModal('modal-purge-bdd');">
                    <i class="fa-solid fa-broom"></i> Vider les logs
                </button>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead><tr>
                        <th style="text-align:left;padding:8px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;">Fichier</th>
                        <th style="text-align:left;padding:8px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;">Taille</th>
                        <th style="text-align:left;padding:8px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;">Modifié</th>
                    </tr></thead>
                    <tbody>
                        ${files.map(f => `
                            <tr>
                                <td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;"><code style="background:#f1f5f9;padding:2px 6px;border-radius:5px;font-size:10px;">${f.name}</code></td>
                                <td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;font-weight:700;color:${f.size_kb > 5000 ? '#ef4444' : f.size_kb > 1000 ? '#f59e0b' : '#10b981'};">${f.size_kb} KB</td>
                                <td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;color:#64748b;">${f.modified}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
}

// Auto-load DB tab à l'ouverture
const origOpenModal = window.openModal;
window.openModal = function(id) {
    origOpenModal && origOpenModal(id);
    if (id === 'modal-perf-bdd' && !perfLoaded['db']) {
        setTimeout(() => loadPerfTab('db'), 300);
    }
};
</script>
