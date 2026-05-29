@extends('back.layouts.app')

@section('title', 'Créer une notification')

@section('content')

<div class="notif-create-page">

    <div class="notif-topbar">
        <div>
            <h1>
                <i class="fa-solid fa-paper-plane"></i>
                Créer une notification
            </h1>

            <p>
                Envoyez des notifications élégantes aux utilisateurs de votre plateforme.
            </p>
        </div>

        <a href="{{ route('back.notifications.index') }}" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Retour
        </a>
    </div>

    <div class="notif-card">

        <div class="notif-card-header">
            <div class="notif-icon-preview" id="notifTypePreview">
                <i class="fa-solid fa-bell"></i>
            </div>

            <div>
                <h2>Nouvelle notification</h2>
                <span>
                    Configurez le message, le type et les destinataires.
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('back.notifications.store') }}">
            @csrf

            <div class="form-grid">

                {{-- TYPE --}}
                <div class="form-group">
                    <label>
                        <i class="fa-solid fa-layer-group"></i>
                        Type de notification
                    </label>

                    <select name="type" class="form-input" id="typeSelect" required>
                        <option value="info" {{ old('type') == 'info' ? 'selected' : '' }}>
                            ℹ️ Information
                        </option>

                        <option value="success" {{ old('type') == 'success' ? 'selected' : '' }}>
                            ✅ Succès
                        </option>

                        <option value="warning" {{ old('type') == 'warning' ? 'selected' : '' }}>
                            ⚠️ Alerte
                        </option>

                        <option value="danger" {{ old('type') == 'danger' ? 'selected' : '' }}>
                            🔴 Danger
                        </option>

                        <option value="admin" {{ old('type') == 'admin' ? 'selected' : '' }}>
                            👑 Admin
                        </option>
                    </select>
                </div>

                {{-- TITRE --}}
                <div class="form-group">
                    <label>
                        <i class="fa-solid fa-heading"></i>
                        Titre
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-input"
                        value="{{ old('title') }}"
                        placeholder="Ex: Nouveau crédit ajouté"
                        required
                    >
                </div>

            </div>

            {{-- MESSAGE --}}
            <div class="form-group">
                <label>
                    <i class="fa-solid fa-comment-dots"></i>
                    Message
                </label>

                <textarea
                    name="message"
                    class="form-input textarea-input"
                    rows="5"
                    placeholder="Écrivez votre notification..."
                    required
                >{{ old('message') }}</textarea>
            </div>

            <div class="form-grid">

                {{-- LIEN --}}
                <div class="form-group">
                    <label>
                        <i class="fa-solid fa-link"></i>
                        Lien (optionnel)
                    </label>

                    <input
                        type="url"
                        name="link"
                        class="form-input"
                        value="{{ old('link') }}"
                        placeholder="https://..."
                    >
                </div>

                {{-- ICONE --}}
                <div class="form-group">
                    <label>
                        <i class="fa-solid fa-icons"></i>
                        Icône personnalisée
                    </label>

                    <input
                        type="text"
                        name="icon"
                        class="form-input"
                        value="{{ old('icon') }}"
                        placeholder="fa-solid fa-bell"
                    >

                    <small class="input-helper">
                        Exemple : fa-solid fa-fire
                    </small>
                </div>

            </div>

            {{-- GLOBAL --}}
            <div class="global-box">
                <label class="toggle-wrapper">

                    <input
                        type="checkbox"
                        name="is_global"
                        value="1"
                        id="globalCheckbox"
                        {{ old('is_global') ? 'checked' : '' }}
                    >

                    <span class="toggle-slider"></span>

                    <div class="toggle-content">
                        <strong>
                            🌍 Notification globale
                        </strong>

                        <span>
                            Envoyer à tous les utilisateurs
                        </span>
                    </div>

                </label>
            </div>

            {{-- USER --}}
            <div
                class="form-group"
                id="userSelect"
                style="display: {{ old('is_global') ? 'none' : 'block' }};"
            >
                <label>
                    <i class="fa-solid fa-user"></i>
                    Utilisateur destinataire
                </label>

                <select name="user_id" class="form-input">
                    <option value="">
                        -- Sélectionner un utilisateur --
                    </option>

                    @foreach($users as $user)
                        <option
                            value="{{ $user->id }}"
                            {{ old('user_id') == $user->id ? 'selected' : '' }}
                        >
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- EXPIRATION --}}
            <div class="form-group">
                <label>
                    <i class="fa-solid fa-clock"></i>
                    Date d’expiration
                </label>

                <input
                    type="datetime-local"
                    name="expires_at"
                    class="form-input"
                    value="{{ old('expires_at') }}"
                >
            </div>

            {{-- ACTIONS --}}
            <div class="form-actions">

                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-paper-plane"></i>
                    Envoyer la notification
                </button>

                <a href="{{ route('back.notifications.index') }}" class="cancel-btn">
                    <i class="fa-solid fa-xmark"></i>
                    Annuler
                </a>

            </div>

        </form>

    </div>

