{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-purge-recherches.blade.php
     Usage: @include('back.security.superadmin.partials.modal-purge-recherches')
     Description: Purger les anciennes recherches
════════════════════════════════════════════════════ --}}

<div id="modal-purge-recherches" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(540px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-broom" style="color:#ef4444;"></i>
                Purger les recherches
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-purge-recherches')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                try {
                    $rechTotal  = \Illuminate\Support\Facades\DB::table('recherches')->count();
                    $rech7days  = \Illuminate\Support\Facades\DB::table('recherches')->where('created_at','<',now()->subDays(7))->count();
                    $rech30days = \Illuminate\Support\Facades\DB::table('recherches')->where('created_at','<',now()->subDays(30))->count();
                    $rech90days = \Illuminate\Support\Facades\DB::table('recherches')->where('created_at','<',now()->subDays(90))->count();
                } catch(\Throwable) {
                    $rechTotal = $rech7days = $rech30days = $rech90days = 0;
                }
            @endphp

            {{-- Stats --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px;">
                @foreach([
                    [$rechTotal,  'Total',    '#3b82f6'],
                    [$rech7days,  '> 7j',     '#f59e0b'],
                    [$rech30days, '> 30j',    '#f97316'],
                    [$rech90days, '> 90j',    '#ef4444'],
                ] as $s)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:11px;padding:11px;text-align:center;">
                    <div style="font-size:18px;font-weight:900;color:{{ $s[2] }};">{{ number_format($s[0],0,',',' ') }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:2px;">{{ $s[1] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Warning --}}
            <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:10px;padding:12px;margin-bottom:14px;font-size:12px;color:#991b1b;display:flex;gap:8px;line-height:1.5;">
                <i class="fa-solid fa-triangle-exclamation" style="flex-shrink:0;margin-top:1px;"></i>
                <div>Suppression <strong>irréversible</strong>. Les recherches supprimées ne pourront pas être récupérées. Recommandé pour libérer l'espace Railway.</div>
            </div>

            {{-- Période --}}
            <div style="margin-bottom:14px;">
                <label style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:8px;">Choisissez une période</label>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
                    @foreach([
                        ['7days',  '+ 7 jours',      '#f59e0b', $rech7days,  'fa-calendar-week'],
                        ['30days', '+ 30 jours',     '#f97316', $rech30days, 'fa-calendar'],
                        ['90days', '+ 90 jours',     '#ef4444', $rech90days, 'fa-calendar-days'],
                        ['all',    'Tout supprimer', '#dc2626', $rechTotal,  'fa-trash'],
                    ] as $opt)
                    <div onclick="selectRecherchesPurge('{{ $opt[0] }}', this)"
                         style="border:2px solid #e2e8f0;border-radius:11px;padding:12px;cursor:pointer;transition:all .2s;text-align:center;"
                         class="purge-recherche-opt">
                        <i class="fa-solid {{ $opt[4] }}" style="color:{{ $opt[2] }};font-size:18px;display:block;margin-bottom:5px;"></i>
                        <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $opt[1] }}</div>
                        <div style="font-size:10px;color:#64748b;margin-top:2px;">{{ number_format($opt[3],0,',',' ') }} recherche(s)</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Confirmation --}}
            <div id="purge-rech-confirm" style="display:none;">
                <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:6px;">
                    Tapez <strong style="color:#ef4444;">CONFIRMER</strong> :
                </label>
                <input type="text" id="purge-rech-input" placeholder="CONFIRMER"
                       style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;outline:none;box-sizing:border-box;"
                       oninput="document.getElementById('purge-rech-btn').disabled = this.value.trim() !== 'CONFIRMER'">
            </div>

            <div id="purge-rech-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

        </div>

        <div class="sa-modal-footer">
            <button id="purge-rech-btn" class="sa-btn sa-btn-danger sa-btn-sm" disabled onclick="executePurgeRecherches()">
                <i class="fa-solid fa-broom"></i> Purger
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-purge-recherches')">Annuler</button>
        </div>
    </div>
</div>

<script>
let selectedRecherchesPeriod = null;

function selectRecherchesPurge(period, el) {
    selectedRecherchesPeriod = period;
    document.querySelectorAll('.purge-recherche-opt').forEach(o => { o.style.borderColor = ''; o.style.background = ''; });
    el.style.borderColor = '#ef4444';
    el.style.background  = '#fff5f5';
    document.getElementById('purge-rech-confirm').style.display = 'block';
    document.getElementById('purge-rech-btn').disabled = true;
}

function executePurgeRecherches() {
    const input  = document.getElementById('purge-rech-input');
    const btn    = document.getElementById('purge-rech-btn');
    const result = document.getElementById('purge-rech-result');

    if (input.value.trim() !== 'CONFIRMER' || !selectedRecherchesPeriod) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Suppression…';

    fetch("{{ route('admin.superadmin.purge.recherches') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ confirm: 'CONFIRMER', period: selectedRecherchesPeriod }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display  = 'block';
        result.style.background = data.success ? '#f0fdf4' : '#fff5f5';
        result.style.border     = `1px solid ${data.success ? '#bbf7d0' : '#fecaca'}`;
        result.style.color      = data.success ? '#166534' : '#991b1b';
        result.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${data.message ?? (data.success ? 'Succès' : 'Erreur')}`;
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger';
        input.value  = '';
        btn.disabled = true;
    })
    .catch(() => {
        btn.innerHTML = '<i class="fa-solid fa-broom"></i> Purger';
        btn.disabled  = false;
    });
}
</script>
