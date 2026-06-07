{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-connexions.blade.php
     Usage: @include('back.security.superadmin.partials.modal-connexions')
     Données: $recentLogins (collection User)
════════════════════════════════════════════════════ --}}

<div id="modal-connexions" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(820px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-right-to-bracket" style="color:#06b6d4;"></i>
                Historique des connexions
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-connexions')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Légende --}}
            <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                <span style="font-size:12px;font-weight:700;color:#64748b;">Légende :</span>
                <span class="sa-badge sa-badge-green"><i class="fa-solid fa-circle" style="font-size:7px;"></i> En ligne (< 30 min)</span>
                <span class="sa-badge sa-badge-gray"><i class="fa-regular fa-circle" style="font-size:7px;"></i> Hors ligne</span>
                <span class="sa-badge sa-badge-gold"><i class="fa-solid fa-crown"></i> Superadmin</span>
                <span class="sa-badge sa-badge-blue"><i class="fa-solid fa-user-shield"></i> Admin</span>
            </div>

            {{-- Filtre rapide --}}
            <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="filterConnexions('all')" id="filter-all" style="border:1.5px solid #0053b3;color:#0053b3;">
                    Tous
                </button>
                <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="filterConnexions('online')" id="filter-online">
                    <i class="fa-solid fa-circle" style="font-size:7px;color:#10b981;"></i> En ligne
                </button>
                <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="filterConnexions('offline')" id="filter-offline">
                    <i class="fa-regular fa-circle" style="font-size:7px;"></i> Hors ligne
                </button>
                <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="filterConnexions('admin')" id="filter-admin">
                    <i class="fa-solid fa-user-shield"></i> Admins
                </button>
            </div>

            {{-- Compteur dynamique --}}
            <div style="font-size:12px;color:#64748b;margin-bottom:10px;">
                <span id="connexions-count">{{ $recentLogins->count() }}</span> connexion(s) affichée(s)
                — <span style="color:#10b981;font-weight:700;" id="online-count">{{ $recentLogins->filter(fn($u) => $u->last_login_at && $u->last_login_at->diffInMinutes(now()) < 30)->count() }}</span> en ligne
            </div>

            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table class="sa-mini-table" id="connexions-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Rôle</th>
                            <th>Plan</th>
                            <th>Dernière connexion</th>
                            <th>IP</th>
                            <th>Durée depuis</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogins as $u)
                        @php
                            $isOnline   = $u->last_login_at && $u->last_login_at->diffInMinutes(now()) < 30;
                            $isSuperAdm = $u->isSuperAdmin();
                            $initials   = collect(explode(' ', trim($u->name ?? '')))->filter()->take(2)->map(fn($p) => mb_substr($p,0,1))->implode('');
                        @endphp
                        <tr class="connexion-row"
                            data-online="{{ $isOnline ? '1' : '0' }}"
                            data-admin="{{ $u->is_admin ? '1' : '0' }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:11px;color:white;flex-shrink:0;text-transform:uppercase;background:{{ $isSuperAdm ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'linear-gradient(135deg,#0053b3,#1d4ed8)' }};">
                                        {{ $isSuperAdm ? '👑' : ($initials ?: 'U') }}
                                    </div>
                                    <div>
                                        <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $u->name }}</div>
                                        <div style="font-size:10px;color:#94a3b8;">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($isSuperAdm)
                                    <span class="sa-badge sa-badge-gold"><i class="fa-solid fa-crown"></i> Super</span>
                                @elseif($u->is_admin)
                                    <span class="sa-badge sa-badge-blue"><i class="fa-solid fa-user-shield"></i> Admin</span>
                                @else
                                    <span class="sa-badge sa-badge-gray">User</span>
                                @endif
                            </td>
                            <td>
                                <span class="sa-badge {{ in_array($u->plan,['premium','enterprise']) ? 'sa-badge-purple' : 'sa-badge-gray' }}">
                                    {{ $u->plan ?? 'free' }}
                                </span>
                            </td>
                            <td style="font-size:11px;">
                                {{ optional($u->last_login_at)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td>
                                <code style="background:#f1f5f9;padding:3px 6px;border-radius:6px;font-size:10px;color:#334155;">
                                    {{ $u->last_login_ip ?? '—' }}
                                </code>
                            </td>
                            <td style="font-size:11px;color:#64748b;">
                                {{ optional($u->last_login_at)->diffForHumans() ?? '—' }}
                            </td>
                            <td>
                                @if($isOnline)
                                    <span class="sa-badge sa-badge-green">
                                        <i class="fa-solid fa-circle" style="font-size:7px;animation:pulse 2s infinite;"></i>
                                        En ligne
                                    </span>
                                @else
                                    <span class="sa-badge sa-badge-gray">
                                        <i class="fa-regular fa-circle" style="font-size:7px;"></i>
                                        Hors ligne
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;font-size:13px;">
                                <i class="fa-solid fa-clock" style="display:block;font-size:24px;margin-bottom:8px;color:#cbd5e1;"></i>
                                Aucune connexion enregistrée
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sa-modal-footer">
            <a href="{{ route('admin.superadmin.users.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-users"></i> Gérer les comptes
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-connexions')">
                Fermer
            </button>
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>

<script>
function filterConnexions(type) {
    // Highlight bouton actif
    ['all','online','offline','admin'].forEach(t => {
        const btn = document.getElementById('filter-' + t);
        if (btn) {
            btn.style.borderColor = t === type ? '#0053b3' : '';
            btn.style.color       = t === type ? '#0053b3' : '';
        }
    });

    const rows = document.querySelectorAll('.connexion-row');
    let visible = 0;

    rows.forEach(row => {
        const online = row.dataset.online === '1';
        const admin  = row.dataset.admin  === '1';
        let show = false;

        if (type === 'all')     show = true;
        if (type === 'online')  show = online;
        if (type === 'offline') show = !online;
        if (type === 'admin')   show = admin;

        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const cnt = document.getElementById('connexions-count');
    if (cnt) cnt.textContent = visible;
}
</script>
