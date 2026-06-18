@extends('front.layouts.app')

@section('title', 'Data Rocket - Rapport immobilier')

@section('content')

@php
/**
 * dr_value() — NOUVELLE PRIORITÉ
 * 1. D'ABORD raw_data (donnée brute du CSV RNIC, source de vérité)
 * 2. EN FALLBACK colonne réelle Eloquent/array, seulement si raw_data
 *    est absent ou vide pour cette clé
 */
function dr_value($model, array $keys, $default = '-')
{
    $raw = is_object($model)
        ? ($model->raw_data ?? [])
        : ($model['raw_data'] ?? []);

    if (is_string($raw)) {
        $raw = json_decode($raw, true) ?: [];
    }

    $rawNested = [];
    if (is_array($raw) && isset($raw['raw_data'])) {
        $rawNested = $raw['raw_data'];

        if (is_string($rawNested)) {
            $rawNested = json_decode($rawNested, true) ?: [];
        }
    }

    foreach ($keys as $key) {

        // PRIORITÉ 1 : raw_data imbriqué (RNIC réel)
        if (
            is_array($rawNested)
            && array_key_exists($key, $rawNested)
            && $rawNested[$key] !== null
            && $rawNested[$key] !== ''
        ) {
            return $rawNested[$key];
        }

        // PRIORITÉ 2 : raw_data niveau 1
        if (
            is_array($raw)
            && array_key_exists($key, $raw)
            && $raw[$key] !== null
            && $raw[$key] !== ''
        ) {
            return $raw[$key];
        }

        // PRIORITÉ 3 : colonne Eloquent
        if (
            is_object($model)
            && isset($model->{$key})
            && $model->{$key} !== null
            && $model->{$key} !== ''
        ) {
            return $model->{$key};
        }

        // PRIORITÉ 4 : tableau
        if (
            is_array($model)
            && array_key_exists($key, $model)
            && $model[$key] !== null
            && $model[$key] !== ''
        ) {
            return $model[$key];
        }
    }

    return $default;
}

/**
 * Détecte les valeurs "placeholder" du CSV RNIC qui signifient en
 * réalité une absence de donnée (ex: "non connu", "inconnu", "-").
 */
function dr_is_placeholder($value): bool
{
    if ($value === null) return true;
    $normalized = \Illuminate\Support\Str::ascii(mb_strtolower(trim((string) $value)));
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
    $normalized = trim($normalized);

    return in_array($normalized, [
        'non connu', 'non connue',
        'non renseigne', 'non renseignee',
        'non communique', 'non communiquee',
        'non disponible',
        'inconnu', 'inconnue',
        'n a', 'na', 'nc',
        '-', '--', '',
    ], true);
}

/**
 * Comme dr_value() mais ignore les valeurs placeholder.
 */
function dr_value_real($model, array $keys, $default = null)
{
    $value = dr_value($model, $keys, $default);
    return dr_is_placeholder($value) ? $default : $value;
}

$copros = collect($resultat['coproprietes'] ?? []);

// ════════════════════════════════════════════════════════════
// CLÉS RÉELLES DU CSV RNIC (vérifiées sur raw_data) :
//   - representant_legal_nom    → raison_sociale_representant_legal
//   - identification_representant_legal (existe aussi tel quel)
//   - siren_syndic              → siren_representant_legal
//   - siret_syndic              → siret_representant_legal
//   - representant_legal_type   → type_syndic
//   - nom_copropriete           → nom_usage_copropriete
//   - code_postal               → code_postal_adresse
//   - ville                     → commune_adresse / nom_officiel_commune
// Les anciennes clés (representant_legal_nom, syndic_nom, siren_syndic,
// siret_syndic) sont conservées en fallback dans les listes au cas où
// une colonne réelle Eloquent les utilise encore.
// ════════════════════════════════════════════════════════════

$coprosAvecRep = $copros->filter(function ($copro) {
    $hasRepNom = !empty(dr_value_real($copro, [
        'raison_sociale_representant_legal',
        'identification_representant_legal',
        'representant_legal_nom',
        'syndic_nom',
    ]));
    $hasSiren = !empty(dr_value_real($copro, ['siren_representant_legal', 'siren_syndic']));
    $hasSiret = !empty(dr_value_real($copro, ['siret_representant_legal', 'siret_syndic']));
    return $hasRepNom || $hasSiren || $hasSiret;
});

$coproPrincipale = $coprosAvecRep
    ->sortByDesc(fn($c) => (int) dr_value($c, ['score_match'], 0))
    ->first()
    ?? $copros
        ->filter(fn($c) => dr_value($c, ['numero_immatriculation'], null) !== null)
        ->sortByDesc(fn($c) => (int) dr_value($c, ['score_match'], 0))
        ->first()
    ?? $copros->first();

$coprosImmatriculees = $copros->filter(
    fn($c) => dr_value($c, ['numero_immatriculation'], null) !== null
);
$nbImmatriculations = $coprosImmatriculees->count();
$hasMultipleImmat   = $nbImmatriculations > 1;

$adresseEnregistree = !empty($coproPrincipale);
$representantNom    = $coproPrincipale
    ? dr_value_real($coproPrincipale, [
        'raison_sociale_representant_legal',
        'identification_representant_legal',
        'representant_legal_nom',
        'syndic_nom',
      ])
    : null;
$sirenSyndic = $coproPrincipale ? dr_value_real($coproPrincipale, ['siren_representant_legal', 'siren_syndic']) : null;
$siretSyndic = $coproPrincipale ? dr_value_real($coproPrincipale, ['siret_representant_legal', 'siret_syndic']) : null;
$representantConnu = $adresseEnregistree && (!empty($representantNom) || !empty($sirenSyndic) || !empty($siretSyndic));

if (!$representantConnu && $copros->isNotEmpty()) {
    $anyRepNom = $copros->filter(fn($c) => !empty(dr_value_real($c, ['raison_sociale_representant_legal', 'identification_representant_legal', 'representant_legal_nom', 'syndic_nom'])))->first();
    $anySiren  = $copros->filter(fn($c) => !empty(dr_value_real($c, ['siren_representant_legal', 'siren_syndic'])))->first();
    if ($anyRepNom || $anySiren) {
        $representantConnu = true;
        if (!$representantNom && $anyRepNom) $representantNom = dr_value_real($anyRepNom, ['raison_sociale_representant_legal', 'identification_representant_legal', 'representant_legal_nom', 'syndic_nom']);
        if (!$sirenSyndic   && $anySiren)   $sirenSyndic     = dr_value_real($anySiren, ['siren_representant_legal', 'siren_syndic']);
    }
}

// ── Statut mandat ─────────────────────────────────────
$coproRawModel = is_array($coproPrincipale)
    ? ($coproPrincipale['raw_data'] ?? [])
    : (is_object($coproPrincipale) ? ($coproPrincipale->raw_data ?? []) : []);
