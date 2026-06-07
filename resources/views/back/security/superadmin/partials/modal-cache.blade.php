{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-cache.blade.php
     Usage: @include('back.security.superadmin.partials.modal-cache')
════════════════════════════════════════════════════ --}}

<div id="modal-cache" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(600px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-bolt" style="color:#f97316;"></i>
                Gestion du cache & Artisan
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-cache')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
                Exécutez des commandes Artisan directement depuis l'interface. Utile après un déploiement ou une mise à jour de configuration.
            </p>

            {{-- Commandes groupées --}}
            <div style="margin-bottom:16px;">
                <div style="font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;margin-bottom:10px;">Vider le cache</div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
                    @foreach([
                        ['cache:clear',    'Cache applicatif',  '#f97316', 'fa-bolt',         'Vide le cache runtime'],
                        ['config:clear',   'Cache config',      '#3b82f6', 'fa-gear',          'Recharge .env et config/'],
                        ['route:clear',    'Cache routes',      '#8b5cf6', 'fa-route',         'Recharge routes/'],
                        ['view:clear',     'Cache vues',        '#06b6d4', 'fa-eye',           'Recompile les vues Blade'],
                    ] as $cmd)
                    <div class="artisan-card" onclick="runArtisan('{{ $cmd[0] }}')"
                         style="border:1.5px solid #e2e8f0;border-radius:12px;padding:13px;cursor:pointer;transition:all .2s;display:flex;align-items:flex-start;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;background:{{ $cmd[2] }}15;color:{{ $cmd[2] }};">
                            <i class="fa-solid {{ $cmd[3] }}"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $cmd[1] }}</div>
                            <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $cmd[4] }}</div>
                            <code style="font-size:9px;color:#64748b;background:#f8fafc;padding:1px 5px;border-radius:4px;margin-top:4px;display:inline-block;">php artisan {{ $cmd[0] }}</code>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <div style="font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;margin-bottom:10px;">Reconstruire le cache</div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
                    @foreach([
                        ['config:cache',   'Rebuild config',    '#10b981', 'fa-circle-check',  'Optimise les configs'],
                        ['route:cache',    'Rebuild routes',    '#10b981', 'fa-route',         'Optimise le routing'],
                        ['view:cache',     'Rebuild vues',      '#10b981', 'fa-eye',           'Precompile les vues'],
                        ['optimize:clear', 'Tout vider',        '#ef4444', 'fa-rocket',        'Vide tous les caches'],
                    ] as $cmd)
                    <div class="artisan-card" onclick="runArtisan('{{ $cmd[0] }}')"
                         style="border:1.5px solid #e2e8f0;border-radius:12px;padding:13px;cursor:pointer;transition:all .2s;display:flex;align-items:flex-start;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;background:{{ $cmd[2] }}15;color:{{ $cmd[2] }};">
                            <i class="fa-solid {{ $cmd[3] }}"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $cmd[1] }}</div>
                            <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $cmd[4] }}</div>
                            <code style="font-size:9px;color:#64748b;background:#f8fafc;padding:1px 5px;border-radius:4px;margin-top:4px;display:inline-block;">php artisan {{ $cmd[0] }}</code>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Terminal output --}}
            <div id="artisan-terminal" style="display:none;background:#0f172a;border-radius:12px;padding:14px;font-family:monospace;font-size:12px;color:#94a3b8;min-height:60px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #1e293b;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                    <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>
                    <span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                    <span style="color:#475569;font-size:10px;margin-left:4px;">Terminal Artisan</span>
                </div>
                <div id="artisan-output"></div>
            </div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft" onclick="closeModal('modal-cache')">Fermer</button>
        </div>
    </div>
</div>

<style>
.artisan-card:hover { border-color:#3b82f6 !important; background:#f8faff; transform:translateY(-1px); box-shadow:0 4px 12px rgba(59,130,246,.1); }
.artisan-card.loading { border-color:#f59e0b !important; background:#fffbeb; }
.artisan-card.done-ok { border-color:#10b981 !important; background:#f0fdf4; }
.artisan-card.done-err { border-color:#ef4444 !important; background:#fff5f5; }
</style>

<script>
function runArtisan(command) {
    const terminal = document.getElementById('artisan-terminal');
    const output   = document.getElementById('artisan-output');

    terminal.style.display = 'block';
    output.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${command}</span>\n<span style="color:#64748b;"><i class="fa-solid fa-circle-notch fa-spin"></i> Exécution en cours…</span>`;

    fetch("{{ route('admin.superadmin.cache.clear') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ command }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            output.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${command}</span>\n<span style="color:#10b981;">✓ ${data.output || 'Succès'}</span>`;
        } else {
            output.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${command}</span>\n<span style="color:#ef4444;">✗ Erreur: ${data.message}</span>`;
        }
    })
    .catch(err => {
        output.innerHTML = `<span style="color:#f59e0b;">$ php artisan ${command}</span>\n<span style="color:#ef4444;">✗ Erreur réseau</span>`;
    });
}
</script>
