<aside class="sidebar">

    <h2 style="margin-bottom:20px;">⚡ Data 360</h2>

    @auth
        @if(auth()->user()->is_admin)

            <a href="{{ route('back.dashboard') }}">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>

            <a href="{{ route('back.adresses.index') }}">
                <i class="fa-solid fa-location-dot"></i> Adresses
            </a>


            <a href="{{ route('back.batiments.index') }}">
                <i class="fa-solid fa-building"></i> Bâtiments
            </a>

            <a href="{{ route('back.coproprietes.index') }}">
                <i class="fa-solid fa-city"></i> Copropriétés
            </a>

            <a href="{{ route('back.syndics.index') }}">
                <i class="fa-solid fa-user-tie"></i> Syndics
            </a>

            <a href="{{ route('back.recherches.index') }}">
                <i class="fa-solid fa-magnifying-glass"></i> Recherches
            </a>

            <a href="{{ route('back.imports.index') }}">
                <i class="fa-solid fa-file-csv"></i> Imports
            </a>

            <div style="
                                margin:20px 0 10px;
                                padding-top:15px;
                                border-top:1px solid rgba(255,255,255,0.12);
                                font-size:11px;
                                letter-spacing:1px;
                                text-transform:uppercase;
                                opacity:.7;
                            ">
                Sécurité
            </div>

            <a href="{{ route('admin.security.users.index') }}">
                <i class="fa-solid fa-shield-halved"></i>
                Utilisateurs & Crédits
            </a>

            <a href="{{ route('admin.security.blocked.index') }}">
                <i class="fa-solid fa-ban"></i>
                Identités bloquées
            </a>


            <!-- Notifications Dropdown -->
            <li class="sidebar-dropdown">
                <a href="#" class="sidebar-dropdown-toggle">
                    <i class="fa-regular fa-bell"></i>
                    <span>Notifications</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </a>
                <ul class="sidebar-dropdown-menu">
                    <li>
                        <a href="{{ route('back.notifications.index') }}">
                            <i class="fa-regular fa-list"></i> Toutes les notifications
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('back.notifications.create') }}">
                            <i class="fa-solid fa-plus"></i> Créer une notification
                        </a>
                    </li>
                    <li>
                        <a href="#" id="sidebarMarkAllRead">
                            <i class="fa-regular fa-check-circle"></i> Tout marquer comme lu
                        </a>
                    </li>
                    <hr class="sidebar-divider">
                    <li>
                        <a href="{{ route('back.notifications.index') }}?is_global=1">
                            <i class="fa-solid fa-globe"></i> Notifications globales
                        </a>
                    </li>
                    <li>
                        <a href="#" id="sidebarUnreadCount">
                            <i class="fa-regular fa-envelope"></i> Non lues
                            <span class="badge-notif" id="sidebarNotifBadge">0</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Dans le else du sidebar (pour les non-admins) -->
        @else
            <a href="{{ route('dashboard') }}">
                <i class="fa-solid fa-house"></i> Mon espace
            </a>

            <a href="{{ route('front.home') }}">
                <i class="fa-solid fa-magnifying-glass"></i> Nouvelle recherche
            </a>

            <a href="#">
                <i class="fa-solid fa-coins"></i> Mes crédits
            </a>

            <a href="#">
                <i class="fa-solid fa-clock-rotate-left"></i> Mes recherches
            </a>

            <a href="#">
                <i class="fa-solid fa-user"></i> Mon profil
            </a>

            <a href="{{ route('notifications.index') }}">
                <i class="fa-regular fa-bell"></i> Mes notifications
                <span id="userNotifBadge" class="badge-notif"
                    style="margin-left: auto; background: #ef4444; color: white; font-size: 0.65rem; padding: 0.15rem 0.4rem; border-radius: 20px; display: none;">0</span>
            </a>
        @endif
    @endauth

</aside>