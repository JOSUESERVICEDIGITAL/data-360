{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-admins.blade.php
     Usage: @include('back.security.superadmin.partials.modal-admins')
     Description: Gestion rapide des admins et superadmins
════════════════════════════════════════════════════ --}}

<div id="modal-admins" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(720px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-user-shield" style="color:#f59e0b;"></i>
                Gestion des Admins & Superadmins
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-admins')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            {{-- Tabs --}}
            <div style="display:flex;gap:4px;background:#f8fafc;border-radius:12px;padding:4px;margin-bottom:16px;">
                <button class="perf-tab active" onclick="switchAdminTab('list')" id="admin-tab-list">
                    <i class="fa-solid fa-list"></i> Liste des admins
                </button>
                <button class="perf-tab" onclick="switchAdminTab('promote')" id="admin-tab-promote">
                    <i class="fa-solid fa-user-plus"></i> Promouvoir
                </button>
                <button class="perf-tab" onclick="switchAdminTab('superadmin')" id="admin-tab-superadmin">
                    <i class="fa-solid fa-crown"></i> Superadmins
                </button>
            </div>

            {{-- Tab: Liste admins --}}
            <div id="admin-content-list" class="admin-tab-content">
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:12px;min-width:500px;">
                        <thead>
                            <tr>
                                <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;">Utilisateur</th>
                                <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;">Rôle</th>
                                <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;">Statut</th>
                                <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;">Dernière co.</th>
                                <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:right;padding:10px 12px;border-bottom:1px solid #e2e8f0;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $admins = \App\Models\User::where('is_admin', true)->orderByDesc('is_superadmin')->get(); @endphp
                            @forelse($admins as $admin)
                            @php $isSuperAdm = $admin->isSuperAdmin(); @endphp
                            <tr>
                                <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;color:white;font-weight:900;flex-shrink:0;background:{{ $isSuperAdm ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'linear-gradient(135deg,#0053b3,#1d4ed8)' }};">
                                            {{ $isSuperAdm ? '👑' : strtoupper(substr($admin->name,0,1)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:800;color:#0f172a;">{{ $admin->name }}</div>
                                            <div style="font-size:10px;color:#94a3b8;">{{ $admin->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9;">
                                    @if($isSuperAdm)
                                        <span class="sa-badge sa-badge-gold"><i class="fa-solid fa-crown"></i> Superadmin</span>
                                    @else
                                        <span class="sa-badge sa-badge-blue"><i class="fa-solid fa-user-shield"></i> Admin</span>
                                    @endif
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9;">
                                    @if($admin->is_active)
                                        <span class="sa-badge sa-badge-green">Actif</span>
                                    @else
                                        <span class="sa-badge sa-badge-red">Suspendu</span>
                                    @endif
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;">
                                    {{ optional($admin->last_login_at)->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9;text-align:right;">
                                    <div style="display:flex;gap:5px;justify-content:flex-end;flex-wrap:wrap;">
                                        <a href="{{ route('admin.security.users.edit', $admin) }}"
                                           class="sa-btn sa-btn-soft sa-btn-sm">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        @if(!$isSuperAdm && $admin->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.superadmin.users.make-superadmin', $admin) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="sa-btn sa-btn-sm" style="background:#fffbeb;color:#92400e;border:1px solid rgba(245,158,11,.3);"
                                                        onclick="return confirm('Promouvoir {{ addslashes($admin->name) }} en superadmin ?')">
                                                    <i class="fa-solid fa-crown"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.security.users.destroy', $admin) }}" style="display:inline;"
                                                  onsubmit="return confirm('Retirer les droits admin de {{ addslashes($admin->name) }} ?')">
                                                @csrf @method('DELETE')
                                                {{-- Bouton retirer admin via controller --}}
                                            </form>
                                        @endif
                                        @if($isSuperAdm && $admin->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.superadmin.users.remove-superadmin', $admin) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="sa-btn sa-btn-danger sa-btn-sm"
                                                        onclick="return confirm('Rétrograder {{ addslashes($admin->name) }} ?')">
                                                    <i class="fa-solid fa-user-minus"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">Aucun admin</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab: Promouvoir --}}
            <div id="admin-content-promote" class="admin-tab-content" style="display:none;">
                <p style="color:#64748b;font-size:13px;margin-bottom:14px;">Recherchez un utilisateur pour lui attribuer les droits admin.</p>
                <div style="display:flex;gap:8px;margin-bottom:14px;">
                    <input type="text" id="promote-search" placeholder="Rechercher par email ou nom…"
                           style="flex:1;border:1.5px solid #e2e8f0;border-radius:10px;padding:9px 12px;font-size:13px;outline:none;"
                           oninput="searchForPromotion()">
                    <button class="sa-btn sa-btn-primary sa-btn-sm" onclick="searchForPromotion()">
                        <i class="fa-solid fa-search"></i> Rechercher
                    </button>
                </div>
                <div id="promote-results"></div>
            </div>

            {{-- Tab: Superadmins --}}
            <div id="admin-content-superadmin" class="admin-tab-content" style="display:none;">
                <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1px solid rgba(245,158,11,.3);border-radius:12px;padding:14px;margin-bottom:14px;display:flex;gap:10px;font-size:12px;color:#92400e;line-height:1.5;">
                    <i class="fa-solid fa-crown" style="font-size:16px;flex-shrink:0;margin-top:1px;"></i>
                    <div>Les <strong>superadmins</strong> ont un accès total à l'application — ils peuvent modifier tous les utilisateurs, purger la base de données et activer le mode maintenance. Accordez ce rôle avec prudence.</div>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead><tr>
                            <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:10px 12px;border-bottom:1px solid rgba(245,158,11,.2);">Superadmin</th>
                            <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:10px 12px;border-bottom:1px solid rgba(245,158,11,.2);">Email</th>
                            <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:10px 12px;border-bottom:1px solid rgba(245,158,11,.2);">Dernière connexion</th>
                            <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:right;padding:10px 12px;border-bottom:1px solid rgba(245,158,11,.2);">Action</th>
                        </tr></thead>
                        <tbody>
                            @php $superadmins = \App\Models\User::where('is_superadmin', true)->get(); @endphp
                            @forelse($superadmins as $sa)
                            <tr>
                                <td style="padding:10px 12px;border-bottom:1px solid #fef3c7;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;font-size:12px;">👑</div>
                                        <span style="font-weight:800;color:#0f172a;">{{ $sa->name }}</span>
                                    </div>
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid #fef3c7;color:#64748b;">{{ $sa->email }}</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #fef3c7;font-size:11px;color:#64748b;">{{ optional($sa->last_login_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #fef3c7;text-align:right;">
                                    @if($sa->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.superadmin.users.remove-superadmin', $sa) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="sa-btn sa-btn-danger sa-btn-sm"
                                                    onclick="return confirm('Rétrograder {{ addslashes($sa->name) }} ?')">
                                                <i class="fa-solid fa-user-minus"></i> Rétrograder
                                            </button>
                                        </form>
                                    @else
                                        <span style="font-size:11px;color:#94a3b8;font-style:italic;">Vous-même</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:#94a3b8;">Aucun superadmin</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="sa-modal-footer">
            <a href="{{ route('admin.superadmin.users.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-users"></i> Gestion complète
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-admins')">Fermer</button>
        </div>
    </div>
</div>

<script>
function switchAdminTab(tab) {
    ['list','promote','superadmin'].forEach(t => {
        document.getElementById('admin-tab-' + t)?.classList.toggle('active', t === tab);
        const content = document.getElementById('admin-content-' + t);
        if (content) content.style.display = t === tab ? 'block' : 'none';
    });
}

function searchForPromotion() {
    const q       = document.getElementById('promote-search')?.value?.trim();
    const results = document.getElementById('promote-results');

    if (!q || q.length < 2) {
        results.innerHTML = '<p style="color:#94a3b8;font-size:12px;">Saisissez au moins 2 caractères.</p>';
        return;
    }

    results.innerHTML = '<div style="text-align:center;padding:14px;color:#94a3b8;font-size:12px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Recherche…</div>';

    fetch(`{{ route('admin.superadmin.users.index') }}?q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(() => {
        results.innerHTML = `
            <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px;font-size:12px;color:#1e40af;">
                <i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>
                Accédez à la gestion complète pour promouvoir un utilisateur spécifique.
                <a href="{{ route('admin.superadmin.users.index') }}?q=${encodeURIComponent(q)}" class="sa-btn sa-btn-primary sa-btn-sm" style="margin-left:8px;">
                    Rechercher dans la liste
                </a>
            </div>`;
    });
}
</script>