</div>

<style>

    .notif-create-page{
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem;
    }

    .notif-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:1rem;
        margin-bottom:2rem;
    }

    .notif-topbar h1{
        margin:0;
        font-size:2rem;
        font-weight:900;
        color:#0f172a;
        display:flex;
        align-items:center;
        gap:.75rem;
    }

    .notif-topbar p{
        margin-top:.4rem;
        color:#64748b;
        font-size:1rem;
    }

    .back-btn{
        display:inline-flex;
        align-items:center;
        gap:.6rem;
        padding:.9rem 1.2rem;
        border-radius:14px;
        text-decoration:none;
        font-weight:800;
        background:#f8fafc;
        color:#0f172a;
        border:1px solid #e2e8f0;
        transition:.2s;
    }

    .back-btn:hover{
        background:#e2e8f0;
        transform:translateY(-2px);
    }

    .notif-card{
        background:white;
        border-radius:28px;
        padding:2rem;
        box-shadow:
            0 10px 40px rgba(15,23,42,.08),
            0 2px 10px rgba(15,23,42,.04);
        border:1px solid #edf2f7;
    }

    .notif-card-header{
        display:flex;
        align-items:center;
        gap:1.2rem;
        margin-bottom:2rem;
        padding-bottom:1.5rem;
        border-bottom:1px solid #e2e8f0;
    }

    .notif-icon-preview{
        width:70px;
        height:70px;
        border-radius:22px;
        background:linear-gradient(135deg,#0053b3,#3b82f6);
        display:flex;
        align-items:center;
        justify-content:center;
        color:white;
        font-size:1.8rem;
        box-shadow:0 10px 25px rgba(0,83,179,.25);
    }

    .notif-card-header h2{
        margin:0;
        font-size:1.5rem;
        font-weight:900;
        color:#0f172a;
    }

    .notif-card-header span{
        color:#64748b;
        font-size:.95rem;
    }

    .form-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
        gap:1.4rem;
    }

    .form-group{
        margin-bottom:1.5rem;
    }

    .form-group label{
        display:flex;
        align-items:center;
        gap:.55rem;
        margin-bottom:.7rem;
        font-size:.92rem;
        font-weight:800;
        color:#0f172a;
    }

    .form-input{
        width:100%;
        padding:1rem 1.1rem;
        border-radius:16px;
        border:1px solid #dbe4ee;
        background:#f8fafc;
        font-size:.95rem;
        transition:.25s;
        color:#0f172a;
    }

    .form-input:focus{
        outline:none;
        border-color:#0053b3;
        background:white;
        box-shadow:0 0 0 5px rgba(0,83,179,.08);
    }

    .textarea-input{
        resize:vertical;
        min-height:130px;
    }

    .input-helper{
        display:block;
        margin-top:.45rem;
        color:#94a3b8;
        font-size:.8rem;
    }

    .global-box{
        background:linear-gradient(135deg,#eff6ff,#f8fafc);
        border:1px solid #dbeafe;
        border-radius:22px;
        padding:1.2rem 1.4rem;
        margin-bottom:1.6rem;
    }

    .toggle-wrapper{
        display:flex;
        align-items:center;
        gap:1rem;
        cursor:pointer;
    }

    .toggle-wrapper input{
        display:none;
    }

    .toggle-slider{
        width:58px;
        height:32px;
        background:#cbd5e1;
        border-radius:999px;
        position:relative;
        transition:.25s;
        flex-shrink:0;
    }

    .toggle-slider::before{
        content:"";
        position:absolute;
        width:24px;
        height:24px;
        background:white;
        border-radius:50%;
        top:4px;
        left:4px;
        transition:.25s;
        box-shadow:0 2px 10px rgba(0,0,0,.15);
    }

    .toggle-wrapper input:checked + .toggle-slider{
        background:#0053b3;
    }

    .toggle-wrapper input:checked + .toggle-slider::before{
        transform:translateX(26px);
    }

    .toggle-content{
        display:flex;
        flex-direction:column;
        gap:.2rem;
    }

    .toggle-content strong{
        color:#0f172a;
        font-size:.95rem;
    }

    .toggle-content span{
        color:#64748b;
        font-size:.85rem;
    }

    .form-actions{
        display:flex;
        flex-wrap:wrap;
        gap:1rem;
        margin-top:2rem;
        padding-top:1.8rem;
        border-top:1px solid #e2e8f0;
    }

    .submit-btn{
        border:none;
        cursor:pointer;
        padding:1rem 1.5rem;
        border-radius:16px;
        background:linear-gradient(135deg,#0053b3,#2563eb);
        color:white;
        font-weight:900;
        display:inline-flex;
        align-items:center;
        gap:.7rem;
        box-shadow:0 10px 25px rgba(0,83,179,.25);
        transition:.25s;
    }

    .submit-btn:hover{
        transform:translateY(-2px);
        box-shadow:0 16px 35px rgba(0,83,179,.35);
    }

    .cancel-btn{
        display:inline-flex;
        align-items:center;
        gap:.7rem;
        padding:1rem 1.4rem;
        border-radius:16px;
        background:#f8fafc;
        color:#0f172a;
        border:1px solid #e2e8f0;
        text-decoration:none;
        font-weight:800;
        transition:.2s;
    }

    .cancel-btn:hover{
        background:#e2e8f0;
        transform:translateY(-2px);
    }

    @media(max-width:768px){

        .notif-create-page{
            padding:1rem;
        }

        .notif-topbar{
            flex-direction:column;
            align-items:flex-start;
        }

        .notif-card{
            padding:1.3rem;
            border-radius:22px;
        }

        .notif-card-header{
            flex-direction:column;
            text-align:center;
        }

        .form-actions{
            flex-direction:column;
        }

        .submit-btn,
        .cancel-btn{
            width:100%;
            justify-content:center;
        }
    }

</style>

<script>

    const globalCheckbox = document.getElementById('globalCheckbox');
    const userSelect = document.getElementById('userSelect');
    const typeSelect = document.getElementById('typeSelect');
    const notifPreview = document.getElementById('notifTypePreview');

    function toggleUserSelect() {
        userSelect.style.display = globalCheckbox.checked ? 'none' : 'block';
    }

    globalCheckbox.addEventListener('change', toggleUserSelect);

    toggleUserSelect();

    function updatePreview() {

        const type = typeSelect.value;

        let bg = 'linear-gradient(135deg,#0053b3,#2563eb)';
        let icon = 'fa-bell';

        if(type === 'success'){
            bg = 'linear-gradient(135deg,#059669,#10b981)';
            icon = 'fa-circle-check';
        }

        if(type === 'warning'){
            bg = 'linear-gradient(135deg,#d97706,#f59e0b)';
            icon = 'fa-triangle-exclamation';
        }

        if(type === 'danger'){
            bg = 'linear-gradient(135deg,#dc2626,#ef4444)';
            icon = 'fa-circle-xmark';
        }

        if(type === 'admin'){
            bg = 'linear-gradient(135deg,#7c2d12,#f59e0b)';
            icon = 'fa-crown';
        }

        notifPreview.style.background = bg;
        notifPreview.innerHTML = `<i class="fa-solid ${icon}"></i>`;
    }

    typeSelect.addEventListener('change', updatePreview);

    updatePreview();

</script>

@endsection