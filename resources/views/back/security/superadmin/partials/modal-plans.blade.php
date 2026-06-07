{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-plans.blade.php
     Usage: @include('back.security.superadmin.partials.modal-plans')
     Description: Gestion des plans d'abonnement
════════════════════════════════════════════════════ --}}

<div id="modal-plans" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(640px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-layer-group" style="color:#10b981;"></i>
                Gestion des plans
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-plans')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                $planCounts = \App\Models\User::selectRaw('plan, count(*) as cnt')->groupBy('plan')->pluck('cnt','plan');
                $free       = $planCounts['free']       ?? 0;
                $premium    = $planCounts['premium']    ?? 0;
                $enterprise = $planCounts['enterprise'] ?? 0;
                $total      = max(1, $free + $premium + $enterprise);
            @endphp

            {{-- Plans cards --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px;">

                <div style="border:2px solid #e2e8f0;border-radius:14px;padding:14px;text-align:center;">
                    <i class="fa-solid fa-user" style="color:#94a3b8;font-size:22px;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:22px;font-weight:900;color:#0f172a;">{{ $free }}</div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;">Free</div>
                    <div style="font-size:10px;color:#94a3b8;margin-top:3px;">{{ round($free/$total*100) }}%</div>
                    <a href="{{ route('admin.superadmin.users.index') }}?plan=free"
                       class="sa-btn sa-btn-soft sa-btn-sm" style="margin-top:10px;width:100%;justify-content:center;">
                        Voir <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div style="border:2px solid rgba(139,92,246,.3);border-radius:14px;padding:14px;text-align:center;background:linear-gradient(135deg,white,#faf5ff);">
                    <i class="fa-solid fa-bolt" style="color:#8b5cf6;font-size:22px;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:22px;font-weight:900;color:#0f172a;">{{ $premium }}</div>
                    <div style="font-size:11px;font-weight:700;color:#7c3aed;">Premium</div>
                    <div style="font-size:10px;color:#94a3b8;margin-top:3px;">{{ round($premium/$total*100) }}%</div>
                    <a href="{{ route('admin.superadmin.users.index') }}?plan=premium"
                       class="sa-btn sa-btn-sm" style="margin-top:10px;width:100%;justify-content:center;background:#ede9fe;color:#7c3aed;">
                        Voir <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div style="border:2px solid rgba(245,158,11,.3);border-radius:14px;padding:14px;text-align:center;background:linear-gradient(135deg,white,#fffbeb);">
                    <i class="fa-solid fa-star" style="color:#f59e0b;font-size:22px;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:22px;font-weight:900;color:#0f172a;">{{ $enterprise }}</div>
                    <div style="font-size:11px;font-weight:700;color:#d97706;">Enterprise</div>
                    <div style="font-size:10px;color:#94a3b8;margin-top:3px;">{{ round($enterprise/$total*100) }}%</div>
                    <a href="{{ route('admin.superadmin.users.index') }}?plan=enterprise"
                       class="sa-btn sa-btn-sm" style="margin-top:10px;width:100%;justify-content:center;background:#fffbeb;color:#d97706;">
                        Voir <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

            </div>

            {{-- Changement en masse --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:12px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">
                    <i class="fa-solid fa-bolt" style="color:#f59e0b;margin-right:4px;"></i>
                    Changement de plan en masse
                </div>
                <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:9px;padding:10px;margin-bottom:12px;font-size:12px;color:#991b1b;display:flex;gap:8px;">
                    <i class="fa-solid fa-triangle-exclamation" style="flex-shrink:0;margin-top:1px;"></i>
                    Action irréversible. Les superadmins sont exclus du changement en masse.
                </div>
                <form method="POST" action="{{ route('admin.superadmin.users.bulk-plan') }}"
                      style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;"
                      onsubmit="return confirm('Modifier les plans en masse ?')">
                    @csrf
                    <div style="flex:1;min-width:120px;">
                        <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:4px;">Depuis</label>
                        <select name="from_plan" style="width:100%;border:1.5px solid #e2e8f0;border-radius:9px;padding:8px 10px;font-size:12px;outline:none;">
                            <option value="all">Tous les plans</option>
                            <option value="free">Free</option>
                            <option value="premium">Premium</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div style="font-size:18px;color:#94a3b8;padding-bottom:8px;flex-shrink:0;">→</div>
                    <div style="flex:1;min-width:120px;">
                        <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:4px;">Vers</label>
                        <select name="to_plan" style="width:100%;border:1.5px solid #e2e8f0;border-radius:9px;padding:8px 10px;font-size:12px;outline:none;">
                            <option value="free">Free</option>
                            <option value="premium">Premium</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <button type="submit" class="sa-btn sa-btn-green sa-btn-sm">
                        <i class="fa-solid fa-check"></i> Appliquer
                    </button>
                </form>
            </div>

            {{-- Liens rapides --}}
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <div class="sa-option-card" style="flex:1;min-width:140px;" onclick="openModal('modal-abonnements');closeModal('modal-plans');">
                    <i class="fa-solid fa-rotate" style="color:#8b5cf6;font-size:16px;"></i>
                    <div class="opt-title">Abonnements</div>
                </div>
                <div class="sa-option-card" style="flex:1;min-width:140px;" onclick="openModal('modal-user-growth');closeModal('modal-plans');">
                    <i class="fa-solid fa-chart-pie" style="color:#3b82f6;font-size:16px;"></i>
                    <div class="opt-title">Statistiques</div>
                </div>
                <a href="{{ route('admin.superadmin.payments.index') }}" class="sa-option-card" style="flex:1;min-width:140px;">
                    <i class="fa-solid fa-credit-card" style="color:#10b981;font-size:16px;"></i>
                    <div class="opt-title">Paiements</div>
                </a>
            </div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-plans')">Fermer</button>
        </div>
    </div>
</div>
