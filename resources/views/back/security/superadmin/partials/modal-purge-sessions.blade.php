{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-purge-sessions.blade.php
     Usage: @include('back.security.superadmin.partials.modal-purge-sessions')
     Description: Vider les sessions expirées
════════════════════════════════════════════════════ --}}

<div id="modal-purge-sessions" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(520px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-cookie-bite" style="color:#ef4444;"></i>
                Vider les sessions
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-sessions')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                try {
                    $totalSessions   = \Illuminate\Support\Facades\DB::table('sessions')->count();
                    $expiredSessions = \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('last_activity', '<', now()->subHours(24)->timestamp)->count();
                } catch(\Throwable) {
                    $totalSessions = $expiredSessions = 0;
                }
            @endphp

            {{-- Stats --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:14px;text-align:center;">
                    <i class="fa-solid fa-cookie" style="color:#3b82f6;font-size:18px;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:22px;font-weight:900;color:#0f172a;">{{ number_format($totalSessions,0,',',' ') }}</div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Sessions totales</div>
                </div>
                <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:14px;text-align:center;">
                    <i class="fa-solid fa-cookie-bite" style="color:#ef4444;font-size:18px;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:22px;font-weight:900;color:#ef4444;">{{ number_format($expiredSessions,0,',',' ') }}</div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Expirées (> 24h)</div>
                </div>
            </div>

            {{-- Info --}}
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;margin-bottom:16px;font-size:12px;color:#166534;display:flex;gap:8px;line-height:1.5;">
                <i class="fa-solid fa-circle-check" style="flex-shrink:0;margin-top:1px;"></i>
                <div>Cette opération est <strong>sûre</strong> — elle supprime uniquement les sessions de plus de 24h. Les utilisateurs actuellement connectés ne seront pas déconnectés.</div>
            </div>

            {{-- Confirmation --}}
            <div style="margin-bottom:6px;">
                <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:6px;">
                    Tapez <strong style="color:#ef4444;">CONFIRMER</strong> pour valider :
                </label>
                <input type="text" id="sessions-confirm-input" placeholder="CONFIRMER"
                       style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .2s;"
                       oninput="document.getElementById('sessions-purge-btn').disabled = this.value.trim() !== 'CONFIRMER'">
            </div>

            {{-- Résultat --}}
            <div id="sessions-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

        </div>

        <div class="sa-modal-footer">
            <button id="sessions-purge-btn" class="sa-btn sa-btn-danger sa-btn-sm" disabled onclick="executePurgeSessions()">
                <i class="fa-solid fa-broom"></i> Vider les sessions expirées
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-purge-sessions')">Annuler</button>
        </div>
    </div>
</div>

<script>
function executePurgeSessions() {
    const input   = document.getElementById('sessions-confirm-input');
    const btn     = document.getElementById('sessions-purge-btn');
    const result  = document.getElementById('sessions-result');

    if (input.value.trim() !== 'CONFIRMER') return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Purge en cours…';

    fetch("{{ route('admin.superadmin.purge.sessions') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ confirm: 'CONFIRMER' }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        result.style.background   = data.success ? '#f0fdf4' : '#fff5f5';
        result.style.border       = `1px solid ${data.success ? '#bbf7d0' : '#fecaca'}`;
        result.style.color        = data.success ? '#166534' : '#991b1b';
        result.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${data.message ?? (data.success ? 'Succès' : 'Erreur')}`;
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Vider les sessions expirées';
        input.value = '';
        btn.disabled = true;
    })
    .catch(() => {
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Vider les sessions expirées';
        btn.disabled = false;
    });
}
</script>
