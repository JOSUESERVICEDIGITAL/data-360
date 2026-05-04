<div class="form-grid">
    <div class="form-group">
        <label>Adresse</label>
        <select name="adresse_id">
            <option value="">Aucune</option>
            @foreach($adresses as $adresse)
                <option value="{{ $adresse->id }}" @selected(old('adresse_id', $batiment->adresse_id ?? '') == $adresse->id)>
                    {{ $adresse->adresse_complete }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Type bâtiment</label>
        <select name="type_batiment" required>
            @foreach(['individuel','collectif','tertiaire','mixte','inconnu'] as $type)
                <option value="{{ $type }}" @selected(old('type_batiment', $batiment->type_batiment ?? 'inconnu') == $type)>
                    {{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group"><label>Identifiant BDNB</label><input name="identifiant_bdnb" value="{{ old('identifiant_bdnb', $batiment->identifiant_bdnb ?? '') }}"></div>
    <div class="form-group"><label>Identifiant cadastre</label><input name="identifiant_cadastre" value="{{ old('identifiant_cadastre', $batiment->identifiant_cadastre ?? '') }}"></div>
    <div class="form-group"><label>Année construction</label><input name="annee_construction" value="{{ old('annee_construction', $batiment->annee_construction ?? '') }}"></div>
    <div class="form-group"><label>Nombre logements</label><input name="nombre_logements" value="{{ old('nombre_logements', $batiment->nombre_logements ?? '') }}"></div>
    <div class="form-group"><label>Nombre niveaux</label><input name="nombre_niveaux" value="{{ old('nombre_niveaux', $batiment->nombre_niveaux ?? '') }}"></div>
    <div class="form-group"><label>Hauteur</label><input name="hauteur" value="{{ old('hauteur', $batiment->hauteur ?? '') }}"></div>
    <div class="form-group"><label>Surface habitable</label><input name="surface_habitable" value="{{ old('surface_habitable', $batiment->surface_habitable ?? '') }}"></div>
    <div class="form-group"><label>Surface emprise sol</label><input name="surface_emprise_sol" value="{{ old('surface_emprise_sol', $batiment->surface_emprise_sol ?? '') }}"></div>
    <div class="form-group"><label>DPE</label><input name="classe_dpe" value="{{ old('classe_dpe', $batiment->classe_dpe ?? '') }}"></div>
    <div class="form-group"><label>GES</label><input name="ges" value="{{ old('ges', $batiment->ges ?? '') }}"></div>
    <div class="form-group"><label>Type chauffage</label><input name="type_chauffage" value="{{ old('type_chauffage', $batiment->type_chauffage ?? '') }}"></div>
    <div class="form-group"><label>Énergie chauffage</label><input name="energie_chauffage" value="{{ old('energie_chauffage', $batiment->energie_chauffage ?? '') }}"></div>
    <div class="form-group"><label>Score opportunité</label><input name="score_opportunite" value="{{ old('score_opportunite', $batiment->score_opportunite ?? '') }}"></div>
</div>