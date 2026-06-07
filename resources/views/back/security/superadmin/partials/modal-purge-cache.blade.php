{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-purge-cache.blade.php
     Usage: @include('back.security.superadmin.partials.modal-purge-cache')
     Description: Vider les entrées de cache expirées en base
════════════════════════════════════════════════════ --}}

<div id="modal-purge-cache" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(520px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-database" style="color:#06b6d4;"></i>
                Purge du cache base de données
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-cache')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                try {
                    $cacheTotal   = \Illuminate\Support\Facades\DB::table('cache')->count();
                    $cacheExpired = \Illuminate\Support\Facades\DB::table('cache')
                        ->where('expiration', '<', now()->timestamp)->count();
                    $cacheActive  = $cacheTotal - $cacheExpired;
                } catch(\Throwable) {
                    $cacheTotal = $cacheExpired = $cacheActive = 0;
                }
            @endphp

            {{-- Stats --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
                @foreach([
                    [$cacheTotal,   'Total cache',   '#3b82f6', 'fa-layer-group'],
                    [$cacheActive,  'Actifs',        '#10b981', 'fa-circle-check'],
                    [$cacheExpired, 'Expirés',       '#ef4444', 'fa-circle-xmark'],
                ] as $s)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;">
                    <i class="fa-solid {{ $s[3] }}" style="color:{{ $s[2] }};font-size:16px;display:block;margin-bottom:5px;"></i>
                    <div style="font-size:20px;font-weight:900;color:#0f172a;">{{ number_format($s[0],0,',',' ') }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:2px;">{{ $s[1] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Infos --}}
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px;margin-bottom:16px;font-size:12px;color:#0369a1;display:flex;gap:8px;line-height:1.5;">
                <i class="fa-solid fa-circle-info" style="flex-shrink:0;margin-top:1px;"></i>
                <div>Cette opération supprime les entrées de la table <code>cache</code> dont la date d'expiration est passée. Les entrées actives sont conservées. Aucun impact sur les utilisateurs connectés.</div>
            </div>

            {{-- Options --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                <div class="sa-option-card" id="purge-cache-opt-expired" onclick="selectCachePurge('expired',this)" style="border:2px solid #e2e8f0;border-radius:12px;padding:13px;cursor:pointer;text-align:center;">
                    <i class="fa-solid fa-clock" style="color:#f59e0b;font-size:18px;display:block;margin-bottom:5px;"></i>
                    <div style="font-weight:800;font-size:12px;color:#0f172a;">Entrées expirées</div>
                    <div style="font-size:10px;color:#64748b;margin-top:2px;">{{ $cacheExpired }} entrée(s)</div>
                </div>
                <div class="sa-option-card" id="purge-cache-opt-all" onclick="selectCachePurge('all',this)" style="border:2px solid #e2e8f0;border-radius:12px;padding:13px;cursor:pointer;text-align:center;">
                    <i class="fa-solid fa-trash" style="color:#ef4444;font-size:18px;display:block;margin-bottom:5px;"></i>
                    <div style="font-weight:800;font-size:12px;color:#0f172a;">Tout le cache</div>
                    <div style="font-size:10px;color:#64748b;margin-top:2px;">{{ $cacheTotal }} entrée(s)</div>
                </div>
            </div>

            {{-- Artisan cache:clear aussi --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:12px;font-weight:800;color:#334155;">Vider aussi le cache fichier</div>
                    <div style="font-size:10px;color:#64748b;margin-top:2px;"><code>php artisan cache:clear</code></div>
                </div>
                <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="runArtisan('cache:clear');closeModal('modal-purge-cache');">
                    <i class="fa-solid fa-bolt"></i> Exécuter
                </button>
            </div>

            {{-- Confirmation --}}
            <div id="cache-confirm-section" style="display:none;">
                <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:6px;">
                    Tapez <strong style="color:#ef4444;">CONFIRMER</strong> pour valider :
                </label>
                <input type="text" id="cache-confirm-input" placeholder="CONFIRMER"
                       style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;outline:none;box-sizing:border-box;"
                       oninput="document.getElementById('cache-purge-btn').disabled = this.value.trim() !== 'CONFIRMER'">
            </div>

            <div id="cache-purge-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

        </div>

        <div class="sa-modal-footer">
            <button id="cache-purge-btn" class="sa-btn sa-btn-danger sa-btn-sm" disabled onclick="executePurgeCache()">
                <i class="fa-solid fa-broom"></i> Purger le cache
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-purge-cache')">Annuler</button>
        </div>
    </div>
</div>

<script>
let selectedCacheOption = null;

function selectCachePurge(option, el) {
    selectedCacheOption = option;
    document.querySelectorAll('#purge-cache-opt-expired, #purge-cache-opt-all').forEach(o => {
        o.style.borderColor = '';
        o.style.background  = '';
    });
    el.style.borderColor = '#ef4444';
    el.style.background  = '#fff5f5';

    document.getElementById('cache-confirm-section').style.display = 'block';
    document.getElementById('cache-purge-btn').disabled = true;
}

function executePurgeCache() {
    const input  = document.getElementById('cache-confirm-input');
    const btn    = document.getElementById('cache-purge-btn');
    const result = document.getElementById('cache-purge-result');

    if (input.value.trim() !== 'CONFIRMER' || !selectedCacheOption) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Purge…';

    fetch("{{ route('admin.superadmin.purge.cache') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ confirm: 'CONFIRMER', mode: selectedCacheOption }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display  = 'block';
        result.style.background = data.success ? '#f0fdf4' : '#fff5f5';
        result.style.border     = `1px solid ${data.success ? '#bbf7d0' : '#fecaca'}`;
        result.style.color      = data.success ? '#166534' : '#991b1b';
        result.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${data.message}`;
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger le cache';
        input.value  = '';
        btn.disabled = true;
    })
    .catch(() => {
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger le cache';
        btn.disabled  = false;
    });
}
</script>
