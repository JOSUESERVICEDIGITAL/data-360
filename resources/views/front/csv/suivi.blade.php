<div id="progressBox" style="max-width:600px; margin:4rem auto; text-align:center;">
    <h2>Traitement en cours…</h2>
    <p>{{ $import->filename_original }} — {{ $import->total_lignes }} adresses</p>

    <div style="background:#e2e8f0; border-radius:99px; height:16px; margin:1.5rem 0;">
        <div id="progressBar" style="background:#0053b3; height:16px; border-radius:99px; width:0%; transition:width 0.5s;"></div>
    </div>

    <div id="progressText">0 / {{ $import->total_lignes }}</div>
    <div id="statusText" style="margin-top:1rem; color:#64748b;"></div>
    <div id="downloadBtn" style="display:none; margin-top:2rem;"></div>
</div>

<script>
const importId    = {{ $import->id }};
const progressUrl = "{{ route('front.csv.progress', $import->id) }}";

let errorCount = 0;
const maxErrorRetries = 100;

function poll() {
    fetch(progressUrl)
        .then(r => r.json())
        .then(data => {
            document.getElementById('progressBar').style.width = data.progress + '%';
            document.getElementById('progressText').textContent = data.lignes_traitees + ' / ' + data.total_lignes + ' adresses traitées (' + data.progress + '%)';

            if (data.statut === 'termine') {
                document.getElementById('statusText').textContent = '✅ Traitement terminé !';
                document.getElementById('downloadBtn').innerHTML =
                    `<a href="${data.download_url}" download style="background:#0053b3;color:white;padding:12px 28px;border-radius:48px;font-weight:700;text-decoration:none;">
                        ⬇ Télécharger le fichier enrichi
                    </a>`;
                document.getElementById('downloadBtn').style.display = 'block';
            } else if (data.statut === 'erreur') {
                errorCount++;
                document.getElementById('statusText').textContent = '⚠️ Si le traitement échoue, actualisez la page… (tentative ' + errorCount + ')';
                if (errorCount < maxErrorRetries) {
                    setTimeout(poll, 5000);
                } else {
                    document.getElementById('statusText').textContent = '❌ Erreur lors du traitement. Actualisez la page…';
                }
            } else {
                errorCount = 0;
                setTimeout(poll, 3000);
            }
        });
}

poll();
</script>