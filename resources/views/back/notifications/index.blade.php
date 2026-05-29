@extends('back.layouts.app')

@section('title', 'Notifications')

@section('content')

<div class="notif-page">

    <div class="notif-topbar">
        <div class="notif-topbar-left">
            <div class="notif-icon-wrap">
                <i class="fa-solid fa-bell"></i>
            </div>

            <div>
                <h1>Gestion des notifications</h1>
                <p>
                    Créez, gérez et surveillez toutes les notifications envoyées aux utilisateurs.
                </p>
            </div>
        </div>

        <a href="{{ route('back.notifications.create') }}" class="notif-create-btn">
            <i class="fa-solid fa-plus"></i>
            Nouvelle notification
        </a>
    </div>

    @if(session('success'))
        <div class="notif-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- FILTRES --}}
    <div class="notif-card notif-filters-card">
        <form method="GET" class="notif-filters">

            <div class="notif-filter-group">
                <label>Type</label>

                <select name="type" class="notif-input">
                    <option value="">Tous les types</option>

                    <option value="admin" {{ request('type') == 'admin' ? 'selected' : '' }}>
                        Admin
                    </option>

                    <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>
                        Info
                    </option>

                    <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>
                        Succès
                    </option>

                    <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>
                        Alerte
                    </option>

                    <option value="danger" {{ request('type') == 'danger' ? 'selected' : '' }}>
                        Danger
                    </option>
                </select>
            </div>

            <div class="notif-filter-group">
                <label>Utilisateur</label>

                <select name="user_id" class="notif-input">
                    <option value="">Tous les utilisateurs</option>

                    @foreach($users as $user)
                        <option value="{{ $user->id }}"
                            {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="notif-filter-group">
                <label>Portée</label>

                <select name="is_global" class="notif-input">
                    <option value="">Toutes</option>

                    <option value="1" {{ request('is_global') == '1' ? 'selected' : '' }}>
                        Globales
                    </option>

                    <option value="0" {{ request('is_global') == '0' ? 'selected' : '' }}>
                        Personnalisées
                    </option>
                </select>
            </div>

            <div class="notif-filter-actions">
                <button class="notif-filter-btn primary" type="submit">
                    <i class="fa-solid fa-filter"></i>
                    Filtrer
                </button>

                <a href="{{ route('back.notifications.index') }}"
                   class="notif-filter-btn secondary">
                    <i class="fa-solid fa-rotate-left"></i>
                    Réinitialiser
                </a>
            </div>

        </form>
    </div>

    {{-- TABLE --}}
    <div class="notif-card notif-table-card">

        <div class="notif-table-header">
            <div>
                <h2>Liste des notifications</h2>
                <p>{{ $notifications->total() }} notification(s)</p>
            </div>
        </div>

        <div class="notif-table-wrap">

            <table class="notif-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Notification</th>
                        <th>Destinataire</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($notifications as $notif)

                        @php
                            $typeStyles = [
                                'admin' => [
                                    'color' => '#8b5cf6',
                                    'icon' => 'fa-solid fa-crown',
                                    'bg' => '#f3e8ff',
                                ],

                                'info' => [
                                    'color' => '#3b82f6',
                                    'icon' => 'fa-solid fa-circle-info',
                                    'bg' => '#dbeafe',
                                ],

                                'success' => [
                                    'color' => '#10b981',
                                    'icon' => 'fa-solid fa-circle-check',
                                    'bg' => '#d1fae5',
                                ],

                                'warning' => [
                                    'color' => '#f59e0b',
                                    'icon' => 'fa-solid fa-triangle-exclamation',
                                    'bg' => '#fef3c7',
                                ],

                                'danger' => [
                                    'color' => '#ef4444',
                                    'icon' => 'fa-solid fa-circle-exclamation',
                                    'bg' => '#fee2e2',
                                ],
                            ];

                            $style = $typeStyles[$notif->type] ?? $typeStyles['info'];
                        @endphp

                        <tr>

                            <td>
                                <div class="notif-id">
                                    #{{ $notif->id }}
                                </div>
                            </td>

                            <td>

                                <div class="notif-main">

                                    <div class="notif-type-icon"
                                         style="
                                            background: {{ $style['bg'] }};
                                            color: {{ $style['color'] }};
                                         ">
                                        <i class="{{ $style['icon'] }}"></i>
                                    </div>

                                    <div class="notif-main-content">

                                        <div class="notif-title-row">
                                            <h3>{{ $notif->title }}</h3>

                                            <span class="notif-type-badge"
                                                  style="
                                                    background: {{ $style['bg'] }};
                                                    color: {{ $style['color'] }};
                                                  ">
                                                {{ ucfirst($notif->type) }}
                                            </span>
                                        </div>

                                        <p>
                                            {{ \Illuminate\Support\Str::limit($notif->message, 90) }}
                                        </p>

                                    </div>

                                </div>

                            </td>

                            <td>

                                @if($notif->is_global)

                                    <div class="notif-destination global">
                                        <i class="fa-solid fa-earth-africa"></i>
                                        Tous les utilisateurs
                                    </div>

                                @else

                                    <div class="notif-user-box">
                                        <div class="notif-user-avatar">
                                            {{ strtoupper(substr($notif->user?->name ?? 'U', 0, 1)) }}
                                        </div>

                                        <div>
                                            <strong>
                                                {{ $notif->user?->name ?? '-' }}
                                            </strong>

                                            <small>
                                                {{ $notif->user?->email }}
                                            </small>
                                        </div>
                                    </div>

                                @endif

                            </td>

                            <td>

                                @if($notif->is_read)

                                    <span class="notif-status read">
                                        <i class="fa-solid fa-check"></i>
                                        Lu
                                    </span>

                                @else

                                    <span class="notif-status unread">
                                        <i class="fa-solid fa-clock"></i>
                                        Non lu
                                    </span>

                                @endif

                            </td>

                            <td>

                                <div class="notif-date">
                                    <strong>
                                        {{ $notif->created_at->format('d/m/Y') }}
                                    </strong>

                                    <small>
                                        {{ $notif->created_at->format('H:i') }}
                                    </small>
                                </div>

                            </td>

                            <td>

                                <div class="notif-actions">

                                    <a href="{{ route('back.notifications.edit', $notif) }}"
                                       class="notif-action-btn edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>

                                    <form method="POST"
                                          action="{{ route('back.notifications.destroy', $notif) }}"
                                          onsubmit="return confirm('Supprimer cette notification ?')">

                                        @csrf
                                        @method('DELETE')

                                        <button class="notif-action-btn delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6">

                                <div class="notif-empty">

                                    <div class="notif-empty-icon">
                                        <i class="fa-regular fa-bell-slash"></i>
                                    </div>

                                    <h3>Aucune notification</h3>

                                    <p>
                                        Aucune notification trouvée pour le moment.
                                    </p>

                                    <a href="{{ route('back.notifications.create') }}"
                                       class="notif-create-btn">
                                        <i class="fa-solid fa-plus"></i>
                                        Créer une notification
                                    </a>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if($notifications->hasPages())
            <div class="notif-pagination">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>

</div>

<style>

    .notif-page{
        padding:2rem;
        display:flex;
        flex-direction:column;
        gap:1.5rem;
        background:#f8fafc;
        min-height:100vh;
    }

    .notif-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:1.5rem;
        flex-wrap:wrap;
    }

    .notif-topbar-left{
        display:flex;
        align-items:center;
        gap:1.2rem;
    }

    .notif-icon-wrap{
        width:72px;
        height:72px;
        border-radius:24px;
        background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
        display:flex;
        align-items:center;
        justify-content:center;
        color:white;
        font-size:1.8rem;
        box-shadow:0 15px 35px rgba(37,99,235,.25);
    }

    .notif-topbar h1{
        margin:0;
        font-size:2rem;
        font-weight:900;
        color:#0f172a;
    }

    .notif-topbar p{
        margin-top:.35rem;
        color:#64748b;
        font-size:.98rem;
    }

    .notif-create-btn{
        display:inline-flex;
        align-items:center;
        gap:.7rem;
        background:linear-gradient(135deg,#10b981 0%,#059669 100%);
        color:white;
        padding:1rem 1.3rem;
        border-radius:16px;
        text-decoration:none;
        font-weight:800;
        transition:.25s;
        box-shadow:0 12px 30px rgba(16,185,129,.25);
    }

    .notif-create-btn:hover{
        transform:translateY(-2px);
        color:white;
    }

    .notif-alert-success{
        display:flex;
        align-items:center;
        gap:.8rem;
        padding:1rem 1.2rem;
        border-radius:16px;
        background:#dcfce7;
        color:#166534;
        font-weight:700;
        border:1px solid #bbf7d0;
    }

    .notif-card{
        background:white;
        border-radius:24px;
        padding:1.5rem;
        box-shadow:0 10px 35px rgba(15,23,42,.05);
        border:1px solid #e2e8f0;
    }

    .notif-filters{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
        gap:1rem;
        align-items:end;
    }

    .notif-filter-group{
        display:flex;
        flex-direction:column;
        gap:.55rem;
    }

    .notif-filter-group label{
        font-weight:800;
        color:#334155;
        font-size:.9rem;
    }

    .notif-input{
        width:100%;
        height:52px;
        border-radius:14px;
        border:1px solid #dbe2ea;
        background:#f8fafc;
        padding:0 1rem;
        font-size:.95rem;
        transition:.2s;
    }

    .notif-input:focus{
        outline:none;
        border-color:#2563eb;
        background:white;
        box-shadow:0 0 0 4px rgba(37,99,235,.1);
    }

    .notif-filter-actions{
        display:flex;
        gap:.8rem;
        flex-wrap:wrap;
    }

    .notif-filter-btn{
        height:52px;
        padding:0 1.2rem;
        border-radius:14px;
        border:none;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:.6rem;
        font-weight:800;
        cursor:pointer;
        transition:.2s;
    }

    .notif-filter-btn.primary{
        background:#2563eb;
        color:white;
    }

    .notif-filter-btn.secondary{
        background:#e2e8f0;
        color:#334155;
    }

    .notif-table-header{
        margin-bottom:1.2rem;
    }

    .notif-table-header h2{
        margin:0;
        font-size:1.3rem;
        color:#0f172a;
        font-weight:900;
    }

    .notif-table-header p{
        margin-top:.35rem;
        color:#64748b;
    }

    .notif-table-wrap{
        overflow-x:auto;
    }

    .notif-table{
        width:100%;
        border-collapse:separate;
        border-spacing:0 14px;
    }

    .notif-table thead th{
        text-align:left;
        padding:.75rem 1rem;
        color:#64748b;
        font-size:.78rem;
        text-transform:uppercase;
        letter-spacing:.05em;
        font-weight:900;
    }

    .notif-table tbody tr{
        background:#f8fafc;
        transition:.2s;
    }

    .notif-table tbody tr:hover{
        transform:translateY(-2px);
        background:white;
        box-shadow:0 12px 25px rgba(15,23,42,.08);
    }

    .notif-table tbody td{
        padding:1rem;
        vertical-align:middle;
    }

    .notif-table tbody td:first-child{
        border-top-left-radius:18px;
        border-bottom-left-radius:18px;
    }

    .notif-table tbody td:last-child{
        border-top-right-radius:18px;
        border-bottom-right-radius:18px;
    }

    .notif-id{
        font-weight:900;
        color:#2563eb;
    }

    .notif-main{
        display:flex;
        align-items:flex-start;
        gap:1rem;
    }

    .notif-type-icon{
        width:52px;
        height:52px;
        border-radius:16px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:1.1rem;
        flex-shrink:0;
    }

    .notif-main-content{
        min-width:240px;
    }

    .notif-title-row{
        display:flex;
        align-items:center;
        gap:.7rem;
        flex-wrap:wrap;
        margin-bottom:.4rem;
    }

    .notif-title-row h3{
        margin:0;
        font-size:1rem;
        font-weight:900;
        color:#0f172a;
    }

    .notif-main-content p{
        margin:0;
        color:#64748b;
        line-height:1.5;
        font-size:.92rem;
    }

    .notif-type-badge{
        padding:.38rem .75rem;
        border-radius:999px;
        font-size:.72rem;
        font-weight:900;
    }

    .notif-destination{
        display:inline-flex;
        align-items:center;
        gap:.5rem;
        padding:.7rem 1rem;
        border-radius:999px;
        font-weight:800;
        font-size:.82rem;
    }

    .notif-destination.global{
        background:#dbeafe;
        color:#1d4ed8;
    }

    .notif-user-box{
        display:flex;
        align-items:center;
        gap:.8rem;
    }

    .notif-user-avatar{
        width:42px;
        height:42px;
        border-radius:50%;
        background:linear-gradient(135deg,#2563eb,#1d4ed8);
        color:white;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight:900;
    }

    .notif-user-box strong{
        display:block;
        color:#0f172a;
    }

    .notif-user-box small{
        color:#64748b;
    }

    .notif-status{
        display:inline-flex;
        align-items:center;
        gap:.45rem;
        padding:.65rem .95rem;
        border-radius:999px;
        font-weight:900;
        font-size:.75rem;
    }

    .notif-status.read{
        background:#dcfce7;
        color:#166534;
    }

    .notif-status.unread{
        background:#fef3c7;
        color:#92400e;
    }

    .notif-date{
        display:flex;
        flex-direction:column;
        gap:.2rem;
    }

    .notif-date strong{
        color:#0f172a;
    }

    .notif-date small{
        color:#64748b;
    }

    .notif-actions{
        display:flex;
        align-items:center;
        gap:.7rem;
    }

    .notif-action-btn{
        width:42px;
        height:42px;
        border:none;
        border-radius:12px;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:.2s;
        text-decoration:none;
    }

    .notif-action-btn.edit{
        background:#dbeafe;
        color:#2563eb;
    }

    .notif-action-btn.delete{
        background:#fee2e2;
        color:#dc2626;
    }

    .notif-action-btn:hover{
        transform:translateY(-2px) scale(1.03);
    }

    .notif-empty{
        padding:4rem 2rem;
        text-align:center;
    }

    .notif-empty-icon{
        width:90px;
        height:90px;
        margin:0 auto 1rem;
        border-radius:30px;
        background:#f1f5f9;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#94a3b8;
        font-size:2rem;
    }

    .notif-empty h3{
        margin:0;
        color:#0f172a;
        font-size:1.3rem;
        font-weight:900;
    }

    .notif-empty p{
        margin:1rem 0 2rem;
        color:#64748b;
    }

    .notif-pagination{
        margin-top:1.5rem;
    }

    @media(max-width:900px){

        .notif-page{
            padding:1rem;
        }

        .notif-topbar{
            flex-direction:column;
            align-items:flex-start;
        }

        .notif-table{
            min-width:1000px;
        }

    }

</style>

@endsection