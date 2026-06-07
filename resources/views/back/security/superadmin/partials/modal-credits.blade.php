{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-credits.blade.php
     Usage: @include('back.security.superadmin.partials.modal-credits')
     Description: Gestion rapide des crédits
════════════════════════════════════════════════════ --}}

<div id="modal-credits" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(620px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-coins" style="color:#8b5cf6;"></i>
                Gestion des crédits
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-credits')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                $totalCredits = \App\Models\User::sum('credits');
                $avgCredits   = round(\App\Models\User::avg('credits') ?? 0);
                $zeroCred     = \App\Models\User::where('credits', 0)->count();
                $richCred     = \App\Models\User::where('credits', '>', 500)->count();
            @endphp

            {{-- Stats --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:18px;">
                @foreach([
                    [number_format($totalCredits,0,',',' '), 'Total',      '#8b5cf6', 'fa-coins'],
                    [$avgCredits,                            'Moyenne',    '#3b82f6', 'fa-chart-bar'],
                    [$zeroCred,                              'Sans crédit','#ef4444', 'fa-battery-empty'],
                    [$richCred,                              '> 500',      '#10b981', 'fa-battery-full'],
                ] as $s)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;">
                    <i class="fa-solid {{ $s[3] }}" style="color:{{ $s[2] }};font-size:16px;display:block;margin-bottom:5px;"></i>
                    <div style="font-size:clamp(14px,2vw,20px);font-weight:900;color:#0f172a;">{{ $s[0] }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:2px;">{{ $s[1] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Options --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">

                <div class="sa-option-card" onclick="openModal('modal-bulk-credits');closeModal('modal-credits');">
                    <i class="fa-solid fa-users" style="color:#8b5cf6;"></i>
                    <div class="opt-title">Crédits en masse</div>
                    <div class="opt-desc">Ajouter, définir ou réinitialiser pour un groupe</div>
                </div>

                <div class="sa-option-card" onclick="openModal('modal-credits-hist');closeModal('modal-credits');">
                    <i class="fa-solid fa-clock-rotate-left" style="color:#f59e0b;"></i>
                    <div class="opt-title">Historique crédits</div>
                    <div class="opt-desc">Top 10 et distribution des crédits</div>
                </div>

                <a href="{{ route('admin.superadmin.users.index') }}" class="sa-option-card">
                    <i class="fa-solid fa-user-pen" style="color:#3b82f6;"></i>
                    <div class="opt-title">Par utilisateur</div>
                    <div class="opt-desc">Modifier les crédits d'un compte spécifique</div>
                </a>

                <div class="sa-option-card" onclick="openModal('modal-abonnements');closeModal('modal-credits');">
                    <i class="fa-solid fa-layer-group" style="color:#10b981;"></i>
                    <div class="opt-title">Par plan</div>
                    <div class="opt-desc">Voir les crédits par abonnement</div>
                </div>

            </div>

            {{-- Attribution rapide --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">
                    <i class="fa-solid fa-bolt" style="color:#f59e0b;margin-right:4px;"></i>
                    Attribution express
                </div>
                <form method="POST" action="{{ route('admin.superadmin.users.bulk-credits') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
                    @csrf
                    <div style="flex:1;min-width:120px;">
                        <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:4px;">Cible</label>
                        <select name="target" style="width:100%;border:1.5px solid #e2e8f0;border-radius:9px;padding:8px 10px;font-size:12px;outline:none;">
                            <option value="all">Tous</option>
                            <option value="free">Free</option>
                            <option value="premium">Premium</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div style="flex:1;min-width:100px;">
                        <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:4px;">Action</label>
                        <select name="action" style="width:100%;border:1.5px solid #e2e8f0;border-radius:9px;padding:8px 10px;font-size:12px;outline:none;">
                            <option value="add">Ajouter</option>
                            <option value="set">Définir à</option>
                            <option value="reset">Réinitialiser</option>
                        </select>
                    </div>
                    <div style="flex:1;min-width:80px;">
                        <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:4px;">Montant</label>
                        <input type="number" name="amount" value="50" min="0"
                               style="width:100%;border:1.5px solid #e2e8f0;border-radius:9px;padding:8px 10px;font-size:12px;outline:none;box-sizing:border-box;">
                    </div>
                    <button type="submit" class="sa-btn sa-btn-gold sa-btn-sm"
                            onclick="return confirm('Appliquer les crédits en masse ?')">
                        <i class="fa-solid fa-bolt"></i> Go
                    </button>
                </form>
            </div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-credits')">Fermer</button>
        </div>
    </div>
</div>
