
@php
    $statutMandat        = $copropriete['statut_mandat']        ?? 'inconnu';
    $representantConnu   = $copropriete['representant_legal_connu'] ?? false;
    $representantNom     = $copropriete['representant_legal_nom']   ?? null;
    $siretSyndic         = $copropriete['siret_syndic']             ?? null;
    $messageRepresentant = $copropriete['message_representant']     ?? null;
    $mandatEnCours       = $copropriete['mandat_en_cours']          ?? null;
    $dateFinMandat       = $copropriete['date_fin_mandat']          ?? null;
    $typeRepresentant    = $copropriete['representant_legal_type']  ?? null;
@endphp

{{-- ── Badge statut ── --}}
@if ($representantConnu && $statutMandat === 'actif')
    {{-- ✅ Représentant actif --}}
    <span class="badge-representant badge-actif">
        <i class="bi bi-check-circle-fill me-1"></i>
        Représentant légal actif
    </span>

@elseif ($statutMandat === 'expire')
    {{-- 🟠 Mandat expiré --}}
    <span class="badge-representant badge-expire">
        <i class="bi bi-exclamation-circle-fill me-1"></i>
        {{ $mandatEnCours ?? 'Mandat expiré sans successeur déclaré' }}
        @if ($dateFinMandat)
            <small class="ms-1">({{ \Carbon\Carbon::parse($dateFinMandat)->format('d/m/Y') }})</small>
        @endif
    </span>

@elseif ($statutMandat === 'sans_syndic')
    {{-- 🔴 Sans syndic --}}
    <span class="badge-representant badge-sans-syndic">
        <i class="bi bi-x-circle-fill me-1"></i>
        Copropriété sans syndic déclaré
    </span>

@else
    {{-- ⚫ Inconnu --}}
    <span class="badge-representant badge-inconnu">
        <i class="bi bi-question-circle-fill me-1"></i>
        Pas de représentant légal connu
    </span>
@endif

{{-- ── Détail représentant ── --}}
@if ($representantConnu && $representantNom)
    <div class="representant-detail mt-2">
        <div class="representant-nom">{{ $representantNom }}</div>
        @if ($typeRepresentant)
            <div class="representant-type text-muted small">{{ ucfirst($typeRepresentant) }}</div>
        @endif
        @if ($siretSyndic)
            <div class="representant-siret text-muted small">SIRET : {{ $siretSyndic }}</div>
        @endif
        @if ($statutMandat === 'expire')
            <div class="representant-alerte text-warning small mt-1">
                <i class="bi bi-clock-history me-1"></i>
                Ce syndic avait un mandat mais celui-ci est expiré — aucun successeur déclaré à ce jour.
            </div>
        @endif
    </div>
@endif

<style>

.badge-representant {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.badge-actif {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.badge-expire {
    background-color: #fff3cd;
    color: #92400e;
    border: 1px solid #fcd34d;
}

.badge-sans-syndic {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.badge-inconnu {
    background-color: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.representant-detail {
    padding: 10px 14px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.representant-nom {
    font-weight: 700;
    font-size: 15px;
    color: #111827;
}

</style>