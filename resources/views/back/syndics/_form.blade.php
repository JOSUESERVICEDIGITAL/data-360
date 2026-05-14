<div class="form-grid">
    <div class="form-group">
        <label>Nom</label>
        <input name="nom" value="{{ old('nom', $syndic->nom ?? '') }}">
    </div>

    <div class="form-group">
        <label>SIREN</label>
        <input name="siren" value="{{ old('siren', $syndic->siren ?? '') }}">
    </div>

    <div class="form-group">
        <label>SIRET</label>
        <input name="siret" value="{{ old('siret', $syndic->siret ?? '') }}">
    </div>

    <div class="form-group">
        <label>Forme juridique</label>
        <input name="forme_juridique" value="{{ old('forme_juridique', $syndic->forme_juridique ?? '') }}">
    </div>

    <div class="form-group">
        <label>Activité</label>
        <input name="activite" value="{{ old('activite', $syndic->activite ?? '') }}">
    </div>

    <div class="form-group">
        <label>Adresse complète</label>
        <input name="adresse_complete" value="{{ old('adresse_complete', $syndic->adresse_complete ?? '') }}">
    </div>

    <div class="form-group">
        <label>Code postal</label>
        <input name="code_postal" value="{{ old('code_postal', $syndic->code_postal ?? '') }}">
    </div>

    <div class="form-group">
        <label>Ville</label>
        <input name="ville" value="{{ old('ville', $syndic->ville ?? '') }}">
    </div>

    <div class="form-group">
        <label>Téléphone</label>
        <input name="telephone" value="{{ old('telephone', $syndic->telephone ?? '') }}">
    </div>

    <div class="form-group">
        <label>Email</label>
        <input name="email" value="{{ old('email', $syndic->email ?? '') }}">
    </div>
</div>