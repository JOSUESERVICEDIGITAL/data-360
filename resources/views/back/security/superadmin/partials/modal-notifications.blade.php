{{-- ═══════════════════════════════════════════════════
     PARTIAL: modal-notifications.blade.php
     Usage: @include('back.security.superadmin.partials.modal-notifications')
════════════════════════════════════════════════════ --}}

<div id="modal-notifications" class="sa-modal-overlay">
    <div class="sa-modal" style="width:min(620px,100%);">

        <div class="sa-modal-header">
            <h3>
                <i class="fa-regular fa-bell" style="color:#8b5cf6;"></i>
                Notification globale
            </h3>
            <button class="sa-modal-close" onclick="closeModal('modal-notifications')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sa-modal-body">

            <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
                Envoyez une notification à un groupe d'utilisateurs. Elle apparaîtra dans leur interface.
            </p>

            <form id="notifBroadcastForm">
                @csrf

                {{-- Cible --}}
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:8px;">Cible</label>
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
                        @foreach([
                            ['all',        'Tous les utilisateurs', 'fa-users',       '#3b82f6'],
                            ['premium',    'Premium & Enterprise',  'fa-bolt',        '#8b5cf6'],
                            ['free',       'Plan Free seulement',   'fa-user',        '#94a3b8'],
                            ['admins',     'Admins seulement',      'fa-user-shield', '#f59e0b'],
                        ] as $target)
                        <label style="border:2px solid #e2e8f0;border-radius:12px;padding:12px;cursor:pointer;display:flex;align-items:center;gap:10px;transition:all .2s;" class="notif-target-option">
                            <input type="radio" name="target" value="{{ $target[0] }}" style="display:none;" {{ $target[0] === 'all' ? 'checked' : '' }}>
                            <div style="width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;background:{{ $target[3] }}15;color:{{ $target[3] }};flex-shrink:0;">
                                <i class="fa-solid {{ $target[1] }}"></i>
                            </div>
                            <div>
                                <div style="font-weight:800;font-size:12px;color:#0f172a;">{{ $target[2] }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Type --}}
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:8px;">Type de notification</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        @foreach([
                            ['info',    'Information', '#3b82f6', 'fa-circle-info'],
                            ['success', 'Succès',      '#10b981', 'fa-circle-check'],
                            ['warning', 'Avertissement','#f59e0b','fa-triangle-exclamation'],
                            ['danger',  'Urgent',      '#ef4444', 'fa-circle-xmark'],
                        ] as $type)
                        <label style="border:2px solid #e2e8f0;border-radius:10px;padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#334155;transition:all .2s;" class="notif-type-option">
                            <input type="radio" name="type" value="{{ $type[0] }}" style="display:none;" {{ $type[0] === 'info' ? 'checked' : '' }}>
                            <i class="fa-solid {{ $type[3] }}" style="color:{{ $type[2] }};"></i>
                            {{ $type[1] }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Titre --}}
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:5px;">Titre</label>
                    <input type="text" name="title" placeholder="Ex: Maintenance programmée ce soir"
                           style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;outline:none;box-sizing:border-box;"
                           id="notifTitle" oninput="updateNotifPreview()">
                </div>

                {{-- Message --}}
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:5px;">Message</label>
                    <textarea name="message" rows="3" placeholder="Détails de la notification…"
                              style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"
                              id="notifMessage" oninput="updateNotifPreview()"></textarea>
                </div>

                {{-- Prévisualisation --}}
                <div style="margin-bottom:6px;">
                    <label style="font-size:12px;font-weight:800;color:#334155;display:block;margin-bottom:8px;">Prévisualisation</label>
                    <div id="notif-preview" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;display:flex;gap:10px;align-items:flex-start;">
                        <i id="preview-icon" class="fa-solid fa-circle-info" style="color:#3b82f6;font-size:16px;flex-shrink:0;margin-top:2px;"></i>
                        <div>
                            <div id="preview-title" style="font-weight:800;font-size:13px;color:#0f172a;">Titre de la notification</div>
                            <div id="preview-message" style="font-size:12px;color:#64748b;margin-top:3px;">Message de la notification…</div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Résultat --}}
            <div id="notif-result" style="display:none;margin-top:12px;border-radius:10px;padding:12px 14px;font-size:13px;font-weight:700;"></div>

        </div>

        <div class="sa-modal-footer">
            <button class="sa-btn sa-btn-primary" onclick="sendBroadcast()" id="notif-send-btn">
                <i class="fa-solid fa-paper-plane"></i> Envoyer
            </button>
            <button class="sa-btn sa-btn-soft" onclick="closeModal('modal-notifications')">Annuler</button>
        </div>
    </div>
</div>

<style>
.notif-target-option:has(input:checked),
.notif-type-option:has(input:checked) {
    border-color:#3b82f6; background:#f0f7ff;
}
</style>

<script>
const typeIcons = { info:'fa-circle-info', success:'fa-circle-check', warning:'fa-triangle-exclamation', danger:'fa-circle-xmark' };
const typeColors = { info:'#3b82f6', success:'#10b981', warning:'#f59e0b', danger:'#ef4444' };

function updateNotifPreview() {
    const title   = document.getElementById('notifTitle')?.value   || 'Titre de la notification';
    const message = document.getElementById('notifMessage')?.value || 'Message de la notification…';
    const type    = document.querySelector('[name="type"]:checked')?.value || 'info';

    document.getElementById('preview-title').textContent   = title;
    document.getElementById('preview-message').textContent = message;

    const icon = document.getElementById('preview-icon');
    icon.className = `fa-solid ${typeIcons[type]}`;
    icon.style.color = typeColors[type];
}

function sendBroadcast() {
    const form = document.getElementById('notifBroadcastForm');
    const data = {
        target:  document.querySelector('[name="target"]:checked')?.value,
        type:    document.querySelector('[name="type"]:checked')?.value,
        title:   document.getElementById('notifTitle')?.value,
        message: document.getElementById('notifMessage')?.value,
    };

    if (!data.title || !data.message) {
        alert('Veuillez remplir le titre et le message.');
        return;
    }

    const btn = document.getElementById('notif-send-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Envoi…';

    fetch("{{ route('admin.superadmin.notifications.broadcast') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        const resultDiv = document.getElementById('notif-result');
        resultDiv.style.display = 'block';
        if (res.success) {
            resultDiv.style.background = '#f0fdf4';
            resultDiv.style.border = '1px solid #bbf7d0';
            resultDiv.style.color = '#166534';
            resultDiv.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${res.message}`;
        } else {
            resultDiv.style.background = '#fff5f5';
            resultDiv.style.border = '1px solid #fecaca';
            resultDiv.style.color = '#991b1b';
            resultDiv.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> ${res.message}`;
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
    });
}
</script>
