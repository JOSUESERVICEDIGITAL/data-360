@extends('front.layouts.app')

@section('title', 'Data Rocket - Résultat recherche')

@section('content')

    @php
        function dr_value($model, array $keys, $default = '-')
        {
            foreach ($keys as $key) {
                if (is_object($model) && isset($model->{$key}) && $model->{$key} !== null && $model->{$key} !== '') {
                    return $model->{$key};
                }

                $raw = is_object($model) ? $model->raw_data ?? [] : $model['raw_data'] ?? [];

                if (is_string($raw)) {
                    $raw = json_decode($raw, true) ?: [];
                }

                if (is_array($raw) && isset($raw[$key]) && $raw[$key] !== null && $raw[$key] !== '') {
                    return $raw[$key];
                }
            }

            return $default;
        }
    @endphp

    <style>
        .result-page {
            background: #f8fafc;
            color: #1e293b;
            padding: 2rem 0;
        }

        .result-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .result-title {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #0f172a;
        }

        .result-title span {
            color: #0053b3;
        }

        .dr-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .dr-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .dr-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #0053b3;
            display: inline-block;
            color: #0053b3;
        }

        .dr-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .dr-badge {
            background-color: #e6f0ff;
            color: #0053b3;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 0.75rem;
        }

        .dr-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .dr-info-item {
            background-color: #f8fafc;
            padding: 0.85rem;
            border-radius: 12px;
            border: 1px solid #eef2f7;
        }

        .dr-info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .dr-info-value {
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .dr-empty {
            color: #64748b;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
        }

        .dr-hr {
            margin: 1rem 0;
            border: none;
            border-top: 1px solid #e2e8f0;
        }

        .dr-btn {
            display: inline-block;
            background-color: #0053b3;
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .dr-btn:hover {
            background-color: #003d85;
            transform: translateY(-1px);
        }

        .dr-btn-white {
            background: white;
            color: #0053b3;
        }

        .dr-cta {
            background: linear-gradient(135deg, #0053b3 0%, #003d85 100%);
            color: white;
            text-align: center;
        }

        .dr-cta h3 {
            color: white;
        }

        .dr-cta p {
            margin-bottom: 1rem;
            color: #eaf2ff;
        }

        details {
            margin-top: 1rem;
        }

        summary {
            cursor: pointer;
            color: #0053b3;
            font-weight: 700;
        }

        pre {
            white-space: pre-wrap;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            overflow: auto;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .result-container {
                padding: 0 1rem;
            }

            .dr-card {
                padding: 1rem;
            }

            .result-title {
                font-size: 1.5rem;
            }
        }
    </style>

    <section class="result-page">
        <div class="result-container">

            <h1 class="result-title">
                🔍 Résultat pour : <span>{{ $q ?? '—' }}</span>
            </h1>

@php
    function dr_norm_addr($value) {
        $value = strtolower($value ?? '');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = preg_replace('/\b(rue|r|avenue|av|boulevard|bd|allee|all|chemin|ch|route|rte|impasse|place|pl)\b/', ' ', $value);
        $value = preg_replace('/[^a-z0-9 ]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    function dr_addr_match_score($searched, $candidate) {
        $searchedNorm = dr_norm_addr($searched);
        $candidateNorm = dr_norm_addr($candidate);

        if (!$searchedNorm || !$candidateNorm) {
            return 0;
        }

        preg_match('/\b\d+\b/', $searchedNorm, $searchedNum);
        preg_match('/\b\d+\b/', $candidateNorm, $candidateNum);

        $searchedNumber = $searchedNum[0] ?? null;
        $candidateNumber = $candidateNum[0] ?? null;

        if ($searchedNumber && $candidateNumber && $searchedNumber !== $candidateNumber) {
            return 0;
        }

        similar_text($searchedNorm, $candidateNorm, $percent);

        $searchedWords = array_filter(explode(' ', $searchedNorm));
        $candidateWords = array_filter(explode(' ', $candidateNorm));

        $common = array_intersect($searchedWords, $candidateWords);

        return (int) $percent + (count($common) * 8);
    }

    $adresseRecherchee = $adresse->adresse_complete ?? $q ?? '';

    $coproPrincipale = collect($resultat['coproprietes'] ?? [])
        ->map(function ($copro) use ($adresseRecherchee) {
            $scoreExact = dr_addr_match_score($adresseRecherchee, dr_value($copro, ['adresse_complete'], ''));

            return [
                'copro' => $copro,
                'score_exact' => $scoreExact,
            ];
        })
        ->filter(fn ($item) => $item['score_exact'] >= 85)
        ->sortByDesc('score_exact')
        ->first()['copro'] ?? null;

    $adresseEnregistree = !empty($coproPrincipale);

    $representantConnu = $coproPrincipale
        ? (bool) ($coproPrincipale->representant_legal_connu ?? false)
        : false;

    $representantPrincipal = $representantConnu
        ? dr_value($coproPrincipale, ['representant_legal_nom', 'syndic_nom'], null)
        : null;
@endphp

<div class="dr-card" style="border-left:5px solid #0053b3;">
    <h2>👤 Représentant légal de cette adresse</h2>

    @if (!$adresseEnregistree)
        <div class="dr-empty">
            Adresse non enregistrée dans le RNIC pour cette recherche.
        </div>
    @else
        <div class="dr-info-grid">
            <div class="dr-info-item">
                <div class="dr-info-label">Adresse contrôlée</div>
                <div class="dr-info-value">
                    {{ $adresse->adresse_complete ?? $q ?? '-' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">Adresse RNIC trouvée</div>
                <div class="dr-info-value">
                    {{ dr_value($coproPrincipale, ['adresse_complete']) }}
                    <br>
                    {{ dr_value($coproPrincipale, ['code_postal']) }}
                    {{ dr_value($coproPrincipale, ['ville']) }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">Statut représentant</div>
                <div class="dr-info-value">
                    @if ($representantConnu && $representantPrincipal)
                        Avec représentant légal connu
                    @else
                        Pas de représentant légal connu
                    @endif
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">Nom représentant / syndic</div>
                <div class="dr-info-value">
                    {{ $representantConnu ? ($representantPrincipal ?? '-') : '-' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">Type représentant</div>
                <div class="dr-info-value">
                    {{ $representantConnu ? dr_value($coproPrincipale, ['representant_legal_type', 'type_syndic']) : '-' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">SIREN syndic</div>
                <div class="dr-info-value">
                    {{ $representantConnu ? dr_value($coproPrincipale, ['siren_syndic']) : '-' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">SIRET syndic</div>
                <div class="dr-info-value">
                    {{ $representantConnu ? dr_value($coproPrincipale, ['siret_syndic']) : '-' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">Immatriculation copropriété</div>
                <div class="dr-info-value">
                    {{ $coproPrincipale->numero_immatriculation ?? '-' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">Lots habitation</div>
                <div class="dr-info-value">
                    {{ $coproPrincipale->nombre_lots_habitation ?? '-' }}
                </div>
            </div>
        </div>
    @endif
</div>

            @if (empty($resultat['success']))
                <div class="dr-card" style="text-align:center;">
                    <h3>Aucun résultat complet</h3>
                    <p style="margin:1rem 0; color:#64748b;">
                        {{ $resultat['message'] ?? 'Adresse non trouvée.' }}
                    </p>

                    <a href="{{ route('front.home') }}#carte" class="dr-btn">
                        ← Rechercher une autre adresse
                    </a>
                </div>
            @else
                <div class="dr-card">
                    <h2>📍 Adresse trouvée</h2>

                    <div class="dr-info-grid">
                        <div class="dr-info-item">
                            <div class="dr-info-label">Adresse complète</div>
                            <div class="dr-info-value">{{ $adresse->adresse_complete ?? '-' }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Ville / Code postal</div>
                            <div class="dr-info-value">{{ $adresse->code_postal ?? '' }} {{ $adresse->ville ?? '' }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Code INSEE</div>
                            <div class="dr-info-value">{{ $adresse->code_insee ?? '-' }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Coordonnées GPS</div>
                            <div class="dr-info-value">{{ $adresse->latitude ?? '-' }}, {{ $adresse->longitude ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dr-card">
                    <h2>🏛️ Cadastre</h2>

                    @forelse(($resultat['cadastre'] ?? []) as $parcelle)
                        <div class="dr-badge">Parcelle cadastrale</div>

                        <div class="dr-info-grid">
                            <div class="dr-info-item">
                                <div class="dr-info-label">ID parcelle</div>
                                <div class="dr-info-value">{{ $parcelle['id_parcelle'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Commune</div>
                                <div class="dr-info-value">{{ $parcelle['commune'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Section</div>
                                <div class="dr-info-value">{{ $parcelle['section'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Numéro</div>
                                <div class="dr-info-value">{{ $parcelle['numero'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Contenance</div>
                                <div class="dr-info-value">{{ $parcelle['contenance'] ?? '-' }} m²</div>
                            </div>
                        </div>

                        @if (!$loop->last)
                            <hr class="dr-hr">
                        @endif
                    @empty
                        <div class="dr-empty">Aucune parcelle trouvée.</div>
                    @endforelse
                </div>

                <div class="dr-card">
                    <h2>🏢 Bâtiments</h2>

                    @forelse(($resultat['batiments'] ?? []) as $batiment)
                        <div class="dr-badge">Bâtiment BDNB</div>

                        <div class="dr-info-grid">
                            <div class="dr-info-item">
                                <div class="dr-info-label">Identifiant BDNB</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['identifiant_bdnb', 'batiment_groupe_id', 'id_batiment_groupe', 'id']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Type de bâtiment</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['type_batiment', 'usage_niveau_1_txt', 'usage_principal', 'usage_niveau_1']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Année construction</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['annee_construction', 'annee_construction_estimee', 'annee_construction_dpe', 'annee_construction_max']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Nombre logements</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['nombre_logements', 'nb_logements', 'nb_log', 'nombre_logement']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Nombre niveaux</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['nombre_niveaux', 'nb_niveaux', 'nb_niveau', 'hauteur_nb_niveau']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Hauteur</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['hauteur', 'hauteur_mean', 'hauteur_moyenne']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Surface habitable</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['surface_habitable', 's_hab', 'surface_habitable_logement']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Surface emprise sol</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['surface_emprise_sol', 's_emprise_sol', 'surface_emprise']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">DPE</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['classe_dpe', 'dpe_classe', 'classe_bilan_dpe']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">GES</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['ges', 'classe_ges', 'ges_classe']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Type chauffage</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, ['type_chauffage', 'chauffage', 'type_installation_chauffage']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Énergie chauffage</div>
                                <div class="dr-info-value">
                                    {{ dr_value($batiment, [
                                        'energie_chauffage',
                                        'l_ch_princ',
                                        'l_ch_princ_generateur',
                                        'l_ch_princ_energie',
                                        'energie_principale_chauffage',
                                        'gen_ch_princ',
                                    ]) }}
                                </div>
                            </div>
                        </div>

                        <details>
                            <summary>Voir données brutes BDNB</summary>
                            <pre>{{ json_encode($batiment->raw_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>

                        @if (!$loop->last)
                            <hr class="dr-hr">
                        @endif
                    @empty
                        <div class="dr-empty">Aucun bâtiment trouvé pour le moment.</div>
                    @endforelse
                </div>

                <div class="dr-card">
                    <h2>🏘️ Copropriétés RNIC</h2>

                    @forelse(($resultat['coproprietes'] ?? []) as $copro)
                        <div class="dr-badge">
                            Copropriété RNIC — Score {{ dr_value($copro, ['score_match'], '-') }}
                        </div>

                        <div class="dr-info-grid">
                            <div class="dr-info-item">
                                <div class="dr-info-label">Nom résidence</div>
                                <div class="dr-info-value">{{ $copro->nom_copropriete ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Adresse RNIC</div>
                                <div class="dr-info-value">
                                    {{ dr_value($copro, ['adresse_complete']) }}
                                    <br>{{ dr_value($copro, ['code_postal']) }} {{ dr_value($copro, ['ville']) }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Numéro immatriculation</div>
                                <div class="dr-info-value">{{ $copro->numero_immatriculation ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIREN copropriété</div>
                                <div class="dr-info-value">{{ $copro->siren_copropriete ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Lots total</div>
                                <div class="dr-info-value">{{ $copro->nombre_lots_total ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Lots habitation</div>
                                <div class="dr-info-value">{{ $copro->nombre_lots_habitation ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Nombre bâtiments</div>
                                <div class="dr-info-value">{{ $copro->nombre_batiments ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Adresses associées</div>
                                <div class="dr-info-value">{{ $copro->nombre_adresses_associees ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Représentant légal</div>
                                <div class="dr-info-value">
                                    @if ($copro->representant_legal_connu)
                                        {{ $copro->representant_legal_nom ?? 'Représentant légal connu' }}
                                    @else
                                        {{ $copro->message_representant ?? 'Pas de représentant légal connu' }}
                                    @endif
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIREN syndic</div>
                                <div class="dr-info-value">{{ dr_value($copro, ['siren_syndic']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIRET syndic</div>
                                <div class="dr-info-value">{{ dr_value($copro, ['siret_syndic']) }}</div>
                            </div>
                        </div>

                        @php
                            $adressesAssociees = $copro->raw_data['adresses_associees_liste'] ?? [];
                        @endphp

                        @if (!empty($adressesAssociees))
                            <h3 style="margin-top:1rem;">Adresses associées à cette immatriculation</h3>
                            <div class="dr-info-item">
                                <ul style="margin-left:1rem;">
                                    @foreach ($adressesAssociees as $adresseAssociee)
                                        <li>{{ $adresseAssociee }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h3 style="margin-top:1rem;">Syndics associés</h3>

                        @forelse($copro->syndics ?? [] as $syndic)
                            <div class="dr-info-grid">
                                <div class="dr-info-item">
                                    <div class="dr-info-label">Nom syndic</div>
                                    <div class="dr-info-value">{{ $syndic->nom ?? '-' }}</div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">SIREN</div>
                                    <div class="dr-info-value">{{ $syndic->siren ?? '-' }}</div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">SIRET</div>
                                    <div class="dr-info-value">{{ $syndic->siret ?? '-' }}</div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">Capital social</div>
                                    <div class="dr-info-value">{{ $syndic->capital_social ?? '-' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="dr-empty">Aucun syndic associé.</div>
                        @endforelse

                        @if (!$loop->last)
                            <hr class="dr-hr">
                        @endif
                    @empty
                        <div class="dr-empty">
                            Aucune copropriété RNIC trouvée. Vérifie que la table <strong>rnic_coproprietes</strong>
                            contient bien
                            l’adresse, le code postal, le représentant légal et les SIREN/SIRET syndic.
                        </div>
                    @endforelse
                </div>

                <div class="dr-card">
                    <h2>🏢 Syndics / Entreprises</h2>

                    @forelse(($resultat['syndics'] ?? []) as $syndic)
                        <div class="dr-badge">Entreprise syndic</div>

                        <div class="dr-info-grid">
                            <div class="dr-info-item">
                                <div class="dr-info-label">Nom syndic</div>
                                <div class="dr-info-value">{{ $syndic->nom ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIREN</div>
                                <div class="dr-info-value">{{ $syndic->siren ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIRET</div>
                                <div class="dr-info-value">{{ $syndic->siret ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Forme juridique</div>
                                <div class="dr-info-value">{{ $syndic->forme_juridique ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Activité</div>
                                <div class="dr-info-value">{{ $syndic->activite ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Capital social</div>
                                <div class="dr-info-value">{{ $syndic->capital_social ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Chiffre d’affaires</div>
                                <div class="dr-info-value">{{ $syndic->chiffre_affaires ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Résultat</div>
                                <div class="dr-info-value">{{ $syndic->resultat ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Effectif</div>
                                <div class="dr-info-value">{{ $syndic->effectif ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Date création</div>
                                <div class="dr-info-value">{{ $syndic->date_creation ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Dirigeant principal</div>
                                <div class="dr-info-value">{{ $syndic->dirigeant_principal ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Adresse siège</div>
                                <div class="dr-info-value">
                                    {{ $syndic->adresse_complete ?? '-' }}
                                    @if ($syndic->code_postal || $syndic->ville)
                                        <br>{{ $syndic->code_postal }} {{ $syndic->ville }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($syndic->pappers_link ?? null)
                            <div style="margin-top:1rem;">
                                <a href="{{ $syndic->pappers_link }}" target="_blank" class="dr-btn">
                                    Voir sur Pappers →
                                </a>
                            </div>
                        @endif

                        <details>
                            <summary>Voir données brutes Sirene / Pappers</summary>
                            <pre>{{ json_encode($syndic->raw_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>

                        @if (!$loop->last)
                            <hr class="dr-hr">
                        @endif
                    @empty
                        <div class="dr-empty">
                            Aucun syndic trouvé pour le moment. Il faut que RNIC local fournisse un SIREN/SIRET syndic pour
                            appeler Sirene/Pappers.
                        </div>
                    @endforelse
                </div>

                <div class="dr-card dr-cta">
                    <h3>✨ Accédez à toutes ces données en illimité</h3>
                    <p>Adresse, cadastre, bâtiments, copropriétés, syndics, SIREN/SIRET et données enrichies.</p>
                    <a href="{{ route('front.home') }}#carte" class="dr-btn dr-btn-white">
                        Rechercher une autre adresse →
                    </a>
                </div>

            @endif

        </div>
    </section>
@endsection
