{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-abonnements.blade.php
     Usage: @include('back.security.superadmin.partials.modal-abonnements')
     Description: Gestion des abonnements et plans actifs
════════════════════════════════════════════════════ --}}

<div id="modal-abonnements" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(760px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-rotate" style="color:#8b5cf6;"></i>
                Abonnements & Plans actifs
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-abonnements')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                $planGroups = \App\Models\User::selectRaw('plan, count(*) as total, sum(credits) as credits_total, sum(case when is_active=1 then 1 else 0 end) as actifs')
                    ->groupBy('plan')
                    ->get()
                    ->keyBy('plan');

                $free       = $planGroups['free']       ?? null;
                $premium    = $planGroups['premium']    ?? null;
                $enterprise = $planGroups['enterprise'] ?? null;

                $recentPremium = \App\Models\User::whereIn('plan',['premium','enterprise'])
                    ->orderByDesc('updated_at')->take(15)->get();
            @endphp

            {{-- Plans overview --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">

                {{-- Free --}}
                <div style="border:2px solid #e2e8f0;border-radius:16px;padding:16px;position:relative;overflow:hidden;">
                    <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#94a3b8,#cbd5e1);"></div>
                    <div style="font-size:13px;font-weight:900;color:#475569;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-user"></i> Free
                    </div>
                    <div style="font-size:28px;font-weight:900;color:#0f172a;">{{ $free->total ?? 0 }}</div>
                    <div style="font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-top:2px;">utilisateurs</div>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;font-size:11px;">
                        <span style="color:#64748b;">Actifs</span>
                        <span style="font-weight:800;color:#0f172a;">{{ $free->actifs ?? 0 }}</span>
                    </div>
                </div>

                {{-- Premium --}}
                <div style="border:2px solid rgba(139,92,246,.3);border-radius:16px;padding:16px;position:relative;overflow:hidden;background:linear-gradient(135deg,white,#faf5ff);">
                    <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#8b5cf6,#c4b5fd);"></div>
                    <div style="font-size:13px;font-weight:900;color:#7c3aed;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-bolt"></i> Premium
                    </div>
                    <div style="font-size:28px;font-weight:900;color:#0f172a;">{{ $premium->total ?? 0 }}</div>
                    <div style="font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-top:2px;">utilisateurs</div>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(139,92,246,.1);display:flex;justify-content:space-between;font-size:11px;">
                        <span style="color:#64748b;">Crédits moy.</span>
                        <span style="font-weight:800;color:#8b5cf6;">{{ $premium && $premium->total > 0 ? round($premium->credits_total / $premium->total) : 0 }}</span>
                    </div>
                </div>

                {{-- Enterprise --}}
                <div style="border:2px solid rgba(245,158,11,.3);border-radius:16px;padding:16px;position:relative;overflow:hidden;background:linear-gradient(135deg,white,#fffbeb);">
                    <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#f59e0b,#fcd34d);"></div>
                    <div style="font-size:13px;font-weight:900;color:#d97706;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-star"></i> Enterprise
                    </div>
                    <div style="font-size:28px;font-weight:900;color:#0f172a;">{{ $enterprise->total ?? 0 }}</div>
                    <div style="font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-top:2px;">utilisateurs</div>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(245,158,11,.1);display:flex;justify-content:space-between;font-size:11px;">
                        <span style="color:#64748b;">Crédits moy.</span>
                        <span style="font-weight:800;color:#f59e0b;">{{ $enterprise && $enterprise->total > 0 ? round($enterprise->credits_total / $enterprise->total) : 0 }}</span>
                    </div>
                </div>

            </div>

            {{-- Actions rapides plans --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">
                    <i class="fa-solid fa-bolt" style="color:#f59e0b;margin-right:4px;"></i>
                    Actions rapides
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="openModal('modal-bulk-plan');closeModal('modal-abonnements');">
                        <i class="fa-solid fa-layer-group"></i> Changer plans en masse
                    </button>
                    <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="openModal('modal-bulk-credits');closeModal('modal-abonnements');">
                        <i class="fa-solid fa-coins"></i> Crédits en masse
                    </button>
                    <a href="{{ route('admin.superadmin.users.index') }}?plan=free" class="sa-btn sa-btn-soft sa-btn-sm">
                        <i class="fa-solid fa-user"></i> Voir utilisateurs Free
                    </a>
                    <a href="{{ route('admin.superadmin.users.index') }}?plan=premium" class="sa-btn sa-btn-soft sa-btn-sm">
                        <i class="fa-solid fa-bolt" style="color:#8b5cf6;"></i> Voir Premium
                    </a>
                </div>
            </div>

            {{-- Liste abonnés payants --}}
            <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
                Abonnés payants récents ({{ $recentPremium->count() }})
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:12px;min-width:500px;">
                    <thead><tr>
                        @foreach(['Utilisateur','Plan','Crédits','Actif','Depuis','Actions'] as $h)
                        <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">{{ $h }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse($recentPremium as $u)
                        <tr>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $u->name }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $u->email }}</div>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                <span class="sa-badge {{ $u->plan === 'enterprise' ? 'sa-badge-gold' : 'sa-badge-purple' }}">
                                    {{ $u->plan }}
                                </span>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-weight:900;color:#3b82f6;">
                                {{ number_format($u->credits, 0, ',', ' ') }}
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                @if($u->is_active)
                                    <span class="sa-badge sa-badge-green"><i class="fa-solid fa-circle" style="font-size:7px;"></i></span>
                                @else
                                    <span class="sa-badge sa-badge-red"><i class="fa-solid fa-circle" style="font-size:7px;"></i></span>
                                @endif
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;">
                                {{ optional($u->created_at)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                <a href="{{ route('admin.security.users.edit', $u) }}"
                                   class="sa-btn sa-btn-soft sa-btn-sm">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">Aucun abonné payant</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div class="sa-modal-footer">
            <a href="{{ route('admin.superadmin.payments.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-credit-card"></i> Page paiements
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-abonnements')">Fermer</button>
        </div>
    </div>
</div>
