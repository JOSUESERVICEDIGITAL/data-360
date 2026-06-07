{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-transactions.blade.php
     Usage: @include('back.security.superadmin.partials.modal-transactions')
     Description: Transactions & paiements Stripe
════════════════════════════════════════════════════ --}}

<div id="modal-transactions" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(820px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-receipt" style="color:#10b981;"></i>
                Transactions & Paiements Stripe
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-transactions')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                try {
                    $txTotal   = \Illuminate\Support\Facades\DB::table('credit_transactions')->count();
                    $txAmount  = \Illuminate\Support\Facades\DB::table('credit_transactions')->sum('amount') ?? 0;
                    $txMonth   = \Illuminate\Support\Facades\DB::table('credit_transactions')
                                    ->where('created_at', '>=', now()->startOfMonth())->sum('amount') ?? 0;
                    $txRecent  = \Illuminate\Support\Facades\DB::table('credit_transactions')
                                    ->latest()->take(20)->get();
                    $txExists  = true;
                } catch (\Throwable) {
                    $txTotal = $txAmount = $txMonth = 0;
                    $txRecent = collect();
                    $txExists = false;
                }
            @endphp

            {{-- KPIs --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px;">
                @foreach([
                    ['Transactions',   $txTotal,                              '#3b82f6', 'fa-receipt'],
                    ['Revenus totaux', number_format($txAmount/100,2,',',' ').' €', '#10b981', 'fa-euro-sign'],
                    ['Ce mois',        number_format($txMonth/100,2,',',' ').' €',  '#f59e0b', 'fa-calendar-check'],
                    ['Env. Stripe',    str_contains(config('services.stripe.key',''),'test') ? 'TEST' : 'PROD', str_contains(config('services.stripe.key',''),'test') ? '#f97316' : '#10b981', 'fa-credit-card'],
                ] as $kpi)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:14px;padding:14px;text-align:center;position:relative;overflow:hidden;">
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:{{ $kpi[2] }};opacity:.5;"></div>
                    <i class="fa-solid {{ $kpi[3] }}" style="color:{{ $kpi[2] }};font-size:18px;display:block;margin-bottom:6px;"></i>
                    <div style="font-size:clamp(13px,2vw,18px);font-weight:900;color:#0f172a;">{{ $kpi[1] }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:3px;">{{ $kpi[0] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Graphique revenus 6 mois --}}
            @php
                $revenueData = [];
                for ($i = 5; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    try {
                        $amount = \Illuminate\Support\Facades\DB::table('credit_transactions')
                            ->whereYear('created_at', $date->year)
                            ->whereMonth('created_at', $date->month)
                            ->sum('amount') ?? 0;
                    } catch (\Throwable) { $amount = 0; }
                    $revenueData[] = ['month' => $date->format('M'), 'amount' => $amount / 100];
                }
                $maxRevenue = max(1, collect($revenueData)->max('amount'));
            @endphp

            <div style="background:white;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">
                    Revenus — 6 derniers mois (€)
                </div>
                <div style="display:flex;align-items:flex-end;gap:6px;height:80px;">
                    @foreach($revenueData as $d)
                    @php $h = $d['amount'] > 0 ? max(6, round($d['amount']/$maxRevenue*80)) : 4; @endphp
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;">
                        <div style="font-size:9px;font-weight:700;color:#64748b;">{{ $d['amount'] > 0 ? number_format($d['amount'],0) : '—' }}</div>
                        <div style="width:100%;height:{{ $h }}px;background:linear-gradient(180deg,#10b981,#059669);border-radius:4px 4px 0 0;min-height:4px;"></div>
                        <div style="font-size:9px;color:#94a3b8;font-weight:700;">{{ $d['month'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Table transactions --}}
            @if(!$txExists)
            <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px;font-size:13px;color:#1e40af;line-height:1.5;">
                <i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>
                La table <code>credit_transactions</code> n'existe pas encore. Créez-la pour afficher les transactions réelles.
                <div style="margin-top:8px;font-size:11px;color:#3b82f6;">
                    Colonnes suggérées : <code>id, user_id, type, amount, stripe_id, status, description, created_at</code>
                </div>
            </div>
            @else
            <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
                Transactions récentes
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:12px;min-width:580px;">
                    <thead><tr>
                        @foreach(['#','Utilisateur','Type','Montant','Stripe ID','Date'] as $h)
                        <th style="background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;font-weight:800;text-align:left;padding:9px 12px;border-bottom:1px solid #e2e8f0;">{{ $h }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse($txRecent as $tx)
                        <tr>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;color:#94a3b8;font-size:11px;">#{{ $tx->id }}</td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-weight:700;">{{ $tx->user_id ?? '—' }}</td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                <span class="sa-badge sa-badge-blue">{{ $tx->type ?? 'credit' }}</span>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-weight:900;color:#10b981;">
                                {{ number_format(($tx->amount ?? 0)/100, 2, ',', ' ') }} €
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;">
                                <code style="font-size:10px;background:#f1f5f9;padding:2px 6px;border-radius:5px;">{{ substr($tx->stripe_id ?? '—', 0, 20) }}…</code>
                            </td>
                            <td style="padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:11px;color:#64748b;">
                                {{ \Carbon\Carbon::parse($tx->created_at ?? now())->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">Aucune transaction</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

        </div>

        <div class="sa-modal-footer">
            <a href="https://dashboard.stripe.com" target="_blank" class="sa-btn sa-btn-sm" style="background:#635bff;color:white;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Dashboard Stripe
            </a>
            <a href="{{ route('admin.superadmin.payments.index') }}" class="sa-btn sa-btn-primary sa-btn-sm">
                <i class="fa-solid fa-receipt"></i> Page paiements
            </a>
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-transactions')">Fermer</button>
        </div>
    </div>
</div>
