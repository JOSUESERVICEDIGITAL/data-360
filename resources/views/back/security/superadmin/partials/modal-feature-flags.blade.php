<div style="max-width: 860px; margin: 24px auto 0; padding: 0 16px;">
<div id="panel-feature-flags" style="
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 22px 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
">

    {{-- ── En-tête ── --}}
    <div style="display:flex; align-items:center; justify-content:space-between;
                flex-wrap:wrap; gap:12px; margin-bottom:18px;
                padding-bottom:16px; border-bottom:1px solid #f1f5f9;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="
                width:42px; height:42px; background:#fef3c7;
                border-radius:12px; display:flex; align-items:center;
                justify-content:center; color:#b45309; font-size:1.1rem;
                flex-shrink:0;">
                <i class="fa-solid fa-toggle-on"></i>
            </div>
            <div>
                <div style="font-size:1rem; font-weight:800; color:#0f172a; line-height:1.2;">
                    Feature Flags
                </div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">
                    Activer / désactiver des fonctionnalités sans redéployer
                </div>
            </div>
        </div>
        <span style="
            display:inline-flex; align-items:center; gap:6px;
            background:#dcfce7; color:#15803d;
            font-size:0.72rem; font-weight:700; text-transform:uppercase;
            letter-spacing:0.5px; padding:5px 12px; border-radius:40px;">
            <i class="fa-solid fa-circle-check"></i> Temps réel
        </span>
    </div>

    {{-- ── Liste des flags ── --}}
    <div style="display:flex; flex-direction:column; gap:10px;">

        {{-- Flag : Recherche avancée CSV --}}
        @php $advEnabled = \App\Models\AppSetting::isEnabled('advanced_search_enabled'); @endphp

        <div class="ff-card" data-key="advanced_search_enabled"
             style="display:flex; align-items:center; justify-content:space-between;
                    gap:12px; background:#f8fafc; border:1.5px solid #e2e8f0;
                    border-radius:14px; padding:14px 16px; transition:all 0.2s;
                    cursor:default;">
            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                <div style="
                    width:36px; height:36px; background:#e6f0ff;
                    border-radius:10px; display:flex; align-items:center;
                    justify-content:center; color:#0053b3; font-size:0.95rem;
                    flex-shrink:0;">
                    <i class="fa-solid fa-file-csv"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:0.88rem; font-weight:700; color:#0f172a;
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        Recherche avancée — Import CSV
                    </div>
                    <div style="font-size:0.75rem; color:#64748b; margin-top:2px;
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        Formulaire CSV sur la page d'accueil (Premium uniquement)
                    </div>
                </div>
            </div>

            {{-- Toggle --}}
            <label class="ff-toggle-label" style="
                position:relative; display:inline-block;
                width:48px; height:26px; flex-shrink:0; cursor:pointer;">
                <input
                    type="checkbox"
                    class="ff-checkbox"
                    data-key="advanced_search_enabled"
                    {{ $advEnabled ? 'checked' : '' }}
                    style="opacity:0; width:0; height:0; position:absolute;"
                >
                <span class="ff-track" style="
                    position:absolute; inset:0; border-radius:34px;
                    background:{{ $advEnabled ? '#0053b3' : '#cbd5e1' }};
                    transition:background 0.25s;">
                </span>
                <span class="ff-knob" style="
                    position:absolute;
                    width:18px; height:18px;
                    top:4px; left:{{ $advEnabled ? '26px' : '4px' }};
                    background:white; border-radius:50%;
                    box-shadow:0 1px 4px rgba(0,0,0,0.2);
                    transition:left 0.25s; display:block;">
                </span>
            </label>
        </div>

        {{-- ════ AJOUTER D'AUTRES FLAGS ICI ════
        @php $autreFlag = \App\Models\AppSetting::isEnabled('autre_flag'); @endphp
        <div class="ff-card" data-key="autre_flag" ...>
            ...
        </div>
        ══════════════════════════════════════ --}}

    </div>

    {{-- ── Toast ── --}}
    <div id="ffToast" style="
        position:fixed; bottom:22px; right:22px; z-index:9999;
        background:#1e293b; color:white; padding:10px 18px;
        border-radius:10px; font-size:0.82rem; font-weight:500;
        opacity:0; transform:translateY(10px); pointer-events:none;
        transition:all 0.25s ease;
        box-shadow:0 4px 16px rgba(0,0,0,0.25);">
        <i class="fa-solid fa-circle-check" style="color:#10b981; margin-right:7px;"></i>
        <span id="ffToastMsg">Mis à jour</span>
    </div>

</div>
</div>

<style>
    .ff-card:hover {
        border-color: #93c5fd !important;
        background: #f0f7ff !important;
        box-shadow: 0 2px 8px rgba(0,83,179,0.07);
    }
    @media (max-width: 520px) {
        .ff-card {
            flex-direction: column;
            align-items: flex-start !important;
        }
        .ff-toggle-label {
            align-self: flex-end;
        }
    }
</style>

<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
              ?? '{{ csrf_token() }}';
    const URL  = '{{ route("admin.superadmin.settings.toggle") }}';

    document.querySelectorAll('.ff-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const key     = this.dataset.key;
            const enabled = this.checked;
            const card    = this.closest('.ff-card');
            const track   = card.querySelector('.ff-track');
            const knob    = card.querySelector('.ff-knob');

            // Feedback visuel immédiat
            track.style.background = enabled ? '#0053b3' : '#cbd5e1';
            knob.style.left        = enabled ? '26px'   : '4px';

            fetch(URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ key: key, value: enabled }),
            })
            .then(r => r.json())
            .then(function (data) {
                toast(data.success
                    ? (data.enabled ? '✅ Activé' : '🔴 Désactivé')
                    : '❌ ' + (data.message || 'Erreur')
                );
                if (!data.success) revert(cb, track, knob, enabled);
            })
            .catch(function () {
                toast('❌ Erreur réseau');
                revert(cb, track, knob, enabled);
            });
        });
    });

    function revert(cb, track, knob, wasEnabled) {
        cb.checked             = !wasEnabled;
        track.style.background = !wasEnabled ? '#0053b3' : '#cbd5e1';
        knob.style.left        = !wasEnabled ? '26px'   : '4px';
    }

    function toast(msg) {
        const el  = document.getElementById('ffToast');
        const txt = document.getElementById('ffToastMsg');
        if (!el || !txt) return;
        txt.textContent       = msg;
        el.style.opacity      = '1';
        el.style.transform    = 'translateY(0)';
        clearTimeout(el._t);
        el._t = setTimeout(function () {
            el.style.opacity   = '0';
            el.style.transform = 'translateY(10px)';
        }, 2500);
    }
}());
</script>