if (is_string($coproRawModel)) $coproRawModel = json_decode($coproRawModel, true) ?: [];
$coproRawModel = is_array($coproRawModel) ? $coproRawModel : [];

$mandatEnCours = $coproRawModel['mandat_en_cours'] ?? null;

if (!$mandatEnCours) {
    $statutColonne = is_array($coproPrincipale)
        ? ($coproPrincipale['statut'] ?? null)
        : (is_object($coproPrincipale) ? ($coproPrincipale->statut ?? null) : null);
    if ($statutColonne) $mandatEnCours = $statutColonne;
}

$dateFinMandat = $coproRawModel['date_fin_dernier_mandat'] ?? null;

$mandatLower = \Illuminate\Support\Str::ascii(mb_strtolower($mandatEnCours ?? ''));

// "Mandat expiré" couvre maintenant 3 libellés possibles du CSV RNIC :
//   - contient "expir"
//   - contient "sans successeur"
//   - exactement "pas de mandat en cours" AVEC une date_fin_dernier_mandat renseignée
//     (= il y a EU un syndic, son mandat a pris fin, pas de successeur déclaré —
//      différent de "jamais eu de syndic")
$mandatExpire = str_contains($mandatLower, 'expir')
    || str_contains($mandatLower, 'sans successeur')
    || (trim($mandatLower) === 'pas de mandat en cours' && !empty($dateFinMandat));

if ($mandatExpire && empty($representantNom)) {
    $raisonSociale = $coproRawModel['raison_sociale_representant_legal'] ?? null;
    if ($raisonSociale) $representantNom = $raisonSociale;
    if (!$siretSyndic) {
        $siretSyndic = $coproRawModel['siret_representant_legal'] ?? null;
    }
    if (!$sirenSyndic) {
        $sirenSyndic = $coproRawModel['siren_representant_legal'] ?? null;
    }
}

// ── Syndics ───────────────────────────────────────────
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

// ── QPV / ZFU ─────────────────────────────────────────
$qpv        = $resultat['qpv'] ?? null;
$qpvChecks  = collect($qpv['checks'] ?? []);
$hasQpv2024 = $qpvChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
$hasQpv2015 = $qpvChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
$hasZfu     = $qpvChecks->contains(fn($c) => $c['result']['zfu']     ?? false);
$hasAnyZone = $hasQpv2024 || $hasQpv2015 || $hasZfu;

// ── Compteurs ─────────────────────────────────────────
$batimentsCount     = count($resultat['batiments']          ?? []);
$cadastreCount      = count($resultat['cadastre']           ?? []);
$coprosCount        = count($resultat['coproprietes']       ?? []);
$syndicsCount       = $syndicsAffiches->count();
$proprietairesCount = count($resultat['proprietaires_bdnb'] ?? []);

// ── RNB ───────────────────────────────────────────────
$rnbData      = $resultat['rnb'] ?? null;
$rnbId        = null;
$rnbAddresses = collect();
$rnbStatus    = null;

