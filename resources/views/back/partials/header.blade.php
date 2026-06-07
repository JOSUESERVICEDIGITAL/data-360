@php
$authUser = auth()->user();
$isAdmin = $authUser && (bool) $authUser->is_admin;
@endphp
@php
$unreadNotificationsCount = 0;
$recentNotifications = collect();

if (auth()->check()) {
$unreadNotificationsCount = App\Models\Back\Notification::forUser(auth()->id())
->notExpired()
->unread()
->count();

$recentNotifications = App\Models\Back\Notification::forUser(auth()->id())
->notExpired()
->orderBy('created_at', 'desc')
->limit(5)
->get();
}
@endphp

<header class="admin-header">
    <div class="header-container">

        <div class="header-brand">
            <a href="{{ $isAdmin ? route('back.dashboard') : route('dashboard') }}" class="brand-link">
                <div class="brand-icon">
                    <i class="fa-solid fa-rocket"></i>
                </div>

                <div class="brand-text">
                    <a href="{{ route('front.home') }}" style="text-decoration: none; color: inherit;">
                        <strong>Data-360</strong>
                        <span class="brand-badge">
                            {{ $isAdmin ? 'Back Office' : 'Espace utilisateur' }}
                        </span>
                    </a>
                </div>
            </a>
        </div>

        <button class="header-mobile-toggle" id="mobileToggle" type="button">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="header-right" id="headerRight">
            <div class="header-actions">
                @auth
                @php $isAdmin = auth()->user()->is_admin; @endphp

                @if($isAdmin)
                <button class="action-btn" onclick="window.location.href='{{ route('admin.security.users.create') }}'"
                    title="Ajouter un utilisateur" type="button">
                    <i class="fa-solid fa-user-plus"></i>
                </button>

                @if(Route::has('back.imports.create'))
                <button class="action-btn" onclick="window.location.href='{{ route('back.imports.create') }}'"
                    title="Importer des données" type="button">
                    <i class="fa-solid fa-upload"></i>
                </button>
                @endif
                @endif
                @endauth
            </div>

            <div class="header-notifications">
                <button class="notif-btn" id="notifBtn" type="button">
                    <i class="fa-regular fa-bell"></i>
                    @if($unreadNotificationsCount > 0)
                    <span
                        class="notif-badge">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>
                    @endif
                </button>

                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span>Notifications</span>
                        <a href="#" id="markAllReadBtn">Tout marquer comme lu</a>
                    </div>

                    <div class="notif-list" id="notifList">
                        @if($recentNotifications->isEmpty())
                        <div class="notif-empty">
                            <i class="fa-regular fa-bell-slash"></i>
                            <p>Aucune notification</p>
                        </div>
                        @else
                        @foreach($recentNotifications as $notif)
                        @php $typeInfo = \App\Models\Back\Notification::types()[$notif->type] ?? \App\Models\Back\Notification::types()['info']; @endphp
                        <div class="notif-item {{ !$notif->is_read ? 'unread' : '' }}" data-id="{{ $notif->id }}">
                            <i class="{{ $notif->icon ?? $typeInfo['icon'] }}"
                                style="color: {{ $typeInfo['color'] }};"></i>
                            <div>
                                <p>{{ $notif->title }}</p>
                                <small>{{ $notif->message }}</small>
                                <span class="notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                            </div>
                            @if($notif->link)
                            <a href="{{ $notif->link }}" class="notif-link"></a>
                            @endif
                        </div>
                        @endforeach
                        @endif
                    </div>

                    <div class="notif-footer">
                        <a href="{{ route('back.notifications.index') }}">
                            Voir toutes les notifications
                        </a>
                    </div>
                </div>
            </div>
            <div class="header-user">
                <button class="user-btn" id="userBtn" type="button">
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div class="user-info">
                        <span class="user-name">{{ $authUser->name ?? 'Utilisateur' }}</span>
                        <span class="user-role">{{ $isAdmin ? 'Administrateur' : 'Utilisateur' }}</span>
                    </div>

                    <i class="fa-solid fa-chevron-down user-chevron"></i>
                </button>

                <div class="user-dropdown" id="userDropdown">
                    <a href="{{ route('profile.edit') }}" class="dropdown-item">
                        <i class="fa-regular fa-user"></i>
                        Mon profil
                    </a>

                    <a href="{{ route('dashboard') }}" class="dropdown-item">
                        <i class="fa-solid fa-gauge-high"></i>
                        Mon espace
                    </a>

                    @if(!$isAdmin)
                    @if(Route::has('front.home'))
                    <a href="{{ route('front.home') }}" class="dropdown-item">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Nouvelle recherche
                    </a>
                    @endif

                    <a href="{{ route('dashboard') }}" class="dropdown-item">
                        <i class="fa-regular fa-credit-card"></i>
                        Mes crédits
                        <span class="badge-credits">{{ $authUser->credits ?? 0 }}</span>
                    </a>
                    @endif

                    @if($isAdmin)
                    <div class="dropdown-divider"></div>

                    <a href="{{ route('admin.security.users.index') }}" class="dropdown-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        Utilisateurs & crédits
                    </a>

                    <a href="{{ route('admin.security.blocked.index') }}" class="dropdown-item">
                        <i class="fa-solid fa-ban"></i>
                        Identités bloquées
                    </a>
                    @endif

                    <div class="dropdown-divider"></div>

                    <a href="#" class="dropdown-item">
                        <i class="fa-regular fa-circle-question"></i>
                        Aide & Support
                    </a>

                    <div class="dropdown-divider"></div>

                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf

                        <button type="submit" class="dropdown-item logout-btn">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</header>

