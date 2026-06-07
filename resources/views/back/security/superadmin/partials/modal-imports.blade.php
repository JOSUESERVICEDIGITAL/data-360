{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-imports.blade.php
     Usage: @include('back.security.superadmin.partials.modal-imports')
     Description: Navigation rapide vers les imports CSV
════════════════════════════════════════════════════ --}}

<div id="modal-imports" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(600px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-solid fa-file-csv" style="color:#f97316;"></i>
                Imports CSV — Actions
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-imports')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            @php
                try {
                    $importTotal   = \App\Models\Back\CsvImport::count();
                    $importTermine = \App\Models\Back\CsvImport::where('statut','termine')->count();
                    $importErreur  = \App\Models\Back\CsvImport::where('statut','erreur')->count();
                    $importEnCours = \App\Models\Back\CsvImport::whereIn('statut',['en_attente','en_cours'])->count();
                    $importLignes  = \App\Models\Back\CsvImport::sum('total_lignes');
                } catch(\Throwable) {
                    $importTotal = $importTermine = $importErreur = $importEnCours = $importLignes = 0;
                }
            @endphp

            {{-- Stats rapides --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:18px;">
                @foreach([
                    [$importTotal,   'Total',     '#3b82f6', 'fa-file-csv'],
                    [$importTermine, 'Terminés',  '#10b981', 'fa-circle-check'],
                    [$importEnCours, 'En cours',  '#f59e0b', 'fa-spinner'],
                    [$importErreur,  'Erreurs',   '#ef4444', 'fa-circle-xmark'],
                ] as $s)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:12px;text-align:center;">
                    <i class="fa-solid {{ $s[3] }}" style="color:{{ $s[2] }};font-size:16px;display:block;margin-bottom:5px;"></i>
                    <div style="font-size:20px;font-weight:900;color:#0f172a;">{{ $s[0] }}</div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:2px;">{{ $s[1] }}</div>
                </div>
                @endforeach
            </div>

            <div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:14px;text-align:center;">
                <i class="fa-solid fa-list" style="color:#8b5cf6;margin-right:6px;"></i>
                {{ number_format($importLignes,0,',',' ') }} lignes traitées au total
            </div>

            {{-- Options --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">

                <div class="sa-option-card" onclick="openModal('modal-imports-hist');closeModal('modal-imports');">
                    <i class="fa-solid fa-clock-rotate-left" style="color:#f97316;"></i>
                    <div class="opt-title">Historique complet</div>
                    <div class="opt-desc">Voir tous les imports avec statuts, erreurs et téléchargements</div>
                </div>

                <div class="sa-option-card" onclick="openModal('modal-purge-imports');closeModal('modal-imports');">
                    <i class="fa-solid fa-broom" style="color:#ef4444;"></i>
                    <div class="opt-title">Purger les imports</div>
                    <div class="opt-desc">Libérer l'espace en vidant csv_content et xlsx_content</div>
                </div>

                <a href="{{ route('back.imports.index') }}" class="sa-option-card">
                    <i class="fa-solid fa-list-check" style="color:#3b82f6;"></i>
                    <div class="opt-title">Backoffice imports</div>
                    <div class="opt-desc">Page dédiée de gestion des imports CSV</div>
                </a>

                <div class="sa-option-card" onclick="openModal('modal-perf-bdd');closeModal('modal-imports');">
                    <i class="fa-solid fa-database" style="color:#10b981;"></i>
                    <div class="opt-title">Stats base de données</div>
                    <div class="opt-desc">Voir la taille occupée par les imports en base</div>
                </div>

            </div>

            @if($importErreur > 0)
            <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:12px;margin-top:14px;font-size:13px;color:#991b1b;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><strong>{{ $importErreur }} import(s) en erreur</strong> — consultez l'historique pour les détails.</span>
            </div>
            @endif

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-soft sa-btn-sm" onclick="closeModal('modal-imports')">Fermer</button>
        </div>
    </div>
</div>

<style>
.sa-option-card {
    border: 2px solid var(--sa-border, #e2e8f0);
    border-radius: 14px;
    padding: 16px;
    cursor: pointer;
    transition: all .2s;
    text-align: center;
    background: white;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.sa-option-card:hover {
    border-color: var(--sa-gold, #f59e0b);
    background: var(--sa-gold-soft, #fffbeb);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245,158,11,.15);
}
.sa-option-card i { font-size: 22px; }
.opt-title { font-size: 13px; font-weight: 800; color: #0f172a; }
.opt-desc  { font-size: 11px; color: #64748b; line-height: 1.4; }
</style>