if ($rnbData) {
    function extractRnbAddresses($data, &$addresses, &$rnbId, &$rnbStatus)
    {
        if (!is_array($data)) return;
        if (isset($data['rnb_id'])  && !$rnbId)    $rnbId    = $data['rnb_id'];
        if (isset($data['status']) && !$rnbStatus) $rnbStatus = $data['status'];
        if (isset($data['addresses']) && is_array($data['addresses'])) {
            foreach ($data['addresses'] as $addr) {
                $label = $addr['label'] ?? $addr['adresse']
                    ?? trim(collect([
                        $addr['street_number'] ?? null,
                        $addr['street_rep']    ?? null,
                        $addr['street']        ?? null,
                        $addr['city_zipcode']  ?? null,
                        $addr['city_name']     ?? null,
                    ])->filter()->implode(' '));
                if ($label) {
                    $addresses->push([
                        'adresse' => $label,
                        'cle_ban' => $addr['cle_interop_ban'] ?? $addr['cle_ban'] ?? $addr['id'] ?? null,
                        'id_ban'  => $addr['ban_id'] ?? $addr['id_ban'] ?? null,
                    ]);
                }
            }
        }
        foreach ($data as $value) {
            extractRnbAddresses($value, $addresses, $rnbId, $rnbStatus);
        }
    }
    extractRnbAddresses($rnbData, $rnbAddresses, $rnbId, $rnbStatus);
    $rnbAddresses = $rnbAddresses
        ->filter(fn($a) => !empty($a['adresse']) && $a['adresse'] !== '-')
        ->unique('adresse')
        ->values();
}
$rnbAddressesCount = $rnbAddresses->count();
@endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    :root {
        --dr-primary:    #0f172a;
        --dr-secondary:  #334155;
        --dr-muted:      #64748b;
        --dr-soft:       #f8fafc;
        --dr-border:     #e2e8f0;
        --dr-blue:       #0053b3;
        --dr-blue-dark:  #003d85;
        --dr-success:    #15803d;
        --dr-success-bg: #dcfce7;
        --dr-danger:     #b91c1c;
        --dr-danger-bg:  #fee2e2;
        --dr-warning:    #b45309;
        --dr-warning-bg: #fff7ed;
        --dr-white:      #ffffff;
    }
    .dr-page { background: radial-gradient(circle at top left, rgba(0,83,179,.08), transparent 35%), linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%); min-height:100vh; padding:32px 0 70px; color:var(--dr-primary); }
    .dr-container { max-width:1280px; margin:0 auto; padding:0 20px; }
    .dr-hero { display:grid; grid-template-columns:1.4fr 0.8fr; gap:22px; background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 60%,#0053b3 100%); color:white; border-radius:30px; padding:34px; margin-bottom:22px; position:relative; overflow:hidden; }
    .dr-hero:after { content:""; position:absolute; right:-120px; top:-120px; width:320px; height:320px; border-radius:50%; background:rgba(255,255,255,.08); }
    .dr-hero-kicker { display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.18); padding:8px 14px; border-radius:40px; font-size:13px; font-weight:700; margin-bottom:18px; }
    .dr-hero h1 { font-size:clamp(30px,4vw,46px); font-weight:900; margin:0 0 14px; letter-spacing:-.02em; }
    .dr-hero p  { color:rgba(255,255,255,.82); font-size:16px; line-height:1.6; max-width:550px; }
    .dr-hero-side { background:rgba(255,255,255,.1); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,.18); border-radius:24px; padding:22px; }
    .dr-side-label { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,.7); margin-bottom:6px; }
    .dr-side-value { font-size:18px; font-weight:800; margin-bottom:16px; word-break:break-word; }
    .dr-side-meta  { display:flex; flex-direction:column; gap:10px; border-top:1px solid rgba(255,255,255,.15); padding-top:14px; }
    .dr-side-meta span { display:flex; justify-content:space-between; font-size:13px; color:rgba(255,255,255,.8); }
    .dr-tabs { position:sticky; top:20px; z-index:100; display:flex; flex-wrap:wrap; gap:8px; background:rgba(255,255,255,.96); backdrop-filter:blur(12px); border:1px solid var(--dr-border); border-radius:60px; padding:8px 16px; margin-bottom:24px; box-shadow:0 8px 24px rgba(0,0,0,.06); }
    .dr-tab-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:40px; font-size:.8rem; font-weight:700; background:transparent; border:none; cursor:pointer; transition:all .2s; color:var(--dr-secondary); }
    .dr-tab-btn i { font-size:.9rem; }
    .dr-tab-btn:hover  { background:#e6f0ff; color:var(--dr-blue); }
    .dr-tab-btn.active { background:var(--dr-blue); color:white; box-shadow:0 2px 8px rgba(0,83,179,.3); }
    .dr-stats { display:grid; grid-template-columns:repeat(6,1fr); gap:16px; margin-bottom:24px; }
    .dr-stat-card { background:white; border:1px solid var(--dr-border); border-radius:20px; padding:18px; transition:all .2s; cursor:pointer; }
    .dr-stat-card:hover { transform:translateY(-2px); box-shadow:0 12px 24px rgba(0,0,0,.06); }
    .dr-stat-icon { width:44px; height:44px; background:#e6f0ff; border-radius:14px; display:flex; align-items:center; justify-content:center; color:var(--dr-blue); font-size:1.2rem; margin-bottom:12px; }
    .dr-stat-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.5px; color:var(--dr-muted); font-weight:800; margin-bottom:4px; }
    .dr-stat-value { font-size:1.6rem; font-weight:800; color:var(--dr-primary); }
    .dr-stat-value.success { color:var(--dr-success); }
    .dr-stat-value.danger  { color:var(--dr-danger); }
    .dr-panel { background:white; border:1px solid var(--dr-border); border-radius:26px; padding:28px; box-shadow:0 12px 32px rgba(0,0,0,.04); margin-bottom:24px; display:none; }
    .dr-panel.active { display:block; }
    .dr-panel-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid var(--dr-border); }
    .dr-panel-title { display:flex; gap:16px; align-items:flex-start; }
    .dr-panel-icon { width:52px; height:52px; background:#e6f0ff; border-radius:18px; display:flex; align-items:center; justify-content:center; color:var(--dr-blue); font-size:1.3rem; flex-shrink:0; }
    .dr-panel-title h2 { font-size:1.4rem; font-weight:800; color:var(--dr-primary); margin-bottom:4px; }
    .dr-panel-title p  { color:var(--dr-muted); font-size:.85rem; }
    .dr-status { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:40px; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px; }
    .dr-status.success { background:var(--dr-success-bg); color:var(--dr-success); }
    .dr-status.danger  { background:var(--dr-danger-bg);  color:var(--dr-danger); }
    .dr-status.warning { background:var(--dr-warning-bg); color:var(--dr-warning); }
    .dr-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
    .dr-field { background:var(--dr-soft); border:1px solid var(--dr-border); border-radius:16px; padding:14px; transition:all .2s; cursor:pointer; }
    .dr-field:hover { background:#f1f5f9; border-color:var(--dr-blue); }
    .dr-field-label { font-size:.65rem; text-transform:uppercase; letter-spacing:.5px; color:var(--dr-muted); font-weight:800; margin-bottom:6px; display:flex; justify-content:space-between; align-items:center; }
    .dr-field-label i { font-size:.7rem; color:var(--dr-muted); opacity:.6; }
    .dr-field-value { font-size:.9rem; font-weight:700; color:var(--dr-primary); word-break:break-word; }
    .dr-field-value code { background:var(--dr-border); padding:2px 6px; border-radius:8px; font-size:.75rem; }
    .dr-record { border:1px solid var(--dr-border); border-radius:20px; padding:18px; margin-top:16px; background:white; }
    .dr-record-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--dr-border); }
    .dr-record-title { font-size:1rem; font-weight:800; color:var(--dr-primary); }
    .dr-toast { position:fixed; bottom:30px; right:30px; background:#1e293b; color:white; padding:12px 24px; border-radius:12px; font-size:.85rem; font-weight:500; z-index:1000; opacity:0; transform:translateY(20px); transition:all .3s; pointer-events:none; box-shadow:0 4px 12px rgba(0,0,0,.15); }
    .dr-toast.show { opacity:1; transform:translateY(0); }
    .dr-toast i { margin-right:8px; color:#10b981; }
    .dr-empty { background:var(--dr-soft); border:1px dashed var(--dr-border); border-radius:18px; padding:24px; text-align:center; color:var(--dr-muted); }
    .dr-cta { background:linear-gradient(135deg,#0f172a 0%,#0053b3 100%); border-radius:28px; padding:40px; text-align:center; color:white; }
    .dr-cta h3 { font-size:1.8rem; font-weight:800; margin-bottom:12px; }
    .dr-cta p  { color:rgba(255,255,255,.8); margin-bottom:24px; }
    .dr-btn { display:inline-flex; align-items:center; gap:8px; padding:12px 28px; border-radius:40px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
    .dr-btn-primary { background:var(--dr-blue); color:white; }
    .dr-btn-primary:hover { background:var(--dr-blue-dark); transform:translateY(-2px); }
    .dr-btn-white { background:white; color:var(--dr-blue); }
    .dr-btn-white:hover { background:#eff6ff; transform:translateY(-2px); }
    details { margin-top:16px; }
    summary { cursor:pointer; color:var(--dr-blue); font-weight:700; font-size:.8rem; }
    pre { background:#0f172a; color:#e2e8f0; padding:16px; border-radius:14px; overflow-x:auto; font-size:.7rem; margin-top:12px; }
    .info-box { background:#f0f9ff; border:1px solid #b6d4fe; border-radius:20px; padding:16px; margin-top:24px; display:flex; gap:12px; align-items:flex-start; }
    .info-box i      { color:var(--dr-blue); font-size:1.2rem; }
    .info-box strong { color:var(--dr-blue); }
    .info-box a      { color:var(--dr-blue); text-decoration:underline; }
    .badge-multi-immat { display:inline-flex; align-items:center; gap:8px; background:#fef3c7; border:1.5px solid #f59e0b; color:#92400e; border-radius:40px; padding:6px 14px; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.4px; }
    .mandat-expire-banner { display:flex; align-items:flex-start; gap:16px; background:#fff7ed; border:2px solid #f59e0b; border-radius:20px; padding:20px 24px; margin-bottom:24px; }
    .mandat-expire-banner .icon  { font-size:2rem; color:#d97706; flex-shrink:0; margin-top:2px; }
    .mandat-expire-banner .title { font-size:1rem; font-weight:800; color:#92400e; margin-bottom:6px; }
    .mandat-expire-banner .detail { font-size:.85rem; color:#b45309; line-height:1.6; }
    .mandat-expire-banner .date-badge { display:inline-flex; align-items:center; gap:6px; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:20px; padding:4px 12px; font-size:.78rem; font-weight:800; margin-top:8px; }
    @media (max-width:960px) { .dr-hero { grid-template-columns:1fr; } .dr-stats { grid-template-columns:repeat(3,1fr); } .dr-tabs { overflow-x:auto; flex-wrap:nowrap; border-radius:20px; } .dr-tab-btn { white-space:nowrap; } }
    @media (max-width:640px) { .dr-container { padding:0 14px; } .dr-hero,.dr-panel,.dr-cta { padding:20px; } .dr-stats { grid-template-columns:repeat(2,1fr); } .dr-panel-header { flex-direction:column; } .dr-grid { grid-template-columns:1fr; } }
</style>

<section class="dr-page">
    <div class="dr-container">

        <div class="dr-hero">
            <div>
                <div class="dr-hero-kicker"><i class="fa-solid fa-chart-line"></i> Rapport d'analyse avancée</div>
                <h1>Rapport d'intelligence immobilière</h1>
                <p>Synthèse croisée BAN, Cadastre, BDNB, RNIC, QPV/ZFU, RNE et entreprises associées.</p>
            </div>
            <div class="dr-hero-side">
                <div class="dr-side-label">Adresse analysée</div>
                <div class="dr-side-value">{{ $q ?? '—' }}</div>
                <div class="dr-side-meta">
                    <span><strong>Ville</strong>       <span>{{ $adresse->ville       ?? '-' }}</span></span>
                    <span><strong>Code postal</strong> <span>{{ $adresse->code_postal ?? '-' }}</span></span>
                    <span><strong>Statut</strong>      <span>{{ empty($resultat['success']) ? 'Incomplet' : 'Analyse disponible' }}</span></span>
                </div>
            </div>
        </div>

        <div class="dr-tabs">
            <button class="dr-tab-btn active" data-tab="eligibilite"><i class="fa-solid fa-shield-halved"></i> Éligibilité</button>
            <button class="dr-tab-btn" data-tab="representant"><i class="fa-solid fa-user-tie"></i> Représentant</button>
            <button class="dr-tab-btn" data-tab="adresse"><i class="fa-solid fa-location-dot"></i> Adresse</button>
            <button class="dr-tab-btn" data-tab="rnb"><i class="fa-solid fa-diagram-project"></i> RNB</button>
            <button class="dr-tab-btn" data-tab="cadastre"><i class="fa-solid fa-map"></i> Cadastre</button>
            <button class="dr-tab-btn" data-tab="batiments"><i class="fa-solid fa-building"></i> Bâtiments</button>
            <button class="dr-tab-btn" data-tab="proprietaires"><i class="fa-solid fa-briefcase"></i> Propriétaires</button>
            <button class="dr-tab-btn" data-tab="coproprietes"><i class="fa-solid fa-city"></i> Copropriétés</button>
            <button class="dr-tab-btn" data-tab="syndics"><i class="fa-solid fa-landmark"></i> Syndics</button>
        </div>

        <div class="dr-stats">
            <div class="dr-stat-card" data-tab="eligibilite">
                <div class="dr-stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="dr-stat-label">QPV / ZFU</div>
                <div class="dr-stat-value {{ $hasAnyZone ? 'danger' : 'success' }}">{{ $hasAnyZone ? 'À exclure' : 'Exploitable' }}</div>
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
            <div class="dr-stat-card" data-tab="proprietaires">
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
                <a href="{{ route('front.home') }}#carte" class="dr-btn dr-btn-primary">
                    <i class="fa-solid fa-arrow-left"></i> Rechercher une autre adresse
                </a>
            </div>
        @else

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
                            <div class="dr-field-value" style="color:{{ $hasQpv2024 ? 'var(--dr-danger)' : 'var(--dr-success)' }}">
                                <i class="fa-solid {{ $hasQpv2024 ? 'fa-circle-xmark' : 'fa-circle-check' }}"></i>
                                {{ $hasQpv2024 ? 'Zone détectée' : 'Hors zone' }}
                            </div>
                        </div>
                        <div class="dr-field">
                            <div class="dr-field-label">QP 2015</div>
                            <div class="dr-field-value" style="color:{{ $hasQpv2015 ? 'var(--dr-danger)' : 'var(--dr-success)' }}">
                                <i class="fa-solid {{ $hasQpv2015 ? 'fa-circle-xmark' : 'fa-circle-check' }}"></i>
                                {{ $hasQpv2015 ? 'Zone détectée' : 'Hors zone' }}
                            </div>
                        </div>
                        <div class="dr-field">
                            <div class="dr-field-label">ZFU</div>
                            <div class="dr-field-value" style="color:{{ $hasZfu ? 'var(--dr-danger)' : 'var(--dr-success)' }}">
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
                            $result    = $check['result']    ?? [];
                            $hasZone   = ($result['qp_2024'] ?? false) || ($result['qp_2015'] ?? false) || ($result['zfu'] ?? false);
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
                                    <div class="dr-field-value">{{ $candidate['latitude'] ?? '-' }}, {{ $candidate['longitude'] ?? '-' }}</div>
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
                                            <span style="color:var(--dr-success);">Aucune zone QPV/ZFU</span>
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

        <div class="dr-panel" id="panel-representant">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <div>
                        <h2>Représentant légal</h2>
                        <p>Synthèse du syndic ou représentant issu du RNIC</p>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">

                    @if ($representantConnu && !$mandatExpire)
                        <div class="dr-status success">
                            <i class="fa-solid fa-circle-check"></i>
                            Avec représentant légal
                        </div>
                    @elseif ($mandatExpire)
                        <div class="dr-status warning">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            Mandat expiré
                        </div>
                    @else
                        <div class="dr-status danger">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            Pas de représentant légal
                        </div>
                    @endif

                    @if ($hasMultipleImmat)
                        <div class="badge-multi-immat">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            @if ($nbImmatriculations === 2)
                                Double immatriculation détectée
                            @else
                                {{ $nbImmatriculations }} immatriculations détectées
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="dr-panel-body">
                @if (!$adresseEnregistree)
                    <div class="dr-empty">Adresse non enregistrée dans le RNIC pour cette recherche.</div>
                @else

                    @if ($mandatExpire)
                        <div class="mandat-expire-banner">
                            <div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                            <div>
                                <div class="title">
                                    {{ $mandatEnCours ?? 'Mandat expiré sans successeur déclaré' }}
                                </div>
                                <div class="detail">
                                    @if ($representantNom)
                                        Le dernier syndic connu pour cette copropriété était
                                        <strong>{{ $representantNom }}</strong>.
                                        Son mandat n'a pas été renouvelé et aucun successeur n'a été déclaré au RNIC à ce jour.
                                    @else
                                        Cette copropriété n'a pas de représentant légal actif déclaré au RNIC.
                                        Le dernier mandat a expiré sans qu'un successeur soit désigné.
                                    @endif
                                </div>
                                @if ($dateFinMandat)
                                    <div class="date-badge">
                                        <i class="fa-solid fa-calendar-xmark"></i>
                                        Mandat expiré le {{ \Carbon\Carbon::parse($dateFinMandat)->format('d/m/Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($hasMultipleImmat)
                        <div style="background:#fff7ed; border:1.5px solid #f59e0b; border-radius:16px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:flex-start; gap:12px;">
                            <i class="fa-solid fa-triangle-exclamation" style="color:#b45309; margin-top:2px; flex-shrink:0;"></i>
                            <div>
                                <div style="font-weight:800; color:#92400e; font-size:.9rem; margin-bottom:4px;">
                                    @if ($nbImmatriculations === 2) Double immatriculation
                                    @else {{ $nbImmatriculations }} immatriculations @endif
                                    — plusieurs copropriétés pour cette adresse
                                </div>
                                <div style="font-size:.82rem; color:#b45309; line-height:1.5;">
                                    {{ $nbImmatriculations }} copropriétés immatriculées trouvées.
                                    Le représentant affiché est celui de la copropriété avec le meilleur score RNIC.
                                    Consultez l'onglet <strong>Copropriétés</strong> pour voir toutes les immatriculations.
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="dr-grid">
                        <div class="dr-field copyable" data-copy="{{ $adresse->adresse_complete ?? $q ?? '-' }}">
                            <div class="dr-field-label">Adresse contrôlée <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value">{{ $adresse->adresse_complete ?? $q ?? '-' }}</div>
                        </div>
                        <div class="dr-field copyable" data-copy="{{ dr_value($coproPrincipale, ['adresse_complete', 'adresse_reference']) }}">
                            <div class="dr-field-label">Adresse RNIC <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value">
                                {{ dr_value($coproPrincipale, ['adresse_complete', 'adresse_reference']) }}<br>
                                {{ dr_value($coproPrincipale, ['code_postal_adresse', 'code_postal']) }} {{ dr_value($coproPrincipale, ['commune_adresse', 'nom_officiel_commune', 'ville']) }}
                            </div>
                        </div>
                        <div class="dr-field copyable" data-copy="{{ $representantNom ?: '' }}">
                            <div class="dr-field-label">Nom représentant / syndic <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value">
                                {{ $representantNom ?: '-' }}
                                @if ($representantNom && $mandatExpire)
                                    <span style="display:block; font-size:.7rem; color:#b45309; font-weight:600; margin-top:4px;">
                                        <i class="fa-solid fa-clock-rotate-left"></i> Dernier syndic connu — mandat expiré
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="dr-field copyable" data-copy="{{ dr_value($coproPrincipale, ['type_syndic', 'representant_legal_type']) }}">
                            <div class="dr-field-label">Type représentant <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value">{{ dr_value($coproPrincipale, ['type_syndic', 'representant_legal_type']) }}</div>
                        </div>
                        <div class="dr-field copyable" data-copy="{{ $sirenSyndic ?: '' }}">
                            <div class="dr-field-label">SIREN syndic <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value"><code>{{ $sirenSyndic ?: '-' }}</code></div>
                        </div>
                        <div class="dr-field copyable" data-copy="{{ $siretSyndic ?: '' }}">
                            <div class="dr-field-label">SIRET syndic <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value"><code>{{ $siretSyndic ?: '-' }}</code></div>
                        </div>
                        <div class="dr-field copyable" data-copy="{{ dr_value($coproPrincipale, ['numero_immatriculation']) }}">
                            <div class="dr-field-label">Immatriculation <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value"><code>{{ dr_value($coproPrincipale, ['numero_immatriculation']) }}</code></div>
                        </div>
                        <div class="dr-field copyable" data-copy="{{ dr_value($coproPrincipale, ['nom_usage_copropriete', 'nom_copropriete']) }}">
                            <div class="dr-field-label">Nom résidence <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value">{{ dr_value($coproPrincipale, ['nom_usage_copropriete', 'nom_copropriete']) }}</div>
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

                    @if ($hasMultipleImmat)
                        <h3 style="margin:24px 0 12px; font-size:.95rem; font-weight:800; color:var(--dr-primary);">
                            <i class="fa-solid fa-list-check" style="color:var(--dr-blue);"></i>
                            Toutes les immatriculations ({{ $nbImmatriculations }})
                        </h3>
                        @foreach ($coprosImmatriculees as $idx => $c)
                            @php
                                $cRepNom = dr_value_real($c, ['raison_sociale_representant_legal', 'identification_representant_legal', 'representant_legal_nom', 'syndic_nom']);
                                $cSiren  = dr_value_real($c, ['siren_representant_legal', 'siren_syndic']);
                                $cHasRep = !empty($cRepNom) || !empty($cSiren);
                            @endphp
                            <div style="display:flex; align-items:center; gap:14px; background:{{ $cHasRep ? 'var(--dr-success-bg)' : 'var(--dr-soft)' }}; border:1px solid {{ $cHasRep ? '#86efac' : 'var(--dr-border)' }}; border-radius:14px; padding:12px 16px; margin-bottom:8px;">
                                <div style="font-size:1.2rem; color:{{ $cHasRep ? 'var(--dr-success)' : 'var(--dr-muted)' }}">
                                    <i class="fa-solid {{ $cHasRep ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:800; font-size:.85rem; color:var(--dr-primary);">
                                        {{ dr_value($c, ['nom_usage_copropriete', 'nom_copropriete'], 'Copropriété ' . ($idx+1)) }}
                                    </div>
                                    <div style="font-size:.75rem; color:var(--dr-muted); margin-top:2px;">
                                        N° {{ dr_value($c, ['numero_immatriculation'], '-') }}
                                        @if($cRepNom) — Représentant : {{ $cRepNom }} @endif
                                        @if($cSiren)  — SIREN : {{ $cSiren }} @endif
                                        @if(!$cHasRep) — Pas de représentant légal @endif
                                    </div>
                                </div>
                                <div style="font-size:.75rem; font-weight:800; color:{{ $cHasRep ? 'var(--dr-success)' : 'var(--dr-muted)' }}">
                                    Score {{ dr_value($c, ['score_match'], '-') }}
                                </div>
                            </div>
                        @endforeach
                    @endif

                @endif
            </div>
        </div>

        <div class="dr-panel" id="panel-adresse">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div><h2>Adresse normalisée</h2><p>Géocodage via la Base Adresse Nationale</p></div>
                </div>
            </div>
            <div class="dr-panel-body">
                <div class="dr-grid">
                    <div class="dr-field copyable" data-copy="{{ $adresse->adresse_complete ?? '-' }}">
                        <div class="dr-field-label">Adresse complète <i class="fa-regular fa-copy"></i></div>
                        <div class="dr-field-value">{{ $adresse->adresse_complete ?? '-' }}</div>
                    </div>
                    <div class="dr-field copyable" data-copy="{{ ($adresse->code_postal ?? '') . ' ' . ($adresse->ville ?? '') }}">
                        <div class="dr-field-label">Code postal / Ville <i class="fa-regular fa-copy"></i></div>
                        <div class="dr-field-value">{{ $adresse->code_postal ?? '' }} {{ $adresse->ville ?? '' }}</div>
                    </div>
                    <div class="dr-field copyable" data-copy="{{ $adresse->code_insee ?? '-' }}">
                        <div class="dr-field-label">Code INSEE <i class="fa-regular fa-copy"></i></div>
                        <div class="dr-field-value"><code>{{ $adresse->code_insee ?? '-' }}</code></div>
                    </div>
                    <div class="dr-field copyable" data-copy="{{ ($adresse->latitude ?? '-') . ', ' . ($adresse->longitude ?? '-') }}">
                        <div class="dr-field-label">Coordonnées GPS <i class="fa-regular fa-copy"></i></div>
                        <div class="dr-field-value">{{ $adresse->latitude ?? '-' }}, {{ $adresse->longitude ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dr-panel" id="panel-rnb">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-diagram-project"></i></div>
                    <div><h2>RNB — Référentiel National des Bâtiments</h2><p>Identifiant unique du bâtiment et toutes les adresses associées</p></div>
                </div>
                @if ($rnbId)
                    <div class="dr-status success"><i class="fa-solid fa-check-circle"></i> {{ $rnbAddressesCount }} adresse(s) associée(s)</div>
                @endif
            </div>
            <div class="dr-panel-body">
                @if ($rnbId)
                    <div class="dr-grid">
                        <div class="dr-field copyable" data-copy="{{ $rnbId }}">
                            <div class="dr-field-label">Identifiant RNB <i class="fa-regular fa-copy"></i></div>
                            <div class="dr-field-value"><code style="font-size:1rem;">{{ $rnbId }}</code></div>
                        </div>
                        <div class="dr-field">
                            <div class="dr-field-label">Statut du bâtiment</div>
                            <div class="dr-field-value"><span class="dr-status success" style="display:inline-flex;"><i class="fa-solid fa-check-circle"></i> Construit</span></div>
                        </div>
                        <div class="dr-field">
                            <div class="dr-field-label">Nombre d'adresses associées</div>
                            <div class="dr-field-value">{{ $rnbAddressesCount }}</div>
                        </div>
                    </div>
                    @if ($rnbAddressesCount > 0)
                        <h3 style="margin:28px 0 16px;font-size:1rem;font-weight:800;color:var(--dr-primary);"><i class="fa-solid fa-list"></i> Toutes les adresses liées à ce RNB</h3>
                        <div class="dr-grid">
                            @foreach ($rnbAddresses as $addrInfo)
                                <div class="dr-record" style="margin-top:0;">
                                    <div class="dr-record-header" style="margin-bottom:12px;padding-bottom:8px;">
                                        <div class="dr-record-title"><i class="fa-solid fa-location-dot" style="color:var(--dr-blue);"></i> Adresse associée</div>
                                    </div>
                                    <div class="dr-grid" style="grid-template-columns:1fr;">
                                        <div class="dr-field copyable" data-copy="{{ $addrInfo['adresse'] }}">
                                            <div class="dr-field-label">Adresse complète <i class="fa-regular fa-copy"></i></div>
                                            <div class="dr-field-value"><strong>{{ $addrInfo['adresse'] }}</strong></div>
                                        </div>
                                        @if (!empty($addrInfo['cle_ban']))
                                            <div class="dr-field copyable" data-copy="{{ $addrInfo['cle_ban'] }}">
                                                <div class="dr-field-label">Clé BAN <i class="fa-regular fa-copy"></i></div>
                                                <div class="dr-field-value"><code>{{ $addrInfo['cle_ban'] }}</code></div>
                                            </div>
                                        @endif
                                        @if (!empty($addrInfo['id_ban']))
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
                        <div><strong>Améliorez le RNB</strong><br>Il manque un bâtiment ? Une adresse semble erronée ? <a href="#" target="_blank">Envoyez votre signalement</a> — tout le monde peut apporter sa pierre au RNB.</div>
                    </div>
                @else
                    <div class="dr-empty">
                        <i class="fa-solid fa-diagram-project"></i> Aucun identifiant RNB trouvé pour cette adresse.<br>
                        <span style="font-size:.8rem;">Le RNB est en cours de construction par l'ANCT et l'IGN.</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="dr-panel" id="panel-cadastre">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-map"></i></div>
                    <div><h2>Cadastre & parcelles</h2><p>Parcelles cadastrales détectées autour des coordonnées</p></div>
                </div>
            </div>
            <div class="dr-panel-body">
                @forelse (($resultat['cadastre'] ?? []) as $parcelle)
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
                            <div class="dr-field copyable" data-copy="{{ ($parcelle['section'] ?? '-') . ' / ' . ($parcelle['numero'] ?? '-') }}">
                                <div class="dr-field-label">Section / Numéro <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value">{{ $parcelle['section'] ?? '-' }} / {{ $parcelle['numero'] ?? '-' }}</div>
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

        <div class="dr-panel" id="panel-batiments">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-building"></i></div>
                    <div><h2>Bâtiments détectés</h2><p>Données BDNB — Base de Données Nationale des Bâtiments</p></div>
                </div>
            </div>
            <div class="dr-panel-body">
                @forelse (($resultat['batiments'] ?? []) as $batiment)
                    <div class="dr-record">
                        <div class="dr-record-header">
                            <div class="dr-record-title">Bâtiment BDNB</div>
                            <div class="dr-status success"><i class="fa-solid fa-database"></i> BDNB</div>
                        </div>
                        <div class="dr-grid">
                            <div class="dr-field copyable" data-copy="{{ dr_value($batiment, ['identifiant_bdnb']) }}">
                                <div class="dr-field-label">Identifiant BDNB <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value"><code>{{ dr_value($batiment, ['identifiant_bdnb']) }}</code></div>
                            </div>
                            <div class="dr-field"><div class="dr-field-label">Type bâtiment</div><div class="dr-field-value">{{ dr_value($batiment, ['type_batiment']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Année construction</div><div class="dr-field-value">{{ dr_value($batiment, ['annee_construction']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Nombre logements</div><div class="dr-field-value">{{ dr_value($batiment, ['nombre_logements']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Nombre niveaux</div><div class="dr-field-value">{{ dr_value($batiment, ['nombre_niveaux']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Hauteur</div><div class="dr-field-value">{{ dr_value($batiment, ['hauteur']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Surface habitable</div><div class="dr-field-value">{{ dr_value($batiment, ['surface_habitable']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Emprise sol</div><div class="dr-field-value">{{ dr_value($batiment, ['surface_emprise_sol']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">DPE</div><div class="dr-field-value">{{ dr_value($batiment, ['classe_dpe']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">GES</div><div class="dr-field-value">{{ dr_value($batiment, ['ges']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Type chauffage principal</div><div class="dr-field-value">{{ dr_value($batiment, ['type_chauffage','chauffage_principal','type_installation_chauffage']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Énergie de chauffage</div><div class="dr-field-value">{{ dr_value($batiment, ['energie_chauffage','energie_principale_chauffage','l_ch_princ_energie']) }}</div></div>
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

        <div class="dr-panel" id="panel-proprietaires">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <div><h2>Propriétaires & entreprises BDNB</h2><p>Dénominations et SIREN détectés dans les données BDNB — enrichis via RNE local</p></div>
                </div>
            </div>
            <div class="dr-panel-body">
                @forelse (($resultat['proprietaires_bdnb'] ?? []) as $proprietaire)
                    <div class="dr-record">
                        <div class="dr-record-header">
                            <div class="dr-record-title">{{ $proprietaire['nom_bdnb'] ?? $proprietaire['nom'] ?? 'Propriétaire BDNB' }}</div>
                            <div class="dr-status success"><i class="fa-solid fa-building-circle-check"></i> Entreprise</div>
                        </div>
                        <div class="dr-grid">
                            <div class="dr-field copyable" data-copy="{{ $proprietaire['nom_bdnb'] ?? $proprietaire['nom'] ?? '-' }}"><div class="dr-field-label">Dénomination BDNB <i class="fa-regular fa-copy"></i></div><div class="dr-field-value">{{ $proprietaire['nom_bdnb'] ?? $proprietaire['nom'] ?? '-' }}</div></div>
                            <div class="dr-field copyable" data-copy="{{ $proprietaire['siren'] ?? '-' }}"><div class="dr-field-label">SIREN <i class="fa-regular fa-copy"></i></div><div class="dr-field-value"><code>{{ $proprietaire['siren'] ?? '-' }}</code></div></div>
                            <div class="dr-field copyable" data-copy="{{ $proprietaire['siret'] ?? '-' }}"><div class="dr-field-label">SIRET siège <i class="fa-regular fa-copy"></i></div><div class="dr-field-value"><code>{{ $proprietaire['siret'] ?? '-' }}</code></div></div>
                            <div class="dr-field"><div class="dr-field-label">Capital social</div><div class="dr-field-value">{{ $proprietaire['capital_social'] ?? '-' }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Forme juridique</div><div class="dr-field-value">{{ $proprietaire['forme_juridique'] ?? '-' }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Dirigeant principal</div><div class="dr-field-value">{{ $proprietaire['dirigeant_principal'] ?? '-' }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Activité</div><div class="dr-field-value">{{ $proprietaire['activite'] ?? '-' }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Chiffre d'affaires</div><div class="dr-field-value">{{ $proprietaire['chiffre_affaires'] ?? '-' }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Effectif</div><div class="dr-field-value">{{ $proprietaire['effectif'] ?? '-' }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Date de création</div><div class="dr-field-value">{{ $proprietaire['date_creation'] ?? '-' }}</div></div>
                            <div class="dr-field">
                                <div class="dr-field-label">Source données</div>
                                <div class="dr-field-value">
                                    @php $src = $proprietaire['source'] ?? ''; @endphp
                                    @if ($src === 'rne_local') <span class="dr-status success" style="display:inline-flex;font-size:11px;"><i class="fa-solid fa-database"></i> RNE local</span>
                                    @elseif ($src === 'bdnb_only') <span class="dr-status warning" style="display:inline-flex;font-size:11px;"><i class="fa-solid fa-building"></i> BDNB uniquement</span>
                                    @else <span class="dr-status warning" style="display:inline-flex;font-size:11px;"><i class="fa-solid fa-circle-info"></i> {{ $src ?: 'Non enrichi' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if (!empty($proprietaire['url_pappers']))
                            <div style="margin-top:16px;">
                                <a href="{{ $proprietaire['url_pappers'] }}" target="_blank" class="dr-btn dr-btn-primary">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la fiche externe
                                </a>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="dr-empty">Aucun propriétaire BDNB trouvé.</div>
                @endforelse
            </div>
        </div>

        <div class="dr-panel" id="panel-coproprietes">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-city"></i></div>
                    <div><h2>Copropriétés RNIC</h2><p>Registre National d'Immatriculation des Copropriétés</p></div>
                </div>
                @if ($hasMultipleImmat)
                    <div class="badge-multi-immat"><i class="fa-solid fa-triangle-exclamation"></i> {{ $nbImmatriculations }} immatriculations</div>
                @endif
            </div>
            <div class="dr-panel-body">
                @forelse (($resultat['coproprietes'] ?? []) as $copro)
                    @php
                        $coproRepNom   = dr_value_real($copro, ['raison_sociale_representant_legal', 'identification_representant_legal', 'representant_legal_nom', 'syndic_nom']);
                        $coproRepConnu = !empty($coproRepNom)
                            || !empty(dr_value_real($copro, ['siren_representant_legal', 'siren_syndic']))
                            || !empty(dr_value_real($copro, ['siret_representant_legal', 'siret_syndic']));
                        $coproRawData  = $copro['raw_data'] ?? ($copro->raw_data ?? null);
                        if (is_string($coproRawData)) $coproRawData = json_decode($coproRawData, true) ?: [];
                        $coproMandat  = is_array($coproRawData) ? ($coproRawData['mandat_en_cours'] ?? null) : null;
                        if (!$coproMandat) $coproMandat = $copro['statut'] ?? ($copro->statut ?? null);
                        $coproDateFin = is_array($coproRawData) ? ($coproRawData['date_fin_dernier_mandat'] ?? null) : null;
                        $coproMandatLower  = \Illuminate\Support\Str::ascii(mb_strtolower($coproMandat ?? ''));
                        $coproMandatExpire = str_contains($coproMandatLower, 'expir')
                            || str_contains($coproMandatLower, 'sans successeur')
                            || (trim($coproMandatLower) === 'pas de mandat en cours' && !empty($coproDateFin));
                    @endphp
                    <div class="dr-record">
                        <div class="dr-record-header">
                            <div class="dr-record-title">{{ dr_value($copro, ['nom_usage_copropriete', 'nom_copropriete']) }}</div>
                            <div class="dr-status {{ $coproRepConnu ? 'success' : 'warning' }}">
                                <i class="fa-solid {{ $coproRepConnu ? 'fa-circle-check' : ($coproMandatExpire ? 'fa-clock-rotate-left' : 'fa-circle-info') }}"></i>
                                Score {{ dr_value($copro, ['score_match'], '-') }}
                            </div>
                        </div>
                        <div class="dr-grid">
                            <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['adresse_complete', 'adresse_reference']) }}">
                                <div class="dr-field-label">Adresse RNIC <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value">{{ dr_value($copro, ['adresse_complete', 'adresse_reference']) }}<br>{{ dr_value($copro, ['code_postal_adresse', 'code_postal']) }} {{ dr_value($copro, ['commune_adresse', 'nom_officiel_commune', 'ville']) }}</div>
                            </div>
                            <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['numero_immatriculation']) }}">
                                <div class="dr-field-label">Immatriculation <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value"><code>{{ dr_value($copro, ['numero_immatriculation']) }}</code></div>
                            </div>
                            <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['siren_copropriete']) }}">
                                <div class="dr-field-label">SIREN copropriété <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value"><code>{{ dr_value($copro, ['siren_copropriete']) }}</code></div>
                            </div>
                            <div class="dr-field"><div class="dr-field-label">Lots habitation</div><div class="dr-field-value">{{ dr_value($copro, ['nombre_lots_habitation']) }}</div></div>
                            <div class="dr-field">
                                <div class="dr-field-label">Représentant légal</div>
                                <div class="dr-field-value">
                                    @if ($coproRepConnu)
                                        {{ $coproRepNom ?: 'Représentant légal connu' }}
                                    @elseif ($coproMandatExpire)
                                        <span style="color:#d97706;">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                            Mandat expiré@if ($coproDateFin) le {{ \Carbon\Carbon::parse($coproDateFin)->format('d/m/Y') }}@endif
                                        </span>
                                    @else
                                        {{ dr_value($copro, ['message_representant'], 'Pas de représentant légal connu') }}
                                    @endif
                                </div>
                            </div>
                            <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['siren_representant_legal', 'siren_syndic']) }}">
                                <div class="dr-field-label">SIREN syndic <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value"><code>{{ dr_value($copro, ['siren_representant_legal', 'siren_syndic']) }}</code></div>
                            </div>
                            <div class="dr-field copyable" data-copy="{{ dr_value($copro, ['siret_representant_legal', 'siret_syndic']) }}">
                                <div class="dr-field-label">SIRET syndic <i class="fa-regular fa-copy"></i></div>
                                <div class="dr-field-value"><code>{{ dr_value($copro, ['siret_representant_legal', 'siret_syndic']) }}</code></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="dr-empty">Aucune copropriété RNIC trouvée.</div>
                @endforelse
            </div>
        </div>

        <div class="dr-panel" id="panel-syndics">
            <div class="dr-panel-header">
                <div class="dr-panel-title">
                    <div class="dr-panel-icon"><i class="fa-solid fa-landmark"></i></div>
                    <div><h2>Syndics & entreprises associées</h2><p>Informations INPI RNE des syndics et représentants légaux</p></div>
                </div>
            </div>
            <div class="dr-panel-body">
                @forelse ($syndicsAffiches as $syndic)
                    <div class="dr-record">
                        <div class="dr-record-header">
                            <div class="dr-record-title">{{ dr_value($syndic, ['nom']) }}</div>
                            <div class="dr-status success"><i class="fa-solid fa-id-card"></i> SIREN {{ dr_value($syndic, ['siren']) }}</div>
                        </div>
                        <div class="dr-grid">
                            <div class="dr-field copyable" data-copy="{{ dr_value($syndic, ['nom']) }}"><div class="dr-field-label">Nom syndic <i class="fa-regular fa-copy"></i></div><div class="dr-field-value">{{ dr_value($syndic, ['nom']) }}</div></div>
                            <div class="dr-field copyable" data-copy="{{ dr_value($syndic, ['siren']) }}"><div class="dr-field-label">SIREN <i class="fa-regular fa-copy"></i></div><div class="dr-field-value"><code>{{ dr_value($syndic, ['siren']) }}</code></div></div>
                            <div class="dr-field copyable" data-copy="{{ dr_value($syndic, ['siret']) }}"><div class="dr-field-label">SIRET <i class="fa-regular fa-copy"></i></div><div class="dr-field-value"><code>{{ dr_value($syndic, ['siret']) }}</code></div></div>
                            <div class="dr-field"><div class="dr-field-label">Forme juridique</div><div class="dr-field-value">{{ dr_value($syndic, ['forme_juridique']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Capital social</div><div class="dr-field-value">{{ dr_value($syndic, ['capital_social']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Chiffre d'affaires / Résultat</div><div class="dr-field-value">{{ dr_value($syndic, ['chiffre_affaires']) }} / {{ dr_value($syndic, ['resultat']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Effectif</div><div class="dr-field-value">{{ dr_value($syndic, ['effectif']) }}</div></div>
                            <div class="dr-field"><div class="dr-field-label">Dirigeant principal</div><div class="dr-field-value">{{ dr_value($syndic, ['dirigeant_principal']) }}</div></div>
                        </div>
                        @if (dr_value($syndic, ['url_pappers'], null) !== '-')
                            <div style="margin-top:16px;">
                                <a href="{{ dr_value($syndic, ['url_pappers']) }}" target="_blank" class="dr-btn dr-btn-primary">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la fiche externe
                                </a>
                            </div>
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

        <div class="dr-cta">
            <h3><i class="fa-solid fa-rocket"></i> Analyse immobilière enrichie</h3>
            <p>Continuez à exploiter les données adresse, cadastre, BDNB, RNIC, SIREN/SIRET et QPV/ZFU.</p>
            <a href="{{ route('front.home') }}#carte" class="dr-btn dr-btn-white">
                <i class="fa-solid fa-magnifying-glass"></i> Rechercher une autre adresse
            </a>
        </div>

        @endif
    </div>
</section>

<div id="copyToast" class="dr-toast"><i class="fa-solid fa-check-circle"></i> Copié dans le presse-papier</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabBtns   = document.querySelectorAll('.dr-tab-btn');
        const panels    = document.querySelectorAll('.dr-panel');
        const statCards = document.querySelectorAll('.dr-stat-card');

        function activateTab(tabId) {
            panels.forEach(p => p.classList.remove('active'));
            const target = document.getElementById('panel-' + tabId);
            if (target) target.classList.add('active');
            tabBtns.forEach(b => {
                b.classList.remove('active');
                if (b.getAttribute('data-tab') === tabId) b.classList.add('active');
            });
            localStorage.setItem('activeReportTab', tabId);
        }

        tabBtns.forEach(btn => btn.addEventListener('click', function () { activateTab(this.getAttribute('data-tab')); }));
        statCards.forEach(card => card.addEventListener('click', function () { const t = this.getAttribute('data-tab'); if (t) activateTab(t); }));

        const lastTab = localStorage.getItem('activeReportTab');
        if (lastTab && document.querySelector(`.dr-tab-btn[data-tab="${lastTab}"]`)) activateTab(lastTab);

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