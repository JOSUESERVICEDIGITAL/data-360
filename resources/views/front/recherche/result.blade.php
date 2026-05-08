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

        // IMPORTANT : la vue ne refait plus de matching.
        // Elle prend uniquement la copro validée par le service.
        $coproPrincipale = $copros
            ->filter(fn($copro) => (bool) dr_value($copro, ['adresse_match_exact'], false))
            ->sortByDesc(fn($copro) => (int) dr_value($copro, ['score_match'], 0))
            ->first();

        $adresseEnregistree = !empty($coproPrincipale);

        $representantNom = $coproPrincipale
            ? dr_value($coproPrincipale, [
                'representant_legal_nom',
                'syndic_nom',
                'raison_sociale_representant_legal',
                'identification_representant_legal'
            ], null)
            : null;

        $sirenSyndic = $coproPrincipale
            ? dr_value($coproPrincipale, ['siren_syndic'], null)
            : null;

        $siretSyndic = $coproPrincipale
            ? dr_value($coproPrincipale, ['siret_syndic', 'siret_representant_legal'], null)
            : null;

        $representantConnu = $adresseEnregistree && (
            !empty($representantNom)
            || !empty($sirenSyndic)
            || !empty($siretSyndic)
        );

        $syndicsAffiches = collect($resultat['syndics'] ?? []);

        foreach ($copros as $copro) {
            if (is_object($copro) && isset($copro->syndics)) {
                $syndicsAffiches = $syndicsAffiches->merge($copro->syndics);
            }
        }

        $syndicsAffiches = $syndicsAffiches
            ->filter()
            ->unique(function ($syndic) {
                return dr_value($syndic, ['siret'], null)
                    ?: dr_value($syndic, ['siren'], null)
                    ?: dr_value($syndic, ['nom'], uniqid());
            })
            ->values();
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            border: 1px solid #e2e8f0;
        }

        .dr-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        }

        .dr-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 2px solid #0053b3;
            display: inline-block;
            color: #0053b3;
        }

        .dr-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 1rem 0 .5rem;
            color: #1e293b;
        }

        .dr-badge {
            background: #e6f0ff;
            color: #0053b3;
            padding: .25rem .75rem;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: .75rem;
        }

        .dr-badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .dr-badge-warning {
            background: #fff7ed;
            color: #9a3412;
        }

        .dr-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-top: .5rem;
        }

        .dr-info-item {
            background: #f8fafc;
            padding: .85rem;
            border-radius: 12px;
            border: 1px solid #eef2f7;
        }

        .dr-info-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            margin-bottom: .25rem;
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
            background: #0053b3;
            color: white;
            padding: .625rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: .2s ease;
            border: none;
            cursor: pointer;
        }

        .dr-btn:hover {
            background: #003d85;
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

        @media (max-width:768px) {
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
    $qpv = $resultat['qpv'] ?? null;
    $qpvEligible = $qpv['eligible'] ?? null;
@endphp

@if ($qpv)
    <div class="dr-card" style="border-left:6px solid {{ $qpvEligible ? '#dc2626' : '#16a34a' }};">

        <div style="
            padding:18px;
            border-radius:16px;
            background:{{ $qpvEligible ? '#fa2121' : '#fee2e2' }};
            color:{{ $qpvEligible ? '#991b1b' : '#166534' }};
            font-weight:800;
            font-size:18px;
            margin-bottom:18px;
        ">
            @if ($qpvEligible)
                ✅ Adresse exploitable : aucun point testé n’est en QPV/ZFU.
            @else
                ⛔ Adresse à exclure : au moins un point testé est en zone QPV/ZFU.
            @endif
        </div>

        <div class="dr-info-grid">
            @php
                $allChecks = collect($qpv['checks'] ?? []);

                $qp2024 = $allChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
                $qp2015 = $allChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
                $zfu = $allChecks->contains(fn($c) => $c['result']['zfu'] ?? false);
            @endphp

            <div class="dr-info-item">
                <div class="dr-info-label">QP 2024</div>
                <div class="dr-info-value" style="color:{{ $qp2024 ? '#16a34a' : '#dc2626' }};">
                    {{ $qp2024 ? 'VERT SIGVILLE — À exclure' : 'ROUGE SIGVILLE — OK' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">QP 2015</div>
                <div class="dr-info-value" style="color:{{ $qp2015 ? '#16a34a' : '#dc2626' }};">
                    {{ $qp2015 ? 'VERT SIGVILLE — À exclure' : 'ROUGE SIGVILLE — OK' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">ZFU</div>
                <div class="dr-info-value" style="color:{{ $zfu ? '#16a34a' : '#dc2626' }};">
                    {{ $zfu ? 'VERT SIGVILLE — À exclure' : 'ROUGE SIGVILLE — OK' }}
                </div>
            </div>

            <div class="dr-info-item">
                <div class="dr-info-label">Points BAN testés</div>
                <div class="dr-info-value">
                    {{ $qpv['candidates_tested'] ?? 0 }}
                </div>
            </div>
        </div>
    </div>
@endif




            {{-- 👤 REPRESENTANT LEGAL EN HAUT --}}
            <div class="dr-card" style="border-left:5px solid #0053b3;">
                <h2>👤 Représentant légal de cette adresse</h2>

                @if (!$adresseEnregistree)
                    <div class="dr-empty">
                        Adresse non enregistrée dans le RNIC pour cette recherche.
                    </div>
                @else
                    <div class="dr-badge {{ $representantConnu ? 'dr-badge-success' : 'dr-badge-warning' }}">
                        {{ $representantConnu ? 'Avec représentant légal connu' : 'Pas de représentant légal connu' }}
                    </div>

                    <div class="dr-info-grid">
                        <div class="dr-info-item">
                            <div class="dr-info-label">Adresse contrôlée</div>
                            <div class="dr-info-value">{{ $adresse->adresse_complete ?? $q ?? '-' }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Adresse RNIC trouvée</div>
                            <div class="dr-info-value">
                                {{ dr_value($coproPrincipale, ['adresse_complete']) }}
                                <br>{{ dr_value($coproPrincipale, ['code_postal']) }}
                                {{ dr_value($coproPrincipale, ['ville']) }}
                            </div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Nom représentant / syndic</div>
                            <div class="dr-info-value">{{ $representantConnu ? ($representantNom ?: '-') : '-' }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Type représentant</div>
                            <div class="dr-info-value">
                                {{ $representantConnu ? dr_value($coproPrincipale, ['representant_legal_type', 'type_syndic']) : '-' }}
                            </div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">SIREN syndic</div>
                            <div class="dr-info-value">{{ $representantConnu ? ($sirenSyndic ?: '-') : '-' }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">SIRET syndic</div>
                            <div class="dr-info-value">{{ $representantConnu ? ($siretSyndic ?: '-') : '-' }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Mandat</div>
                            <div class="dr-info-value">{{ dr_value($coproPrincipale, ['statut', 'mandat_en_cours']) }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Date fin mandat</div>
                            <div class="dr-info-value">{{ dr_value($coproPrincipale, ['date_fin_dernier_mandat']) }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Immatriculation copropriété</div>
                            <div class="dr-info-value">{{ dr_value($coproPrincipale, ['numero_immatriculation']) }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Nom résidence</div>
                            <div class="dr-info-value">
                                {{ dr_value($coproPrincipale, ['nom_copropriete', 'nom_usage_copropriete']) }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Lots habitation</div>
                            <div class="dr-info-value">{{ dr_value($coproPrincipale, ['nombre_lots_habitation']) }}</div>
                        </div>

                        <div class="dr-info-item">
                            <div class="dr-info-label">Score RNIC</div>
                            <div class="dr-info-value">{{ dr_value($coproPrincipale, ['score_match']) }}</div>
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
                            <div class="dr-info-value">{{ $adresse->latitude ?? '-' }}, {{ $adresse->longitude ?? '-' }}</div>
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
                                    <div class="dr-info-value">{{ dr_value($batiment, ['hauteur', 'hauteur_mean', 'hauteur_moyenne']) }}
                                    </div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">Surface habitable</div>
                                    <div class="dr-info-value">
                                        {{ dr_value($batiment, ['surface_habitable', 's_hab', 'surface_habitable_logement']) }}</div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">Surface emprise sol</div>
                                    <div class="dr-info-value">
                                        {{ dr_value($batiment, ['surface_emprise_sol', 's_emprise_sol', 'surface_emprise']) }}</div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">DPE</div>
                                    <div class="dr-info-value">
                                        {{ dr_value($batiment, ['classe_dpe', 'dpe_classe', 'classe_bilan_dpe']) }}</div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">GES</div>
                                    <div class="dr-info-value">{{ dr_value($batiment, ['ges', 'classe_ges', 'ges_classe']) }}</div>
                                </div>

                                <div class="dr-info-item">
                                    <div class="dr-info-label">Type chauffage</div>
                                    <div class="dr-info-value">
                                        {{ dr_value($batiment, ['type_chauffage', 'chauffage', 'type_installation_chauffage']) }}</div>
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
                    <h2>🏢 Propriétaires / Entreprises liées BDNB</h2>

                    @forelse(($resultat['proprietaires_bdnb'] ?? []) as $proprietaire)
                        <div class="dr-badge">Propriétaire BDNB</div>

                        <div class="dr-info-grid">
                            <div class="dr-info-item">
                                <div class="dr-info-label">Dénomination BDNB</div>
                                <div class="dr-info-value">{{ $proprietaire['nom_bdnb'] ?? $proprietaire['nom'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Nom Pappers</div>
                                <div class="dr-info-value">{{ $proprietaire['nom'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIREN</div>
                                <div class="dr-info-value">{{ $proprietaire['siren'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIRET siège</div>
                                <div class="dr-info-value">{{ $proprietaire['siret'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Capital social</div>
                                <div class="dr-info-value">{{ $proprietaire['capital_social'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Forme juridique</div>
                                <div class="dr-info-value">{{ $proprietaire['forme_juridique'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Activité</div>
                                <div class="dr-info-value">{{ $proprietaire['activite'] ?? '-' }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Dirigeant principal</div>
                                <div class="dr-info-value">{{ $proprietaire['dirigeant_principal'] ?? '-' }}</div>
                            </div>
                        </div>

                        @if(!empty($proprietaire['url_pappers']))
                            <div style="margin-top:1rem;">
                                <a href="{{ $proprietaire['url_pappers'] }}" target="_blank" class="dr-btn">
                                    Voir sur Pappers →
                                </a>
                            </div>
                        @endif

                        @if(!$loop->last)
                            <hr class="dr-hr">
                        @endif
                    @empty
                        <div class="dr-empty">
                            Aucun propriétaire BDNB trouvé pour ce bâtiment.
                        </div>
                    @endforelse
                </div>








                <div class="dr-card">
                    <h2>🏘️ Copropriétés RNIC liées à cette adresse</h2>

                    @forelse(($resultat['coproprietes'] ?? []) as $copro)
                        <div class="dr-badge">
                            Copropriété RNIC — Score {{ dr_value($copro, ['score_match'], '-') }}
                        </div>

                        <div class="dr-info-grid">
                            <div class="dr-info-item">
                                <div class="dr-info-label">Nom résidence</div>
                                <div class="dr-info-value">{{ dr_value($copro, ['nom_copropriete', 'nom_usage_copropriete']) }}
                                </div>
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
                                <div class="dr-info-value">{{ dr_value($copro, ['numero_immatriculation']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIREN copropriété</div>
                                <div class="dr-info-value">{{ dr_value($copro, ['siren_copropriete']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Lots total</div>
                                <div class="dr-info-value">{{ dr_value($copro, ['nombre_lots_total']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Lots habitation</div>
                                <div class="dr-info-value">{{ dr_value($copro, ['nombre_lots_habitation']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Représentant légal</div>
                                <div class="dr-info-value">
                                    @php
                                        $coproRepNom = dr_value($copro, ['representant_legal_nom', 'syndic_nom'], null);
                                        $coproRepConnu = !empty($coproRepNom)
                                            || !empty(dr_value($copro, ['siren_syndic'], null))
                                            || !empty(dr_value($copro, ['siret_syndic'], null));
                                    @endphp

                                    {{ $coproRepConnu ? ($coproRepNom ?: 'Représentant légal connu') : dr_value($copro, ['message_representant'], 'Pas de représentant légal connu') }}
                                </div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Type syndic</div>
                                <div class="dr-info-value">{{ dr_value($copro, ['representant_legal_type', 'type_syndic']) }}</div>
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

                        @if (!$loop->last)
                            <hr class="dr-hr">
                        @endif
                    @empty
                        <div class="dr-empty">
                            Aucune copropriété RNIC trouvée pour cette adresse.
                        </div>
                    @endforelse
                </div>

                <div class="dr-card">
                    <h2>🏢 Syndics / Entreprises associées</h2>

                    @forelse($syndicsAffiches as $syndic)
                        <div class="dr-badge">Entreprise syndic</div>

                        <div class="dr-info-grid">
                            <div class="dr-info-item">
                                <div class="dr-info-label">Nom syndic</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['nom']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIREN</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['siren']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">SIRET</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['siret']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Forme juridique</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['forme_juridique']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Activité</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['activite']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Capital social</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['capital_social']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Chiffre d’affaires</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['chiffre_affaires']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Résultat</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['resultat']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Effectif</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['effectif']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Date création</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['date_creation']) }}</div>
                            </div>

                            <div class="dr-info-item">
                                <div class="dr-info-label">Dirigeant principal</div>
                                <div class="dr-info-value">{{ dr_value($syndic, ['dirigeant_principal']) }}</div>
                            </div>
                        </div>

                        @if (dr_value($syndic, ['pappers_link'], null))
                            <div style="margin-top:1rem;">
                                <a href="{{ dr_value($syndic, ['pappers_link']) }}" target="_blank" class="dr-btn">
                                    Voir sur Pappers →
                                </a>
                            </div>
                        @endif

                        <details>
                            <summary>Voir données brutes Sirene / Pappers</summary>
                            <pre>{{ json_encode(is_object($syndic) ? ($syndic->raw_data ?? []) : ($syndic['raw_data'] ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>

                        @if (!$loop->last)
                            <hr class="dr-hr">
                        @endif
                    @empty
                        <div class="dr-empty">
                            Aucun syndic trouvé pour le moment.
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