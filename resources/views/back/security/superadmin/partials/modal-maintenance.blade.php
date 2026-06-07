{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-maintenance.blade.php
     Usage: @include('back.security.superadmin.partials.modal-maintenance')
     Description: Mode maintenance ON/OFF + status app
════════════════════════════════════════════════════ --}}

<div id="modal-maintenance" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(620px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-screwdriver-wrench" style="color:#f97316;"></i>
                Mode Maintenance
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-maintenance')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Status actuel --}}
            <div id="maintenance-status-loading" style="text-align:center;padding:20px;color:#94a3b8;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size:20px;"></i>
                <div style="margin-top:8px;font-size:13px;">Vérification du statut…</div>
            </div>

            <div id="maintenance-status-content" style="display:none;">

                {{-- App status card --}}
                <div id="app-status-card" style="border-radius:14px;padding:16px;margin-bottom:18px;display:flex;align-items:center;gap:14px;">
                    <div id="app-status-icon" style="width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;"></div>
                    <div style="flex:1;">
                        <div id="app-status-title" style="font-size:16px;font-weight:900;"></div>
                        <div id="app-status-desc" style="font-size:12px;color:#64748b;margin-top:3px;"></div>
                    </div>
                    <div id="app-status-badge"></div>
                </div>

                {{-- Infos système --}}
                <div id="app-infos-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:18px;"></div>

                {{-- Mise en maintenance --}}
                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;margin-bottom:16px;">
                    <div style="font-size:12px;font-weight:800;color:#92400e;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Activer le mode maintenance
                    </div>
                    <p style="font-size:12px;color:#78350f;margin:0 0 12px;">L'application affichera une page de maintenance aux utilisateurs. Vous resterez connecté grâce au secret.</p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                        <div>
                            <label style="font-size:11px;font-weight:800;color:#334155;display:block;margin-bottom:4px;">Secret de bypass</label>
                            <input type="text" id="maintenance-secret" placeholder="ex: mon-secret-123"
                                   style="width:100%;border:1.5px solid #fed7aa;border-radius:8px;padding:8px 10px;font-size:12px;outline:none;box-sizing:border-box;background:white;">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:800;color:#334155;display:block;margin-bottom:4px;">Message affiché</label>
                            <input type="text" id="maintenance-message" placeholder="Maintenance en cours…"
                                   style="width:100%;border:1.5px solid #fed7aa;border-radius:8px;padding:8px 10px;font-size:12px;outline:none;box-sizing:border-box;background:white;">
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="sa-btn sa-btn-sm" style="background:#f97316;color:white;" onclick="toggleMaintenanceMode('down')">
                            <i class="fa-solid fa-power-off"></i> Activer la maintenance
                        </button>
                        <button class="sa-btn sa-btn-green sa-btn-sm" onclick="toggleMaintenanceMode('up')">
                            <i class="fa-solid fa-circle-check"></i> Remettre en ligne
                        </button>
                    </div>
                </div>

                {{-- Résultat --}}
                <div id="maintenance-result" style="display:none;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

            </div>
        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="loadMaintenanceStatus()">
                <i class="fa-solid fa-rotate"></i> Actualiser
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-maintenance')">Fermer</button>
        </div>
    </div>
</div>

<script>
let maintenanceLoaded = false;

function loadMaintenanceStatus() {
    const loading = document.getElementById('maintenance-status-loading');
    const content = document.getElementById('maintenance-status-content');
    if (loading) loading.style.display = 'block';
    if (content) content.style.display = 'none';

    fetch("{{ route('admin.superadmin.maintenance.status') }}")
        .then(r => r.json())
        .then(data => {
            if (loading) loading.style.display = 'none';
            if (content) content.style.display = 'block';
            renderMaintenanceStatus(data);
            maintenanceLoaded = true;
        })
        .catch(() => {
            if (loading) loading.innerHTML = '<span style="color:#ef4444;">Erreur de chargement</span>';
        });
}

function renderMaintenanceStatus(data) {
    const isMaintenance = data.maintenance ?? false;
    const card    = document.getElementById('app-status-card');
    const icon    = document.getElementById('app-status-icon');
    const title   = document.getElementById('app-status-title');
    const desc    = document.getElementById('app-status-desc');
    const badge   = document.getElementById('app-status-badge');
    const infos   = document.getElementById('app-infos-grid');

    if (isMaintenance) {
        card.style.background  = '#fff5f5';
        card.style.border      = '1px solid #fecaca';
        icon.style.background  = '#fee2e2';
        icon.style.color       = '#ef4444';
        icon.innerHTML         = '<i class="fa-solid fa-power-off"></i>';
        title.style.color      = '#991b1b';
        title.textContent      = 'Application en maintenance';
        desc.textContent       = 'Les utilisateurs voient la page de maintenance.';
        badge.innerHTML        = '<span style="background:#fee2e2;color:#991b1b;border-radius:999px;padding:5px 10px;font-size:11px;font-weight:800;"><i class="fa-solid fa-circle" style="font-size:7px;"></i> MAINTENANCE</span>';
    } else {
        card.style.background  = '#f0fdf4';
        card.style.border      = '1px solid #bbf7d0';
        icon.style.background  = '#d1fae5';
        icon.style.color       = '#10b981';
        icon.innerHTML         = '<i class="fa-solid fa-circle-check"></i>';
        title.style.color      = '#065f46';
        title.textContent      = 'Application en ligne';
        desc.textContent       = 'L\'application fonctionne normalement.';
        badge.innerHTML        = '<span style="background:#d1fae5;color:#065f46;border-radius:999px;padding:5px 10px;font-size:11px;font-weight:800;"><i class="fa-solid fa-circle" style="font-size:7px;animation:pulse 2s infinite;"></i> EN LIGNE</span>';
    }

    infos.innerHTML = [
        ['App Name',    data.app_name    ?? '—', '#3b82f6', 'fa-tag'],
        ['PHP',         data.php_version ?? '—', '#8b5cf6', 'fa-code'],
        ['Laravel',     data.laravel     ?? '—', '#ef4444', 'fa-laravel'],
        ['Env',         data.env         ?? '—', data.env === 'production' ? '#10b981' : '#f59e0b', 'fa-server'],
        ['URL',         (data.app_url ?? '—').replace('https://',''), '#06b6d4', 'fa-link'],
        ['Statut',      isMaintenance ? 'Maintenance' : 'Online', isMaintenance ? '#ef4444' : '#10b981', 'fa-circle'],
    ].map(([label, val, color, icon]) => `
        <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                <i class="fa-solid ${icon}" style="color:${color};font-size:11px;"></i>
                <span style="font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">${label}</span>
            </div>
            <div style="font-size:12px;font-weight:800;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${val}</div>
        </div>
    `).join('');
}

function toggleMaintenanceMode(action) {
    const secret  = document.getElementById('maintenance-secret')?.value  ?? '';
    const message = document.getElementById('maintenance-message')?.value ?? '';
    const result  = document.getElementById('maintenance-result');

    fetch("{{ route('admin.superadmin.maintenance.toggle') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ action, secret, message }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        result.style.background   = data.success ? '#f0fdf4' : '#fff5f5';
        result.style.border       = `1px solid ${data.success ? '#bbf7d0' : '#fecaca'}`;
        result.style.color        = data.success ? '#166534' : '#991b1b';
        result.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${data.message ?? (data.success ? 'Succès' : 'Erreur')}`;
        setTimeout(loadMaintenanceStatus, 1000);
    });
}

// Auto-load à l'ouverture
document.addEventListener('DOMContentLoaded', () => {
    const origOpen = window.openModal;
    window.openModal = function(id) {
        if (typeof origOpen === 'function') origOpen(id);
        if (id === 'modal-maintenance' && !maintenanceLoaded) {
            setTimeout(loadMaintenanceStatus, 200);
        }
    };
});
</script>
