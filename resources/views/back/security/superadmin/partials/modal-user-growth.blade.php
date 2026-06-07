{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-user-growth.blade.php
     Usage: @include('back.security.superadmin.partials.modal-user-growth')
     Description: Croissance utilisateurs + graphique 6 mois
════════════════════════════════════════════════════ --}}

<div id="modal-user-growth" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(720px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-chart-line" style="color:#3b82f6;"></i>
                Croissance & Statistiques utilisateurs
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-user-growth')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                // Stats globales
                $totalUsers     = \App\Models\User::count();
                $activeUsers    = \App\Models\User::where('is_active', true)->count();
                $verifiedUsers  = \App\Models\User::whereNotNull('email_verified_at')->count();
                $newToday       = \App\Models\User::whereDate('created_at', today())->count();
                $newWeek        = \App\Models\User::where('created_at', '>=', now()->subDays(7))->count();
                $newMonth       = \App\Models\User::where('created_at', '>=', now()->subDays(30))->count();

                // Plans
                $planFree       = \App\Models\User::where('plan','free')->count();
                $planPremium    = \App\Models\User::where('plan','premium')->count();
                $planEnterprise = \App\Models\User::where('plan','enterprise')->count();

                // Croissance 6 mois
                $growthData = [];
                for ($i = 5; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    $growthData[] = [
                        'month'   => $date->format('M Y'),
                        'short'   => $date->format('M'),
                        'users'   => \App\Models\User::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                        'premium' => \App\Models\User::whereIn('plan',['premium','enterprise'])->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                    ];
                }
                $maxGrowth = max(1, collect($growthData)->max('users'));
            @endphp

            {{-- KPIs rapides --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px;">
                @foreach([
                    ['Aujourd\'hui', $newToday,  '#10b981', 'fa-calendar-day'],
                    ['Cette semaine',$newWeek,   '#3b82f6', 'fa-calendar-week'],
                    ['Ce mois',      $newMonth,  '#8b5cf6', 'fa-calendar'],
                ] as $kpi)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:14px;padding:14px;text-align:center;">
                    <i class="fa-solid {{ $kpi[3] }}" style="color:{{ $kpi[2] }};font-size:18px;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:22px;font-weight:900;color:#0f172a;">+{{ $kpi[1] }}</div>
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:3px;">{{ $kpi[0] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Graphique 6 mois --}}
            <div style="background:white;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
                    Inscriptions — 6 derniers mois
                </div>
                <div style="display:flex;align-items:flex-end;gap:8px;height:100px;">
                    @foreach($growthData as $month)
                    @php $barHeight = $month['users'] > 0 ? max(8, round($month['users'] / $maxGrowth * 100)) : 4; @endphp
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                        <div style="font-size:10px;font-weight:800;color:#64748b;">{{ $month['users'] }}</div>
                        <div style="width:100%;height:{{ $barHeight }}px;background:linear-gradient(180deg,#3b82f6,#1d4ed8);border-radius:4px 4px 0 0;transition:height .5s;min-height:4px;" title="{{ $month['users'] }} inscription(s)"></div>
                        <div style="font-size:9px;color:#94a3b8;font-weight:700;white-space:nowrap;">{{ $month['short'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Distribution plans --}}
            <div style="background:white;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">Distribution des plans</div>
                @php $tp = max(1, $totalUsers); @endphp
                @foreach([
                    ['Free',       $planFree,       '#94a3b8', '#f1f5f9'],
                    ['Premium',    $planPremium,    '#8b5cf6', '#ede9fe'],
                    ['Enterprise', $planEnterprise, '#f59e0b', '#fffbeb'],
                ] as $plan)
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <div style="width:80px;font-size:12px;font-weight:700;color:#334155;flex-shrink:0;">{{ $plan[0] }}</div>
                    <div style="flex:1;background:#f1f5f9;border-radius:999px;height:10px;overflow:hidden;">
                        <div style="height:100%;border-radius:999px;background:{{ $plan[2] }};width:{{ round($plan[1]/$tp*100) }}%;transition:width 1s;"></div>
                    </div>
                    <div style="width:60px;display:flex;justify-content:space-between;flex-shrink:0;">
                        <span style="font-size:12px;font-weight:900;color:#0f172a;">{{ $plan[1] }}</span>
                        <span style="font-size:10px;color:#94a3b8;font-weight:700;">{{ round($plan[1]/$tp*100) }}%</span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Santé des comptes --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                @foreach([
                    ['Comptes actifs',  $activeUsers,   $totalUsers,  '#10b981'],
                    ['Emails vérifiés', $verifiedUsers, $totalUsers,  '#3b82f6'],
                    ['Payants',         $planPremium + $planEnterprise, $totalUsers, '#8b5cf6'],
                ] as $health)
                @php $pct = $health[2] > 0 ? round($health[1]/$health[2]*100) : 0; @endphp
                <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;">
                    <div style="position:relative;width:56px;height:56px;margin:0 auto 8px;">
                        <svg viewBox="0 0 36 36" style="width:56px;height:56px;transform:rotate(-90deg);">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="{{ $health[3] }}" stroke-width="3"
                                    stroke-dasharray="{{ $pct }} {{ 100 - $pct }}" stroke-linecap="round"/>
                        </svg>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:#0f172a;">{{ $pct }}%</div>
                    </div>
                    <div style="font-size:12px;font-weight:800;color:#0f172a;">{{ number_format($health[1],0,',',' ') }}</div>
                    <div style="font-size:9px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-top:2px;">{{ $health[0] }}</div>
                </div>
                @endforeach
            </div>

        </div>

        <div class="sa-modal-footer">
            <a href="{{ route('admin.superadmin.users.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-users"></i> Gérer les utilisateurs
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-user-growth')">Fermer</button>
        </div>
    </div>
</div>
