@extends('front.layouts.app')

@section('title', 'Data Rocket - Rapport immobilier')

@section('content')

    @php
        function dr_value($model, array $keys, $default = '-')
        {
            foreach ($keys as $key) {
                if (is_object($model) && isset($model->{$key}) && $model->{$key} !== null && $model->{$key} !== '') {
                    return $model->{$key};
                }
                if (is_array($model) && isset($model[$key]) && $model[$key] !== null && $model[$key] !== '') {
                    return $model[$key];
                }
                $raw = is_object($model) ? ($model->raw_data ?? []) : ($model['raw_data'] ?? []);
                if (is_string($raw)) {
                    $raw = json_decode($raw, true) ?: [];
                }
                if (is_array($raw) && isset($raw[$key]) && $raw[$key] !== null && $raw[$key] !== '') {
                    return $raw[$key];
                }
            }
            return $default;
        }

        $copros = collect($resultat['coproprietes'] ?? []);
        $coproPrincipale = $copros
            ->filter(fn($copro) => (bool) dr_value($copro, ['adresse_match_exact'], false))
            ->sortByDesc(fn($copro) => (int) dr_value($copro, ['score_match'], 0))
            ->first();

        $adresseEnregistree = !empty($coproPrincipale);
        $representantNom = $coproPrincipale ? dr_value($coproPrincipale, ['representant_legal_nom', 'syndic_nom', 'raison_sociale_representant_legal', 'identification_representant_legal'], null) : null;
        $sirenSyndic = $coproPrincipale ? dr_value($coproPrincipale, ['siren_syndic'], null) : null;
        $siretSyndic = $coproPrincipale ? dr_value($coproPrincipale, ['siret_syndic', 'siret_representant_legal'], null) : null;
        $representantConnu = $adresseEnregistree && (!empty($representantNom) || !empty($sirenSyndic) || !empty($siretSyndic));

        $syndicsAffiches = collect($resultat['syndics'] ?? []);
        foreach ($copros as $copro) {
            if (is_object($copro) && isset($copro->syndics)) {
                $syndicsAffiches = $syndicsAffiches->merge($copro->syndics);
            }
        }
        $syndicsAffiches = $syndicsAffiches->filter()->unique(function ($syndic) {
            return dr_value($syndic, ['siret'], null) ?: dr_value($syndic, ['siren'], null) ?: dr_value($syndic, ['nom'], uniqid());
        })->values();

        $qpv = $resultat['qpv'] ?? null;
        $qpvChecks = collect($qpv['checks'] ?? []);
        $hasQpv2024 = $qpvChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
        $hasQpv2015 = $qpvChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
        $hasZfu = $qpvChecks->contains(fn($c) => $c['result']['zfu'] ?? false);
        $hasAnyZone = $hasQpv2024 || $hasQpv2015 || $hasZfu;
        $qpvEligible = !$hasAnyZone;

        $batimentsCount = count($resultat['batiments'] ?? []);
        $cadastreCount = count($resultat['cadastre'] ?? []);
        $coprosCount = count($resultat['coproprietes'] ?? []);
        $syndicsCount = $syndicsAffiches->count();
        $proprietairesCount = count($resultat['proprietaires_bdnb'] ?? []);

        // ============================================
        // RNB - RÉCUPÉRATION DES DONNÉES
        // ============================================
        // ============================================
        // RNB - RÉCUPÉRATION ROBUSTE DES ADRESSES
        // ============================================
        $rnbData = $resultat['rnb'] ?? null;
        $rnbId = null;
        $rnbAddresses = collect();
        $rnbStatus = null;

        if ($rnbData) {
            // Parcours récursif pour trouver TOUTES les adresses
            function extractRnbAddresses($data, &$addresses, &$rnbId, &$rnbStatus)
            {
                if (is_array($data)) {
                    // Vérifier si c'est un bâtiment avec rnb_id
                    if (isset($data['rnb_id']) && !$rnbId) {
                        $rnbId = $data['rnb_id'];
                    }
                    if (isset($data['status']) && !$rnbStatus) {
                        $rnbStatus = $data['status'];
                    }

                    // Extraire les adresses
                    if (isset($data['addresses']) && is_array($data['addresses'])) {
                        foreach ($data['addresses'] as $addr) {
                           $label = $addr['label']
    ?? $addr['adresse']
    ?? trim(collect([
        $addr['street_number'] ?? null,
        $addr['street_rep'] ?? null,
        $addr['street'] ?? null,
        $addr['city_zipcode'] ?? null,
        $addr['city_name'] ?? null,
    ])->filter()->implode(' '));

if ($label) {
    $addresses->push([
        'adresse' => $label,
        'cle_ban' => $addr['cle_interop_ban'] ?? $addr['cle_ban'] ?? $addr['id'] ?? null,
        'id_ban' => $addr['ban_id'] ?? $addr['id_ban'] ?? null,
    ]);
}
                        }
                    }

                    // Parcourir récursivement
                    foreach ($data as $value) {
                        extractRnbAddresses($value, $addresses, $rnbId, $rnbStatus);
                    }
                }
            }

            extractRnbAddresses($rnbData, $rnbAddresses, $rnbId, $rnbStatus);

            // Nettoyer et dédupliquer
            $rnbAddresses = $rnbAddresses
                ->filter(fn($addr) => !empty($addr['adresse']) && $addr['adresse'] !== '-')
                ->unique('adresse')
                ->values();
        }

        $rnbAddressesCount = $rnbAddresses->count();
    @endphp

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --dr-primary: #0f172a;
            --dr-secondary: #334155;
            --dr-muted: #64748b;
            --dr-soft: #f8fafc;
            --dr-border: #e2e8f0;
            --dr-blue: #0053b3;
            --dr-blue-dark: #003d85;
            --dr-success: #15803d;
            --dr-success-bg: #dcfce7;
            --dr-danger: #b91c1c;
            --dr-danger-bg: #fee2e2;
            --dr-warning: #b45309;
            --dr-warning-bg: #fff7ed;
            --dr-white: #ffffff;
        }

        .dr-page {
            background: radial-gradient(circle at top left, rgba(0, 83, 179, .08), transparent 35%), linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            min-height: 100vh;
            padding: 32px 0 70px;
            color: var(--dr-primary);
        }

        .dr-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .dr-hero {
            display: grid;
            grid-template-columns: 1.4fr 0.8fr;
            gap: 22px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #0053b3 100%);
            color: white;
            border-radius: 30px;
            padding: 34px;
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;
        }

        .dr-hero:after {
            content: "";
            position: absolute;
            right: -120px;
            top: -120px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }

        .dr-hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .18);
            padding: 8px 14px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .dr-hero h1 {
            font-size: clamp(30px, 4vw, 46px);
            font-weight: 900;
            margin: 0 0 14px;
            letter-spacing: -0.02em;
        }

        .dr-hero p {
            color: rgba(255, 255, 255, .82);
            font-size: 16px;
            line-height: 1.6;
            max-width: 550px;
        }

        .dr-hero-side {
            background: rgba(255, 255, 255, .1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 24px;
            padding: 22px;
        }

        .dr-side-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, .7);
            margin-bottom: 6px;
        }

        .dr-side-value {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 16px;
            word-break: break-word;
        }

        .dr-side-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-top: 1px solid rgba(255, 255, 255, .15);
            padding-top: 14px;
        }

        .dr-side-meta span {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: rgba(255, 255, 255, .8);
        }

        /* Navigation Fixe (sticky) */
        .dr-tabs {
            position: sticky;
            top: 20px;
            z-index: 100;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(12px);
            border: 1px solid var(--dr-border);
            border-radius: 60px;
            padding: 8px 16px;
            margin-bottom: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
        }

        .dr-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 700;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--dr-secondary);
        }

        .dr-tab-btn i {
            font-size: 0.9rem;
        }

        .dr-tab-btn:hover {
            background: #e6f0ff;
            color: var(--dr-blue);
        }

        .dr-tab-btn.active {
            background: var(--dr-blue);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 83, 179, .3);
        }

        /* Stats */
        .dr-stats {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .dr-stat-card {
            background: white;
            border: 1px solid var(--dr-border);
            border-radius: 20px;
            padding: 18px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .dr-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, .06);
        }

        .dr-stat-icon {
            width: 44px;
            height: 44px;
            background: #e6f0ff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dr-blue);
            font-size: 1.2rem;
            margin-bottom: 12px;
        }

        .dr-stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dr-muted);
            font-weight: 800;
            margin-bottom: 4px;
        }

        .dr-stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--dr-primary);
        }

        .dr-stat-value.success {
            color: var(--dr-success);
        }

        .dr-stat-value.danger {
            color: var(--dr-danger);
        }

        /* Panels */
        .dr-panel {
            background: white;
            border: 1px solid var(--dr-border);
            border-radius: 26px;
            padding: 28px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, .04);
            margin-bottom: 24px;
            display: none;
        }

        .dr-panel.active {
            display: block;
        }

        .dr-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--dr-border);
        }

        .dr-panel-title {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .dr-panel-icon {
            width: 52px;
            height: 52px;
            background: #e6f0ff;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dr-blue);
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .dr-panel-title h2 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--dr-primary);
            margin-bottom: 4px;
        }

        .dr-panel-title p {
            color: var(--dr-muted);
            font-size: 0.85rem;
        }

        .dr-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dr-status.success {
            background: var(--dr-success-bg);
            color: var(--dr-success);
        }

        .dr-status.danger {
            background: var(--dr-danger-bg);
            color: var(--dr-danger);
        }

        .dr-status.warning {
            background: var(--dr-warning-bg);
            color: var(--dr-warning);
        }

        .dr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 14px;
        }

        .dr-field {
            background: var(--dr-soft);
            border: 1px solid var(--dr-border);
            border-radius: 16px;
            padding: 14px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .dr-field:hover {
            background: #f1f5f9;
            border-color: var(--dr-blue);
        }

        .dr-field-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dr-muted);
            font-weight: 800;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dr-field-label i {
            font-size: 0.7rem;
            color: var(--dr-muted);
            opacity: 0.6;
        }

        .dr-field-value {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--dr-primary);
            word-break: break-word;
        }

        .dr-field-value code {
            background: var(--dr-border);
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.75rem;
        }

        .dr-record {
            border: 1px solid var(--dr-border);
            border-radius: 20px;
            padding: 18px;
            margin-top: 16px;
            background: white;
        }

        .dr-record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--dr-border);
        }

        .dr-record-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--dr-primary);
        }

        .dr-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e293b;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 1000;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        }

        .dr-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .dr-toast i {
            margin-right: 8px;
            color: #10b981;
        }

        .dr-empty {
            background: var(--dr-soft);
            border: 1px dashed var(--dr-border);
            border-radius: 18px;
            padding: 24px;
            text-align: center;
            color: var(--dr-muted);
        }

        .dr-cta {
            background: linear-gradient(135deg, #0f172a 0%, #0053b3 100%);
            border-radius: 28px;
            padding: 40px;
            text-align: center;
            color: white;
        }

        .dr-cta h3 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .dr-cta p {
            color: rgba(255, 255, 255, .8);
            margin-bottom: 24px;
        }

        .dr-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .dr-btn-primary {
            background: var(--dr-blue);
            color: white;
        }

        .dr-btn-primary:hover {
            background: var(--dr-blue-dark);
            transform: translateY(-2px);
        }

        .dr-btn-white {
            background: white;
            color: var(--dr-blue);
        }

        .dr-btn-white:hover {
            background: #eff6ff;
            transform: translateY(-2px);
        }

        details {
            margin-top: 16px;
        }

        summary {
            cursor: pointer;
            color: var(--dr-blue);
            font-weight: 700;
            font-size: 0.8rem;
        }

        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 14px;
            overflow-x: auto;
            font-size: 0.7rem;
            margin-top: 12px;
        }

        .info-box {
            background: #f0f9ff;
            border: 1px solid #b6d4fe;
            border-radius: 20px;
            padding: 16px;
            margin-top: 24px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .info-box i {
            color: var(--dr-blue);
            font-size: 1.2rem;
        }

        .info-box strong {
            color: var(--dr-blue);
        }

        .info-box a {
            color: var(--dr-blue);
            text-decoration: underline;
        }

        @media (max-width: 960px) {
            .dr-hero {
                grid-template-columns: 1fr;
            }

            .dr-stats {
                grid-template-columns: repeat(3, 1fr);
            }

            .dr-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                border-radius: 20px;
            }

            .dr-tab-btn {
                white-space: nowrap;
            }
        }

        @media (max-width: 640px) {
            .dr-container {
                padding: 0 14px;
            }

            .dr-hero,
            .dr-panel,
            .dr-cta {
                padding: 20px;
            }

            .dr-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .dr-panel-header {
                flex-direction: column;
            }

            .dr-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section class="dr-page">
        <div class="dr-container">

            {{-- HERO --}}
            <div class="dr-hero">
                <div>
                    <div class="dr-hero-kicker"><i class="fa-solid fa-chart-line"></i> Rapport d'analyse avancée</div>
                    <h1>Rapport d'intelligence immobilière</h1>
                    <p>Synthèse croisée BAN, Cadastre, BDNB, RNIC, QPV/ZFU, SIRENE et entreprises associées.</p>
                </div>
                <div class="dr-hero-side">
                    <div class="dr-side-label">Adresse analysée</div>
                    <div class="dr-side-value">{{ $q ?? '—' }}</div>
                    <div class="dr-side-meta">
                        <span><strong>Ville</strong> <span>{{ $adresse->ville ?? '-' }}</span></span>
                        <span><strong>Code postal</strong> <span>{{ $adresse->code_postal ?? '-' }}</span></span>
                        <span><strong>Statut</strong>
                            <span>{{ empty($resultat['success']) ? 'Incomplet' : 'Analyse disponible' }}</span></span>
                    </div>
                </div>
            </div>

            {{-- ONGLETS DE NAVIGATION --}}
            <div class="dr-tabs">
                <button class="dr-tab-btn active" data-tab="eligibilite"><i class="fa-solid fa-shield-halved"></i>
                    Éligibilité</button>
                <button class="dr-tab-btn" data-tab="representant"><i class="fa-solid fa-user-tie"></i>
                    Représentant</button>
                <button class="dr-tab-btn" data-tab="adresse"><i class="fa-solid fa-location-dot"></i> Adresse</button>
                <button class="dr-tab-btn" data-tab="rnb"><i class="fa-solid fa-diagram-project"></i> RNB</button>
                <button class="dr-tab-btn" data-tab="cadastre"><i class="fa-solid fa-map"></i> Cadastre</button>
                <button class="dr-tab-btn" data-tab="batiments"><i class="fa-solid fa-building"></i> Bâtiments</button>
                <button class="dr-tab-btn" data-tab="proprietaires"><i class="fa-solid fa-briefcase"></i>
                    Propriétaires</button>
                <button class="dr-tab-btn" data-tab="coproprietes"><i class="fa-solid fa-city"></i> Copropriétés</button>
                <button class="dr-tab-btn" data-tab="syndics"><i class="fa-solid fa-landmark"></i> Syndics</button>
            </div>

            {{-- STATS GLOBALES --}}
            <div class="dr-stats">
                <div class="dr-stat-card" data-tab="eligibilite">
                    <div class="dr-stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="dr-stat-label">QPV / ZFU</div>
                    <div class="dr-stat-value {{ $hasAnyZone ? 'danger' : 'success' }}">
                        {{ $hasAnyZone ? 'À exclure' : 'Exploitable' }}
                    </div>
                </div>
                <div class="dr-stat-card" data-tab="rnb">
                    <div class="dr-stat-icon"><i class="fa-solid fa-diagram-project"></i></div>
                    <div class="dr-stat-label">Identifiant RNB</div>
                    <div class="dr-stat-value">{{ $rnbId ? substr($rnbId, 0, 12) . '…' : 'Non trouvé' }}</div>
                </div>
                <div class="dr-stat-card" data-tab="cadastre">
                    <div class="dr-stat-icon"><i class="fa-solid fa-map"></i></div>
                    <div class="dr-stat-label">Parcelles</div>
                    <div class="dr-stat-value">{{ $cadastreCount }}</div>
                </div>
                <div class="dr-stat-card" data-tab="batiments">
                    <div class="dr-stat-icon"><i class="fa-solid fa-building"></i></div>
                    <div class="dr-stat-label">Bâtiments</div>
                    <div class="dr-stat-value">{{ $batimentsCount }}</div>
                </div>
                <div class="dr-stat-card" data-tab="coproprietes">
                    <div class="dr-stat-icon"><i class="fa-solid fa-city"></i></div>
                    <div class="dr-stat-label">Copropriétés</div>
                    <div class="dr-stat-value">{{ $coprosCount }}</div>
                </div>
                <div class="dr-stat-card" data-tab="syndics">
                    <div class="dr-stat-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <div class="dr-stat-label">Entreprises</div>
                    <div class="dr-stat-value">{{ $syndicsCount + $proprietairesCount }}</div>
                </div>
            </div>

            @if (empty($resultat['success']))
                <div class="dr-panel active">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                            <div>
                                <h2>Aucune analyse complète disponible</h2>
                                <p>{{ $resultat['message'] ?? 'Adresse non trouvée.' }}</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('front.home') }}#carte" class="dr-btn dr-btn-primary"><i
                            class="fa-solid fa-arrow-left"></i> Rechercher une autre adresse</a>
                </div>
            @else

                {{-- SECTION 1 : ÉLIGIBILITÉ QPV/ZFU --}}
                <div class="dr-panel active" id="panel-eligibilite">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <div>
                                <h2>Éligibilité QPV / ZFU</h2>
                                <p>Vérification multi-points BAN pour détecter les zones prioritaires</p>
                            </div>
                        </div>
                        @if ($qpv)
                            <div class="dr-status {{ $hasAnyZone ? 'danger' : 'success' }}">
                                <i class="fa-solid {{ $hasAnyZone ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
                                {{ $hasAnyZone ? 'Adresse à exclure' : 'Adresse exploitable' }}
                            </div>
                        @endif
                    </div>
                    <div class="dr-panel-body">
                        @if ($qpv)
                            <div class="dr-grid">
                                <div class="dr-field">
                                    <div class="dr-field-label">QP 2024</div>
                                    <div class="dr-field-value"
                                        style="color: {{ $hasQpv2024 ? 'var(--dr-danger)' : 'var(--dr-success)' }}">
                                        <i class="fa-solid {{ $hasQpv2024 ? 'fa-circle-xmark' : 'fa-circle-check' }}"></i>
                                        {{ $hasQpv2024 ? 'Zone détectée' : 'Hors zone' }}
                                    </div>
                                </div>
                                <div class="dr-field">
                                    <div class="dr-field-label">QP 2015</div>
                                    <div class="dr-field-value"
                                        style="color: {{ $hasQpv2015 ? 'var(--dr-danger)' : 'var(--dr-success)' }}">
                                        <i class="fa-solid {{ $hasQpv2015 ? 'fa-circle-xmark' : 'fa-circle-check' }}"></i>
                                        {{ $hasQpv2015 ? 'Zone détectée' : 'Hors zone' }}
                                    </div>
                                </div>
                                <div class="dr-field">
                                    <div class="dr-field-label">ZFU</div>
                                    <div class="dr-field-value"
                                        style="color: {{ $hasZfu ? 'var(--dr-danger)' : 'var(--dr-success)' }}">
                                        <i class="fa-solid {{ $hasZfu ? 'fa-circle-xmark' : 'fa-circle-check' }}"></i>
                                        {{ $hasZfu ? 'Zone détectée' : 'Hors zone' }}
                                    </div>
                                </div>
                                <div class="dr-field">
                                    <div class="dr-field-label">Points BAN contrôlés</div>
                                    <div class="dr-field-value">{{ $qpv['candidates_tested'] ?? 0 }}</div>
                                </div>
                            </div>

                            @foreach (($qpv['checks'] ?? []) as $index => $check)
                                @php
                                    $candidate = $check['candidate'] ?? [];
                                    $result = $check['result'] ?? [];
                                    $hasZone = ($result['qp_2024'] ?? false) || ($result['qp_2015'] ?? false) || ($result['zfu'] ?? false);
                                @endphp
                                <div class="dr-record">
                                    <div class="dr-record-header">
                                        <div class="dr-record-title">Point BAN {{ $index + 1 }}</div>
                                        <div class="dr-status {{ $hasZone ? 'danger' : 'success' }}">
                                            <i class="fa-solid {{ $hasZone ? 'fa-location-crosshairs' : 'fa-circle-check' }}"></i>
                                            {{ $hasZone ? 'Zone détectée' : 'Hors zone' }}
                                        </div>
                                    </div>
                                    <div class="dr-grid">
                                        <div class="dr-field">
                                            <div class="dr-field-label">Adresse BAN</div>
                                            <div class="dr-field-value">{{ $candidate['adresse'] ?? '-' }}</div>
                                        </div>
                                        <div class="dr-field">
                                            <div class="dr-field-label">Score BAN</div>
                                            <div class="dr-field-value">{{ $candidate['score'] ?? '-' }}</div>
                                        </div>
                                        <div class="dr-field">
                                            <div class="dr-field-label">Coordonnées</div>
                                            <div class="dr-field-value">{{ $candidate['latitude'] ?? '-' }},
                                                {{ $candidate['longitude'] ?? '-' }}
                                            </div>
                                        </div>
                                        <div class="dr-field">
                                            <div class="dr-field-label">Zone détectée</div>
                                            <div class="dr-field-value">
                                                @if ($hasZone)
                                                    @foreach (($result['matches'] ?? []) as $type => $match)
                                                        @if ($match['found'] ?? false)
                                                            {{ strtoupper($type) }} : {{ $match['nom'] ?? $match['code'] ?? 'Zone détectée' }}<br>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <span style="color: var(--dr-success);">Aucune zone QPV/ZFU</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="dr-empty">Aucun contrôle QPV/ZFU disponible pour cette recherche.</div>
                        @endif
                    </div>
                </div>

                {{-- SECTION 2 : REPRÉSENTANT LÉGAL --}}
                <div class="dr-panel" id="panel-representant">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-user-tie"></i></div>
                            <div>
                                <h2>Représentant légal</h2>
                                <p>Synthèse du syndic ou représentant issu du RNIC</p>
                            </div>
                        </div>
                        <div class="dr-status {{ $representantConnu ? 'success' : 'danger' }}">
                            <i class="fa-solid {{ $representantConnu ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                            {{ $representantConnu ? 'Avec représentant légal' : 'Pas de représentant légal' }}
                        </div>
                    </div>
                    <div class="dr-panel-body">
                        @if (!$adresseEnregistree)
                            <div class="dr-empty">Adresse non enregistrée dans le RNIC pour cette recherche.</div>
                        @else
                            <div class="dr-grid">
                                <div class="dr-field copyable" data-copy="{{ $adresse->adresse_complete ?? $q ?? '-' }}">
                                    <div class="dr-field-label">Adresse contrôlée <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value">{{ $adresse->adresse_complete ?? $q ?? '-' }}</div>
                                </div>
                                <div class="dr-field copyable" data-copy="{{ dr_value($coproPrincipale, ['adresse_complete']) }}">
                                    <div class="dr-field-label">Adresse RNIC <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value">
                                        {{ dr_value($coproPrincipale, ['adresse_complete']) }}<br>{{ dr_value($coproPrincipale, ['code_postal']) }}
                                        {{ dr_value($coproPrincipale, ['ville']) }}
                                    </div>
                                </div>
                                <div class="dr-field copyable" data-copy="{{ $representantConnu ? ($representantNom ?: '') : '' }}">
                                    <div class="dr-field-label">Nom représentant / syndic <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value">{{ $representantConnu ? ($representantNom ?: '-') : '-' }}</div>
                                </div>
                                <div class="dr-field copyable"
                                    data-copy="{{ $representantConnu ? dr_value($coproPrincipale, ['representant_legal_type', 'type_syndic']) : '' }}">
                                    <div class="dr-field-label">Type représentant <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value">
                                        {{ $representantConnu ? dr_value($coproPrincipale, ['representant_legal_type', 'type_syndic']) : '-' }}
                                    </div>
                                </div>
                                <div class="dr-field copyable" data-copy="{{ $representantConnu ? ($sirenSyndic ?: '') : '' }}">
                                    <div class="dr-field-label">SIREN syndic <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value"><code>{{ $representantConnu ? ($sirenSyndic ?: '-') : '-' }}</code>
                                    </div>
                                </div>
                                <div class="dr-field copyable" data-copy="{{ $representantConnu ? ($siretSyndic ?: '') : '' }}">
                                    <div class="dr-field-label">SIRET syndic <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value"><code>{{ $representantConnu ? ($siretSyndic ?: '-') : '-' }}</code>
                                    </div>
                                </div>
                                <div class="dr-field copyable"
                                    data-copy="{{ dr_value($coproPrincipale, ['numero_immatriculation']) }}">
                                    <div class="dr-field-label">Immatriculation <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value">
                                        <code>{{ dr_value($coproPrincipale, ['numero_immatriculation']) }}</code>
                                    </div>
                                </div>
                                <div class="dr-field copyable"
                                    data-copy="{{ dr_value($coproPrincipale, ['nom_copropriete', 'nom_usage_copropriete']) }}">
                                    <div class="dr-field-label">Nom résidence <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value">
                                        {{ dr_value($coproPrincipale, ['nom_copropriete', 'nom_usage_copropriete']) }}
                                    </div>
                                </div>
                                <div class="dr-field">
                                    <div class="dr-field-label">Lots habitation</div>
                                    <div class="dr-field-value">{{ dr_value($coproPrincipale, ['nombre_lots_habitation']) }}</div>
                                </div>
                                <div class="dr-field">
                                    <div class="dr-field-label">Score RNIC</div>
                                    <div class="dr-field-value">{{ dr_value($coproPrincipale, ['score_match']) }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SECTION 3 : ADRESSE --}}
                <div class="dr-panel" id="panel-adresse">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div>
                                <h2>Adresse normalisée</h2>
                                <p>Géocodage via la Base Adresse Nationale</p>
                            </div>
                        </div>
                    </div>
                    <div class="dr-panel-body">
                        <div class="dr-grid">
                            <div class="dr-field copyable" data-copy="{{ $adresse->adresse_complete ?? '-' }}">
                                <div class="dr-field-label">Adresse complète <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value">{{ $adresse->adresse_complete ?? '-' }}</div>
                            </div>
                            <div class="dr-field copyable"
                                data-copy="{{ $adresse->code_postal ?? '' }} {{ $adresse->ville ?? '' }}">
                                <div class="dr-field-label">Code postal / Ville <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value">{{ $adresse->code_postal ?? '' }} {{ $adresse->ville ?? '' }}</div>
                            </div>
                            <div class="dr-field copyable" data-copy="{{ $adresse->code_insee ?? '-' }}">
                                <div class="dr-field-label">Code INSEE <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value"><code>{{ $adresse->code_insee ?? '-' }}</code></div>
                            </div>
                            <div class="dr-field copyable"
                                data-copy="{{ $adresse->latitude ?? '-' }}, {{ $adresse->longitude ?? '-' }}">
                                <div class="dr-field-label">Coordonnées GPS <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value">{{ $adresse->latitude ?? '-' }}, {{ $adresse->longitude ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 4 : RNB - ADRESSES ASSOCIÉES --}}
                {{-- SECTION RNB - ADRESSES ASSOCIÉES --}}
                <div class="dr-panel" id="panel-rnb">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-diagram-project"></i></div>
                            <div>
                                <h2>RNB - Référentiel National des Bâtiments</h2>
                                <p>Identifiant unique du bâtiment et toutes les adresses associées</p>
                            </div>
                        </div>
                        @if($rnbId)
                            <div class="dr-status success"><i class="fa-solid fa-check-circle"></i> {{ $rnbAddressesCount }}
                                adresse(s) associée(s)</div>
                        @endif
                    </div>
                    <div class="dr-panel-body">

                        @if($rnbId)
                            <div class="dr-grid">
                                <div class="dr-field copyable" data-copy="{{ $rnbId }}">
                                    <div class="dr-field-label">Identifiant RNB <i class="fa-regular fa-copy"></i></div>
                                    <div class="dr-field-value"><code style="font-size: 1rem;">{{ $rnbId }}</code></div>
                                </div>
                                <div class="dr-field">
                                    <div class="dr-field-label">Statut du bâtiment</div>
                                    <div class="dr-field-value">
                                        <span class="dr-status success" style="display: inline-flex;">
                                            <i class="fa-solid fa-check-circle"></i> Construit
                                        </span>
                                    </div>
                                </div>
                                <div class="dr-field">
                                    <div class="dr-field-label">Nombre d'adresses associées</div>
                                    <div class="dr-field-value">{{ $rnbAddressesCount }}</div>
                                </div>
                            </div>

                            @if($rnbAddressesCount > 0)
                                <h3 style="margin: 28px 0 16px 0; font-size: 1rem; font-weight: 800; color: var(--dr-primary);">
                                    <i class="fa-solid fa-list"></i> Toutes les adresses liées à ce RNB
                                </h3>
                                <div class="dr-grid">
                                    @foreach($rnbAddresses as $addrInfo)
                                        <div class="dr-record" style="margin-top: 0;">
                                            <div class="dr-record-header" style="margin-bottom: 12px; padding-bottom: 8px;">
                                                <div class="dr-record-title">
                                                    <i class="fa-solid fa-location-dot" style="color: var(--dr-blue);"></i> Adresse associée
                                                </div>
                                            </div>
                                            <div class="dr-grid" style="grid-template-columns: 1fr;">
                                                <div class="dr-field copyable" data-copy="{{ $addrInfo['adresse'] }}">
                                                    <div class="dr-field-label">Adresse complète <i class="fa-regular fa-copy"></i></div>
                                                    <div class="dr-field-value"><strong>{{ $addrInfo['adresse'] }}</strong></div>
                                                </div>
                                                @if($addrInfo['cle_ban'])
                                                    <div class="dr-field copyable" data-copy="{{ $addrInfo['cle_ban'] }}">
                                                        <div class="dr-field-label">Clé BAN <i class="fa-regular fa-copy"></i></div>
                                                        <div class="dr-field-value"><code>{{ $addrInfo['cle_ban'] }}</code></div>
                                                    </div>
                                                @endif
                                                @if($addrInfo['id_ban'])
                                                    <div class="dr-field copyable" data-copy="{{ $addrInfo['id_ban'] }}">
                                                        <div class="dr-field-label">Identifiant BAN <i class="fa-regular fa-copy"></i></div>
                                                        <div class="dr-field-value"><code>{{ $addrInfo['id_ban'] }}</code></div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="info-box">
                                <i class="fa-solid fa-circle-info"></i>
                                <div>
                                    <strong>Améliorez le RNB</strong><br>
                                    Il manque un bâtiment ? Une adresse semble erronée ?
                                    <a href="#" target="_blank">Envoyez votre signalement</a> — tout le monde peut apporter sa
                                    pierre au RNB.
                                </div>
                            </div>
                        @else
                            <div class="dr-empty">
                                <i class="fa-solid fa-diagram-project"></i> Aucun identifiant RNB trouvé pour cette adresse.<br>
                                <span style="font-size: 0.8rem;">Le RNB est en cours de construction par l'ANCT et l'IGN.</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SECTION 5 : CADASTRE --}}
                <div class="dr-panel" id="panel-cadastre">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-map"></i></div>
                            <div>
                                <h2>Cadastre & parcelles</h2>
                                <p>Parcelles cadastrales détectées autour des coordonnées</p>
                            </div>
                        </div>
                    </div>
                    <div class="dr-panel-body">
                        @forelse(($resultat['cadastre'] ?? []) as $parcelle)
                            <div class="dr-record">
                                <div class="dr-record-header">
                                    <div class="dr-record-title">Parcelle cadastrale</div>
                                    <div class="dr-status success"><i class="fa-solid fa-map-pin"></i> Détectée</div>
                                </div>
                                <div class="dr-grid">
                                    <div class="dr-field copyable" data-copy="{{ $parcelle['id_parcelle'] ?? '-' }}">
                                        <div class="dr-field-label">ID parcelle <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ $parcelle['id_parcelle'] ?? '-' }}</code></div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ $parcelle['commune'] ?? '-' }}">
                                        <div class="dr-field-label">Commune <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value">{{ $parcelle['commune'] ?? '-' }}</div>
                                    </div>
                                    <div class="dr-field copyable"
                                        data-copy="{{ $parcelle['section'] ?? '-' }} / {{ $parcelle['numero'] ?? '-' }}">
                                        <div class="dr-field-label">Section / Numéro <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value">{{ $parcelle['section'] ?? '-' }} /
                                            {{ $parcelle['numero'] ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Contenance</div>
                                        <div class="dr-field-value">{{ $parcelle['contenance'] ?? '-' }} m²</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="dr-empty">Aucune parcelle trouvée.</div>
                        @endforelse
                    </div>
                </div>

                {{-- SECTION 6 : BÂTIMENTS --}}
                <div class="dr-panel" id="panel-batiments">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-building"></i></div>
                            <div>
                                <h2>Bâtiments détectés</h2>
                                <p>Données BDNB - Base de Données Nationale des Bâtiments</p>
                            </div>
                        </div>
                    </div>
                    <div class="dr-panel-body">
                        @forelse(($resultat['batiments'] ?? []) as $batiment)
                            <div class="dr-record">
                                <div class="dr-record-header">
                                    <div class="dr-record-title">Bâtiment BDNB</div>
                                    <div class="dr-status success"><i class="fa-solid fa-database"></i> BDNB</div>
                                </div>
                                <div class="dr-grid">
                                    <div class="dr-field copyable" data-copy="{{ dr_value($batiment, ['identifiant_bdnb']) }}">
                                        <div class="dr-field-label">Identifiant BDNB <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ dr_value($batiment, ['identifiant_bdnb']) }}</code>
                                        </div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Type bâtiment</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['type_batiment']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Année construction</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['annee_construction']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Nombre logements</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['nombre_logements']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Nombre niveaux</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['nombre_niveaux']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Hauteur</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['hauteur']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Surface habitable</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['surface_habitable']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Emprise sol</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['surface_emprise_sol']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">DPE</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['classe_dpe']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">GES</div>
                                        <div class="dr-field-value">{{ dr_value($batiment, ['ges']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Type chauffage principal</div>
                                        <div class="dr-field-value">
                                            {{ dr_value($batiment, ['type_chauffage', 'chauffage_principal', 'type_installation_chauffage']) }}
                                        </div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Énergie de chauffage</div>
                                        <div class="dr-field-value">
                                            {{ dr_value($batiment, ['energie_chauffage', 'energie_principale_chauffage', 'l_ch_princ_energie']) }}
                                        </div>
                                    </div>
                                </div>
                                <details>
                                    <summary><i class="fa-regular fa-file-code"></i> Données brutes BDNB</summary>
                                    <pre>{{ json_encode($batiment->raw_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </div>
                        @empty
                            <div class="dr-empty">Aucun bâtiment trouvé.</div>
                        @endforelse
                    </div>
                </div>

                {{-- SECTION 7 : PROPRIÉTAIRES BDNB --}}
                <div class="dr-panel" id="panel-proprietaires">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-briefcase"></i></div>
                            <div>
                                <h2>Propriétaires & entreprises BDNB</h2>
                                <p>Dénominations et SIREN détectés dans les données BDNB</p>
                            </div>
                        </div>
                    </div>
                    <div class="dr-panel-body">
                        @forelse(($resultat['proprietaires_bdnb'] ?? []) as $proprietaire)
                            <div class="dr-record">
                                <div class="dr-record-header">
                                    <div class="dr-record-title">
                                        {{ $proprietaire['nom_bdnb'] ?? $proprietaire['nom'] ?? 'Propriétaire BDNB' }}
                                    </div>
                                    <div class="dr-status success"><i class="fa-solid fa-building-circle-check"></i> Entreprise
                                    </div>
                                </div>
                                <div class="dr-grid">
                                    <div class="dr-field copyable"
                                        data-copy="{{ $proprietaire['nom_bdnb'] ?? $proprietaire['nom'] ?? '-' }}">
                                        <div class="dr-field-label">Dénomination BDNB <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value">{{ $proprietaire['nom_bdnb'] ?? $proprietaire['nom'] ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ $proprietaire['siren'] ?? '-' }}">
                                        <div class="dr-field-label">SIREN <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ $proprietaire['siren'] ?? '-' }}</code></div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ $proprietaire['siret'] ?? '-' }}">
                                        <div class="dr-field-label">SIRET siège <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ $proprietaire['siret'] ?? '-' }}</code></div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Capital social</div>
                                        <div class="dr-field-value">{{ $proprietaire['capital_social'] ?? '-' }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Forme juridique</div>
                                        <div class="dr-field-value">{{ $proprietaire['forme_juridique'] ?? '-' }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Dirigeant principal</div>
                                        <div class="dr-field-value">{{ $proprietaire['dirigeant_principal'] ?? '-' }}</div>
                                    </div>
                                </div>
                                @if(!empty($proprietaire['url_pappers']))
                                    <div style="margin-top: 16px;"><a href="{{ $proprietaire['url_pappers'] }}" target="_blank"
                                            class="dr-btn dr-btn-primary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la
                                            fiche externe</a></div>
                                @endif
                            </div>
                        @empty
                            <div class="dr-empty">Aucun propriétaire BDNB trouvé.</div>
                        @endforelse
                    </div>
                </div>

                {{-- SECTION 8 : COPROPRIÉTÉS RNIC --}}
                <div class="dr-panel" id="panel-coproprietes">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-city"></i></div>
                            <div>
                                <h2>Copropriétés RNIC</h2>
                                <p>Registre National d'Immatriculation des Copropriétés</p>
                            </div>
                        </div>
                    </div>
                    <div class="dr-panel-body">
                        @forelse(($resultat['coproprietes'] ?? []) as $copro)
                            @php
                                $coproRepNom = dr_value($copro, ['representant_legal_nom', 'syndic_nom'], null);
                                $coproRepConnu = !empty($coproRepNom) || !empty(dr_value($copro, ['siren_syndic'], null)) || !empty(dr_value($copro, ['siret_syndic'], null));
                            @endphp
                            <div class="dr-record">
                                <div class="dr-record-header">
                                    <div class="dr-record-title">
                                        {{ dr_value($copro, ['nom_copropriete', 'nom_usage_copropriete']) }}
                                    </div>
                                    <div class="dr-status {{ $coproRepConnu ? 'success' : 'warning' }}"><i
                                            class="fa-solid {{ $coproRepConnu ? 'fa-circle-check' : 'fa-circle-info' }}"></i> Score
                                        {{ dr_value($copro, ['score_match'], '-') }}
                                    </div>
                                </div>
                                <div class="dr-grid">
                                    <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['adresse_complete']) }}">
                                        <div class="dr-field-label">Adresse RNIC <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value">
                                            {{ dr_value($copro, ['adresse_complete']) }}<br>{{ dr_value($copro, ['code_postal']) }}
                                            {{ dr_value($copro, ['ville']) }}
                                        </div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['numero_immatriculation']) }}">
                                        <div class="dr-field-label">Immatriculation <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ dr_value($copro, ['numero_immatriculation']) }}</code>
                                        </div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['siren_copropriete']) }}">
                                        <div class="dr-field-label">SIREN copropriété <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ dr_value($copro, ['siren_copropriete']) }}</code></div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Lots habitation</div>
                                        <div class="dr-field-value">{{ dr_value($copro, ['nombre_lots_habitation']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Représentant légal</div>
                                        <div class="dr-field-value">
                                            {{ $coproRepConnu ? ($coproRepNom ?: 'Représentant légal connu') : dr_value($copro, ['message_representant'], 'Pas de représentant légal connu') }}
                                        </div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['siren_syndic']) }}">
                                        <div class="dr-field-label">SIREN syndic <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ dr_value($copro, ['siren_syndic']) }}</code></div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['siret_syndic']) }}">
                                        <div class="dr-field-label">SIRET syndic <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ dr_value($copro, ['siret_syndic']) }}</code></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="dr-empty">Aucune copropriété RNIC trouvée.</div>
                        @endforelse
                    </div>
                </div>

                {{-- SECTION 9 : SYNDICS --}}
                <div class="dr-panel" id="panel-syndics">
                    <div class="dr-panel-header">
                        <div class="dr-panel-title">
                            <div class="dr-panel-icon"><i class="fa-solid fa-landmark"></i></div>
                            <div>
                                <h2>Syndics & entreprises associées</h2>
                                <p>Informations SIRENE / INPI RNE des syndics</p>
                            </div>
                        </div>
                    </div>
                    <div class="dr-panel-body">
                        @forelse($syndicsAffiches as $syndic)
                            <div class="dr-record">
                                <div class="dr-record-header">
                                    <div class="dr-record-title">{{ dr_value($syndic, ['nom']) }}</div>
                                    <div class="dr-status success"><i class="fa-solid fa-id-card"></i> SIREN
                                        {{ dr_value($syndic, ['siren']) }}
                                    </div>
                                </div>
                                <div class="dr-grid">
                                    <div class="dr-field copyable" data-copy="{{ dr_value($syndic, ['nom']) }}">
                                        <div class="dr-field-label">Nom syndic <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value">{{ dr_value($syndic, ['nom']) }}</div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ dr_value($syndic, ['siren']) }}">
                                        <div class="dr-field-label">SIREN <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ dr_value($syndic, ['siren']) }}</code></div>
                                    </div>
                                    <div class="dr-field copyable" data-copy="{{ dr_value($syndic, ['siret']) }}">
                                        <div class="dr-field-label">SIRET <i class="fa-regular fa-copy"></i></div>
                                        <div class="dr-field-value"><code>{{ dr_value($syndic, ['siret']) }}</code></div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Forme juridique</div>
                                        <div class="dr-field-value">{{ dr_value($syndic, ['forme_juridique']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Capital social</div>
                                        <div class="dr-field-value">{{ dr_value($syndic, ['capital_social']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Chiffre d'affaires / Résultat</div>
                                        <div class="dr-field-value">{{ dr_value($syndic, ['chiffre_affaires']) }} /
                                            {{ dr_value($syndic, ['resultat']) }}
                                        </div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Effectif</div>
                                        <div class="dr-field-value">{{ dr_value($syndic, ['effectif']) }}</div>
                                    </div>
                                    <div class="dr-field">
                                        <div class="dr-field-label">Dirigeant principal</div>
                                        <div class="dr-field-value">{{ dr_value($syndic, ['dirigeant_principal']) }}</div>
                                    </div>
                                </div>
                                @if (dr_value($syndic, ['url_pappers'], null))
                                    <div style="margin-top: 16px;"><a href="{{ dr_value($syndic, ['url_pappers']) }}" target="_blank"
                                            class="dr-btn dr-btn-primary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la
                                            fiche externe</a></div>
                                @endif
                                <details>
                                    <summary><i class="fa-regular fa-file-code"></i> Données brutes</summary>
                                    <pre>{{ json_encode(is_object($syndic) ? ($syndic->raw_data ?? []) : ($syndic['raw_data'] ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </div>
                        @empty
                            <div class="dr-empty">Aucun syndic trouvé.</div>
                        @endforelse
                    </div>
                </div>

                {{-- CTA --}}
                <div class="dr-cta">
                    <h3><i class="fa-solid fa-rocket"></i> Analyse immobilière enrichie</h3>
                    <p>Continuez à exploiter les données adresse, cadastre, BDNB, RNIC, SIREN/SIRET et QPV/ZFU.</p>
                    <a href="{{ route('front.home') }}#carte" class="dr-btn dr-btn-white"><i
                            class="fa-solid fa-magnifying-glass"></i> Rechercher une autre adresse</a>
                </div>

            @endif
        </div>
    </section>

    <div id="copyToast" class="dr-toast"><i class="fa-solid fa-check-circle"></i> Copié dans le presse-papier</div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tab navigation
            const tabBtns = document.querySelectorAll('.dr-tab-btn');
            const panels = document.querySelectorAll('.dr-panel');
            const statCards = document.querySelectorAll('.dr-stat-card');

            function activateTab(tabId) {
                panels.forEach(panel => {
                    panel.classList.remove('active');
                });
                const targetPanel = document.getElementById('panel-' + tabId);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
                tabBtns.forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('data-tab') === tabId) {
                        btn.classList.add('active');
                    }
                });
                localStorage.setItem('activeReportTab', tabId);
            }

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    activateTab(this.getAttribute('data-tab'));
                });
            });

            statCards.forEach(card => {
                card.addEventListener('click', function () {
                    const tabId = this.getAttribute('data-tab');
                    if (tabId) activateTab(tabId);
                });
            });

            const lastTab = localStorage.getItem('activeReportTab');
            if (lastTab && document.querySelector(`.dr-tab-btn[data-tab="${lastTab}"]`)) {
                activateTab(lastTab);
            }

            // Copy functionality
            const toast = document.getElementById('copyToast');
            document.querySelectorAll('.copyable').forEach(field => {
                field.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const text = this.getAttribute('data-copy');
                    if (text && text !== '-' && text !== '') {
                        navigator.clipboard.writeText(text).then(() => {
                            toast.classList.add('show');
                            setTimeout(() => toast.classList.remove('show'), 2000);
                        });
                    }
                });
            });
        });
    </script>

@endsection