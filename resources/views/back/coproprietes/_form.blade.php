<div class="form-grid">
    <div class="form-group">
        <label>Adresse</label>
        <select name="adresse_id">
            <option value="">Aucune</option>
            @foreach($adresses as $adresse)
                <option value="{{ $adresse->id }}" @selected(old('adresse_id', $copropriete->adresse_id ?? '') == $adresse->id)>
                    {{ $adresse->adresse_complete }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Bâtiment</label>
        <select name="batiment_id">
            <option value="">Aucun</option>
            @foreach($batiments as $batiment)
                <option value="{{ $batiment->id }}" @selected(old('batiment_id', $copropriete->batiment_id ?? '') == $batiment->id)>
                    #{{ $batiment->id }} — {{ $batiment->adresse->adresse_complete ?? 'Sans adresse' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Nom copropriété</label>
        <input name="nom_copropriete" value="{{ old('nom_copropriete', $copropriete->nom_copropriete ?? '') }}">
    </div>

    <div class="form-group">
        <label>Numéro immatriculation</label>
        <input name="numero_immatriculation" value="{{ old('numero_immatriculation', $copropriete->numero_immatriculation ?? '') }}">
    </div>

    <div class="form-group">
        <label>SIREN copropriété</label>
        <input name="siren_copropriete" value="{{ old('siren_copropriete', $copropriete->siren_copropriete ?? '') }}">
    </div>

    <div class="form-group">
        <label>Lots total</label>
        <input name="nombre_lots_total" value="{{ old('nombre_lots_total', $copropriete->nombre_lots_total ?? '') }}">
    </div>

    <div class="form-group">
        <label>Lots habitation</label>
        <input name="nombre_lots_habitation" value="{{ old('nombre_lots_habitation', $copropriete->nombre_lots_habitation ?? '') }}">
    </div>

    <div class="form-group">
        <label>Nombre bâtiments</label>
        <input name="nombre_batiments" value="{{ old('nombre_batiments', $copropriete->nombre_batiments ?? '') }}">
    </div>

    <div class="form-group">
        <label>Statut</label>
        <input name="statut" value="{{ old('statut', $copropriete->statut ?? '') }}">
    </div>

    <div class="form-group">
        <label>Date immatriculation</label>
        <input type="date" name="date_immatriculation" value="{{ old('date_immatriculation', $copropriete->date_immatriculation ?? '') }}">
    </div>

    <div class="form-group" style="grid-column: 1 / -1;">
        <label>Syndics associés</label>
        <select name="syndic_ids[]" multiple>
            @foreach($syndics as $syndic)
                <option value="{{ $syndic->id }}"
                    @selected(collect(old('syndic_ids', isset($copropriete) ? $copropriete->syndics->pluck('id')->toArray() : []))->contains($syndic->id))>
                    {{ $syndic->nom }} — {{ $syndic->siren }}
                </option>
            @endforeach
        </select>
    </div>
</div>