<style>
    .admin-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 0.75rem 0;
    }

    .header-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
    }

    .header-brand {
        flex-shrink: 0;
    }

    .brand-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .brand-link:hover {
        opacity: 0.85;
    }

    .brand-icon {
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #0053b3, #004099);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(0, 83, 179, 0.25);
    }

    .brand-text {
        display: flex;
        flex-direction: column;
    }

    .brand-text strong {
        font-size: 1.1rem;
        color: #0f172a;
        letter-spacing: -0.3px;
    }

    .brand-badge {
        font-size: 0.7rem;
        color: #64748b;
        background: #f1f5f9;
        padding: 0.15rem 0.5rem;
        border-radius: 20px;
        display: inline-block;
        width: fit-content;
        margin-top: 0.15rem;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .header-actions {
        display: flex;
        gap: 0.5rem;
        border-right: 1px solid #e2e8f0;
        padding-right: 1rem;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1rem;
    }

    .action-btn:hover {
        background: #f1f5f9;
        color: #0053b3;
        transform: translateY(-1px);
    }

    .header-notifications {
        position: relative;
    }

    .notif-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        font-size: 1.1rem;
    }

    .notif-btn:hover {
        background: #f1f5f9;
        color: #0053b3;
    }

    .notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #ef4444;
        color: white;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 0.15rem 0.4rem;
        border-radius: 20px;
        min-width: 18px;
        text-align: center;
    }

    .notif-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 0.5rem;
        width: 320px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s ease;
        z-index: 99999;
    }

    .notif-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .notif-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .notif-header a {
        color: #0053b3;
        text-decoration: none;
        font-size: 0.7rem;
    }

    .notif-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .notif-item {
        padding: 0.75rem 1rem;
        display: flex;
        gap: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
        cursor: pointer;
    }

    .notif-item:hover {
        background: #f8fafc;
    }

    .notif-item.unread {
        background: #eff6ff;
    }

    .notif-item i {
        width: 28px;
        height: 28px;
        background: #f1f5f9;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0053b3;
        font-size: 0.8rem;
    }

    .notif-item p {
        margin: 0;
        font-size: 0.8rem;
        font-weight: 500;
        color: #1e293b;
    }

    .notif-item small {
        font-size: 0.65rem;
        color: #94a3b8;
    }

    .notif-footer {
        padding: 0.75rem 1rem;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }

    .notif-footer a {
        color: #64748b;
        text-decoration: none;
        font-size: 0.75rem;
    }

    .header-user {
        position: relative;
    }

    .user-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.4rem 0.75rem 0.4rem 0.5rem;
        border-radius: 40px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .user-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #0053b3, #004099);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .user-info {
        text-align: left;
    }

    .user-name {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #0f172a;
    }

    .user-role {
        display: block;
        font-size: 0.65rem;
        color: #64748b;
    }

    .user-chevron {
        font-size: 0.7rem;
        color: #94a3b8;
        transition: transform 0.2s;
    }

    .user-btn.active .user-chevron {
        transform: rotate(180deg);
    }

    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 0.5rem;
        width: 250px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s ease;
        z-index: 99999;
    }

    .user-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.7rem 1rem;
        color: #334155;
        text-decoration: none;
        font-size: 0.85rem;
        transition: background 0.2s;
        width: 100%;
        border: none;
        background: none;
        cursor: pointer;
    }

    .dropdown-item:hover {
        background: #f8fafc;
        color: #0053b3;
    }

    .dropdown-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 0.25rem 0;
    }

    .logout-btn {
        color: #ef4444;
    }

    .logout-btn:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    .badge-credits {
        margin-left: auto;
        background: #0053b3;
        color: white;
        padding: 0.15rem 0.5rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .header-mobile-toggle {
        display: none;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: none;
        background: transparent;
        color: #0053b3;
        cursor: pointer;
        font-size: 1.3rem;
    }

    @media (max-width: 768px) {
        .header-container {
            padding: 0 1rem;
        }

        .header-mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-right {
            position: fixed;
            top: 0;
            right: -100%;
            width: 280px;
            height: 100vh;
            background: white;
            flex-direction: column;
            align-items: stretch;
            padding: 5rem 1rem 2rem;
            box-shadow: -5px 0 20px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 999;
        }

        .header-right.active {
            right: 0;
        }

        .header-actions {
            border-right: none;
            border-bottom: 1px solid #e2e8f0;
            padding-right: 0;
            padding-bottom: 1rem;
            justify-content: center;
        }

        .header-notifications {
            width: 100%;
        }

        .notif-dropdown,
        .user-dropdown {
            position: static;
            width: 100%;
            box-shadow: none;
            margin-top: 0.5rem;
            opacity: 1;
            visibility: visible;
            transform: none;
            display: none;
        }

        .notif-dropdown.active,
        .user-dropdown.active {
            display: block;
        }

        .user-btn {
            justify-content: space-between;
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileToggle = document.getElementById('mobileToggle');
        const headerRight = document.getElementById('headerRight');
        const userBtn = document.getElementById('userBtn');
        const userDropdown = document.getElementById('userDropdown');
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');

        if (mobileToggle && headerRight) {
            mobileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                headerRight.classList.toggle('active');
            });
        }

        if (userBtn && userDropdown) {
            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
                userBtn.classList.toggle('active');

                if (notifDropdown) {
                    notifDropdown.classList.remove('active');
                }
            });
        }

        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notifDropdown.classList.toggle('active');

                if (userDropdown) {
                    userDropdown.classList.remove('active');
                }

                if (userBtn) {
                    userBtn.classList.remove('active');
                }
            });
        }

        document.addEventListener('click', function() {
            if (userDropdown) {
                userDropdown.classList.remove('active');
            }

            if (userBtn) {
                userBtn.classList.remove('active');
            }

            if (notifDropdown) {
                notifDropdown.classList.remove('active');
            }
        });
    });
</script>