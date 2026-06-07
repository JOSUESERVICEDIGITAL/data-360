{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-credits-hist.blade.php
     Usage: @include('back.security.superadmin.partials.modal-credits-hist')
     Description: Historique des mouvements de crédits
════════════════════════════════════════════════════ --}}

<div id="modal-credits-hist" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(800px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-coins" style="color:#f59e0b;"></i>
                Historique des crédits
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-credits-hist')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                $totalCredits  = \App\Models\User::sum('credits');
                $avgCredits    = \App\Models\User::avg('credits');
                $maxCredits    = \App\Models\User::max('credits');
                $usersWithCred = \App\Models\User::where('credits', '>', 0)->count();
                $usersZeroCred = \App\Models\User::where('credits', '<=', 0)->count();

                // Top 10 utilisateurs avec le plus de crédits
                $topCredits = \App\Models\User::orderByDesc('credits')->take(10)->get();

                // Distribution crédits
                $distrib = [
                    '0'        => \App\Models\User::where('credits', 0)->count(),
                    '1-100'    => \App\Models\User::whereBetween('credits', [1, 100])->count(),
                    '101-500'  => \App\Models\User::whereBetween('credits', [101, 500])->count(),
                    '501-1000' => \App\Models\User::whereBetween('credits', [501, 1000])->count(),
                    '1000+'    => \App\Models\User::where('credits', '>', 1000)->count(),
                ];
                $maxDistrib = max(1, max($distrib));
            @endphp

            {{-- KPIs --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px;">
                @foreach([
                    ['Total crédits',  number_format($totalCredits,0,',',' '), '#f59e0b', 'fa-coins'],
                    ['Moyenne / user', round($avgCredits),                     '#3b82f6', 'fa-chart-bar'],
                    ['Avec crédits',   $usersWithCred,                        '#10b981', 'fa-user-check'],
                    ['Sans crédits',   $usersZeroCred,                        '#ef4444', 'fa-user-slash'],
                ] as $kpi)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:14px;padding:12px;text-align:center;">
                    <i class="fa-solid {{ $kpi[3] }}" style="color:{{ $kpi[2] }};font-size:16px;display:block;margin-bottom:5px;"></i>
                    <div style="font-size:clamp(14px,2vw,20px);font-weight:900;color:#0f172a;">{{ $kpi[1] }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:2px;">{{ $kpi[0] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Distribution --}}
            <div style="background:white;border:1px solid #e2e8f0;border-radius:14px;padding:14px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">Distribution des crédits</div>
                @foreach($distrib as $range => $count)
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:70px;font-size:11px;font-weight:700;color:#334155;flex-shrink:0;">{{ $range }}</div>
                    <div style="flex:1;background:#f1f5f9;border-radius:999px;height:9px;overflow:hidden;">
                        <div style="height:100%;border-radius:999px;background:linear-gradient(90deg,#f59e0b,#fcd34d);width:{{ round($count/$maxDistrib*100) }}%;transition:width 1s;"></div>
                    </div>
                    <div style="width:40px;text-align:right;font-size:12px;font-weight:900;color:#0f172a;">{{ $count }}</div>
                </div>
                @endforeach
            </div>

            {{-- Top 10 --}}
            <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
                Top 10 — Plus de crédits
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:12px;min-width:500px;">
                    <thead><tr>
                        <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #fef3c7;">#</th>
                        <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #fef3c7;">Utilisateur</th>
                        <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #fef3c7;">Plan</th>
                        <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #fef3c7;">Crédits</th>
                        <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #fef3c7;">Barre</th>
                        <th style="background:#fffbeb;color:#92400e;font-size:10px;text-transform:uppercase;font-weight:800;text-align:right;padding:9px 12px;border-bottom:1px solid #fef3c7;">Action</th>
                    </tr></thead>
                    <tbody>
                        @foreach($topCredits as $idx => $u)
                        <tr>
                            <td style="padding:9px 12px;border-bottom:1px solid #fef9e7;color:#94a3b8;font-weight:900;">
                                @if($idx === 0) 🥇
                                @elseif($idx === 1) 🥈
                                @elseif($idx === 2) 🥉
                                @else {{ $idx + 1 }}
                                @endif
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #fef9e7;">
                                <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $u->name }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $u->email }}</div>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #fef9e7;">
                                <span class="sa-badge {{ in_array($u->plan,['premium','enterprise']) ? 'sa-badge-purple' : 'sa-badge-gray' }}">{{ $u->plan }}</span>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #fef9e7;font-size:16px;font-weight:900;color:#f59e0b;">
                                {{ number_format($u->credits, 0, ',', ' ') }}
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #fef9e7;">
                                <div style="background:#fef3c7;border-radius:999px;height:7px;width:120px;overflow:hidden;">
                                    <div style="height:100%;border-radius:999px;background:linear-gradient(90deg,#f59e0b,#fcd34d);width:{{ $maxCredits > 0 ? round($u->credits/$maxCredits*100) : 0 }}%;transition:width 1s;"></div>
                                </div>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #fef9e7;text-align:right;">
                                <a href="{{ route('admin.security.users.edit', $u) }}"
                                   class="sa-btn sa-btn-soft sa-btn-sm">
                                    <i class="fa-solid fa-coins"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-gold sa-btn-sm" onclick="openModal('modal-bulk-credits');closeModal('modal-credits-hist');">
                <i class="fa-solid fa-coins"></i> Crédits en masse
            </button>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-credits-hist')">Fermer</button>
        </div>
    </div>
</div>
