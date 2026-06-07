{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-perf-queue.blade.php
     Usage: @include('back.security.superadmin.partials.modal-perf-queue')
     Description: Monitoring queue worker Railway en temps réel
════════════════════════════════════════════════════ --}}

<div id="modal-perf-queue" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(720px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-list-check" style="color:#3b82f6;"></i>
                File d'attente — Queue Worker
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-perf-queue')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Status worker --}}
            <div id="queue-worker-status" style="border-radius:14px;padding:14px;margin-bottom:16px;display:flex;align-items:center;gap:12px;background:#f0fdf4;border:1px solid #bbf7d0;">
                <div style="width:42px;height:42px;border-radius:12px;background:#d1fae5;color:#10b981;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                </div>
                <div>
                    <div style="font-weight:900;font-size:14px;color:#065f46;">Chargement du statut…</div>
                    <div style="font-size:11px;color:#6ee7b7;margin-top:2px;">Connexion à Railway en cours</div>
                </div>
                <div style="margin-left:auto;">
                    <span id="worker-badge" style="background:#d1fae5;color:#065f46;border-radius:999px;padding:5px 12px;font-size:11px;font-weight:800;">…</span>
                </div>
            </div>

            {{-- KPIs queue --}}
            <div id="queue-kpis" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px;">
                @foreach([['pending','En attente','#3b82f6','fa-clock'],['failed','Échoués','#ef4444','fa-circle-xmark'],['queues','Canaux','#8b5cf6','fa-layer-group'],['sessions','Sessions actives','#10b981','fa-users']] as $k)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;">
                    <i class="fa-solid {{ $k[3] }}" style="color:{{ $k[2] }};font-size:16px;display:block;margin-bottom:5px;"></i>
                    <div id="queue-kpi-{{ $k[0] }}" style="font-size:20px;font-weight:900;color:#0f172a;">—</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:2px;">{{ $k[1] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Par canal --}}
            <div id="queue-by-channel" style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:14px;display:none;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Par canal</div>
                <div id="queue-channels-content"></div>
            </div>

            {{-- Failed jobs --}}
            <div id="queue-failed-section" style="display:none;">
                <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:14px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <div style="font-size:11px;font-weight:800;color:#991b1b;text-transform:uppercase;letter-spacing:.08em;">
                            <i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i>
                            Jobs échoués récents
                        </div>
                        <form method="POST" action="{{ route('admin.superadmin.purge.failed-jobs') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="confirm" value="CONFIRMER">
                            <button type="submit" class="sa-btn sa-btn-danger sa-btn-sm"
                                    onclick="return confirm('Vider tous les failed jobs ?')">
                                <i class="fa-solid fa-trash"></i> Vider
                            </button>
                        </form>
                    </div>
                    <div id="failed-jobs-list"></div>
                </div>
            </div>

            {{-- Actions --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Actions Artisan</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="runArtisanQueue('queue:restart')">
                        <i class="fa-solid fa-rotate"></i> Redémarrer worker
                    </button>
                    <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="runArtisanQueue('queue:flush')">
                        <i class="fa-solid fa-broom"></i> Vider failed jobs
                    </button>
                </div>
                <div id="queue-artisan-result" style="display:none;margin-top:10px;background:#0f172a;border-radius:8px;padding:10px;font-family:monospace;font-size:11px;color:#94a3b8;"></div>
            </div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-green sa-btn-sm" onclick="loadQueueStats()">
                <i class="fa-solid fa-rotate"></i> Actualiser
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-perf-queue')">Fermer</button>
        </div>
    </div>
</div>

<script>
let queueStatsLoaded = false;

function loadQueueStats() {
    fetch("{{ route('admin.superadmin.metrics.queue') }}")
        .then(r => r.json())
        .then(data => {
            renderQueueStats(data);
            queueStatsLoaded = true;
        })
        .catch(() => {
            document.getElementById('queue-worker-status').innerHTML = '<span style="color:#ef4444;">Erreur de chargement</span>';
        });
}

function renderQueueStats(data) {
    // Status card
    const hasFailed = (data.failed ?? 0) > 0;
    const hasPending = (data.pending ?? 0) > 0;
    const statusEl = document.getElementById('queue-worker-status');

    if (hasFailed) {
        statusEl.style.background = '#fff5f5';
        statusEl.style.borderColor = '#fecaca';
        statusEl.innerHTML = `
            <div style="width:42px;height:42px;border-radius:12px;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div><div style="font-weight:900;font-size:14px;color:#991b1b;">Jobs échoués détectés</div><div style="font-size:11px;color:#f87171;margin-top:2px;">${data.failed} job(s) en erreur</div></div>
            <div style="margin-left:auto;"><span style="background:#fee2e2;color:#991b1b;border-radius:999px;padding:5px 12px;font-size:11px;font-weight:800;">ATTENTION</span></div>
        `;
    } else {
        statusEl.style.background = '#f0fdf4';
        statusEl.style.borderColor = '#bbf7d0';
        statusEl.innerHTML = `
            <div style="width:42px;height:42px;border-radius:12px;background:#d1fae5;color:#10b981;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;"><i class="fa-solid fa-circle-check"></i></div>
            <div><div style="font-weight:900;font-size:14px;color:#065f46;">Worker opérationnel</div><div style="font-size:11px;color:#6ee7b7;margin-top:2px;">${data.pending ?? 0} job(s) en attente</div></div>
            <div style="margin-left:auto;"><span style="background:#d1fae5;color:#065f46;border-radius:999px;padding:5px 12px;font-size:11px;font-weight:800;"><i class="fa-solid fa-circle" style="font-size:7px;animation:pulse 2s infinite;"></i> EN LIGNE</span></div>
        `;
    }

    // KPIs
    document.getElementById('queue-kpi-pending').textContent  = data.pending ?? 0;
    document.getElementById('queue-kpi-failed').textContent   = data.failed  ?? 0;
    document.getElementById('queue-kpi-failed').style.color   = hasFailed ? '#ef4444' : '#10b981';
    document.getElementById('queue-kpi-queues').textContent   = (data.by_queue ?? []).length;

    try {
        fetch("{{ route('admin.superadmin.metrics.performance') }}")
            .then(r => r.json())
            .then(p => {
                document.getElementById('queue-kpi-sessions').textContent = p.queue?.sessions ?? 0;
            });
    } catch(e) {}

    // Par canal
    if ((data.by_queue ?? []).length > 0) {
        const channelEl = document.getElementById('queue-by-channel');
        const contentEl = document.getElementById('queue-channels-content');
        channelEl.style.display = 'block';
        contentEl.innerHTML = (data.by_queue ?? []).map(q => `
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <code style="background:#f1f5f9;padding:3px 7px;border-radius:6px;font-size:11px;min-width:80px;">${q.queue}</code>
                <div style="flex:1;background:#f1f5f9;border-radius:999px;height:8px;overflow:hidden;">
                    <div style="height:100%;border-radius:999px;background:#3b82f6;width:${Math.min(100, q.count * 10)}%;"></div>
                </div>
                <span style="font-size:12px;font-weight:900;color:#0f172a;">${q.count}</span>
            </div>
        `).join('');
    }

    // Failed jobs
    if ((data.recent_failed ?? []).length > 0) {
        const failedSection = document.getElementById('queue-failed-section');
        const failedList    = document.getElementById('failed-jobs-list');
        failedSection.style.display = 'block';
        failedList.innerHTML = (data.recent_failed ?? []).map(j => `
            <div style="border:1px solid #fecaca;border-radius:9px;padding:10px;margin-bottom:6px;background:white;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                    <div>
                        <div style="font-weight:800;font-size:12px;color:#991b1b;">${j.job ?? 'Unknown'}</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:3px;font-family:monospace;">${j.exception ?? ''}</div>
                    </div>
                    <div style="font-size:10px;color:#94a3b8;white-space:nowrap;">${j.failed_at ?? ''}</div>
                </div>
            </div>
        `).join('');
    }
}

function runArtisanQueue(command) {
    const resultEl = document.getElementById('queue-artisan-result');
    resultEl.style.display = 'block';
    resultEl.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${command}</span>\n<i class="fa-solid fa-circle-notch fa-spin"></i> Exécution…`;

    fetch("{{ route('admin.superadmin.cache.clear') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ command }),
    })
    .then(r => r.json())
    .then(data => {
        resultEl.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${command}</span>\n<span style="color:${data.success ? '#10b981' : '#ef4444'};">${data.success ? '✓ ' : '✗ '}${data.output || data.message}</span>`;
        if (data.success) setTimeout(loadQueueStats, 1000);
    });
}

// Auto-load à l'ouverture
document.addEventListener('click', e => {
    if (e.target.closest('[onclick*="modal-perf-queue"]') && !queueStatsLoaded) {
        setTimeout(loadQueueStats, 300);
    }
});
</script>
