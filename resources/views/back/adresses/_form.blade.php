<div class="form-grid">
    <div class="form-group">
        <label>Adresse complète</label>
        <input name="adresse_complete" value="{{ old('adresse_complete', $adresse->adresse_complete ?? '') }}" required>
    </div>

    <div class="form-group">
        <label>Numéro</label>
        <input name="numero" value="{{ old('numero', $adresse->numero ?? '') }}">
    </div>

    <div class="form-group">
        <label>Voie</label>
        <input name="voie" value="{{ old('voie', $adresse->voie ?? '') }}">
    </div>

    <div class="form-group">
        <label>Code postal</label>
        <input name="code_postal" value="{{ old('code_postal', $adresse->code_postal ?? '') }}">
    </div>

    <div class="form-group">
        <label>Ville</label>
        <input name="ville" value="{{ old('ville', $adresse->ville ?? '') }}">
    </div>

    <div class="form-group">
        <label>Code INSEE</label>
        <input name="code_insee" value="{{ old('code_insee', $adresse->code_insee ?? '') }}">
    </div>

    <div class="form-group">
        <label>Latitude</label>
        <input name="latitude" value="{{ old('latitude', $adresse->latitude ?? '') }}">
    </div>

    <div class="form-group">
        <label>Longitude</label>
        <input name="longitude" value="{{ old('longitude', $adresse->longitude ?? '') }}">
    </div>

    <div class="form-group">
        <label>Source</label>
        <input name="source" value="{{ old('source', $adresse->source ?? '') }}">
    </div>
</div>