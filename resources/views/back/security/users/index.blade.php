@extends('back.layouts.app')

@section('title', 'Gestion des Utilisateurs | Data Rocket')

@section('content')
    <style>
        /* ============================================
           DESIGN SYSTEM - PROFESSIONAL UI/UX
        ============================================ */
        :root {
            --primary: #0053b3;
            --primary-dark: #003d85;
            --primary-light: #e6f0ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.25rem;
        }

        .users-page {
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            min-height: 100vh;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-title h1 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-800) 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .page-title p {
            color: var(--gray-500);
            margin: 0.5rem 0 0;
            font-size: 0.875rem;
        }

        /* Cards */
        .card-modern {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .card-modern:hover {
            box-shadow: var(--shadow-md);
        }

        /* Search */
        .search-form {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input {
            flex: 1;
            min-width: 280px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            outline: none;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 83, 179, 0.1);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-gray {
            background: var(--gray-500);
            color: white;
        }

        .btn-dark {
            background: var(--gray-800);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--gray-200);
            color: var(--gray-600);
        }

        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius-lg);
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th {
            background: var(--gray-50);
            text-align: left;
            color: var(--gray-600);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .user-table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
            font-size: 0.875rem;
            color: var(--gray-700);
        }

        .user-table tr:hover td {
            background-color: var(--gray-50);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-gray {
            background: #f1f5f9;
            color: #475569;
        }

        .badge-warning {
            background: #fed7aa;
            color: #9a3412;
        }

        /* Credits */
        .credits-number {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--info));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Action Menu (3 dots) */
        .action-menu {
            position: relative;
            display: inline-block;
        }

        .menu-trigger {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
            color: var(--gray-500);
        }

        .menu-trigger:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .menu-trigger i {
            font-size: 1.2rem;
        }

        .menu-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            min-width: 200px;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }

        .action-menu.active .menu-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.75rem 1rem;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--gray-700);
            transition: all 0.2s ease;
        }

        .menu-item:hover {
            background: var(--gray-50);
            color: var(--primary);
        }

        .menu-item.danger {
            color: var(--danger);
        }

        .menu-item.danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .menu-divider {
            height: 1px;
            background: var(--gray-200);
            margin: 0.25rem 0;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            background: white;
            border-radius: var(--radius-xl);
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            transform: scale(0.95);
            transition: transform 0.2s ease;
        }

        .modal-overlay.active .modal-container {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-400);
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: var(--gray-600);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Credit Form in Modal */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 83, 179, 0.1);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #ecfdf5;
            border-left: 4px solid var(--success);
            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;
            border-left: 4px solid var(--danger);
            color: #991b1b;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Pagination */
        .pagination {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .pagination nav {
            display: flex;
            gap: 0.25rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            color: var(--gray-600);
            text-decoration: none;
        }

        .pagination a:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .pagination .active span {
            background: var(--primary);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .users-page {
                padding: 1rem;
            }
            .card-modern {
                padding: 1rem;
            }
            .user-info {
                margin-bottom: 0.5rem;
            }
        }
    </style>

    <div class="users-page">

        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <h1>🔐 Gestion des utilisateurs</h1>
                <p>Gérez les comptes, crédits, permissions et sécurité</p>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="{{ route('admin.security.users.create') }}" class="btn btn-success">
                    ➕ Ajouter un utilisateur
                </a>
                <a href="{{ route('admin.security.blocked.index') }}" class="btn btn-dark">
                    🚫 Identités bloquées
                </a>
                <a href="#" class="btn btn-info">
                    📥 Export CSV
                </a>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                ❌ {{ session('error') }}
            </div>
        @endif

        <!-- Search Card -->
        <div class="card-modern">
            <form method="GET" action="{{ route('admin.security.users.index') }}" class="search-form">
                <input type="text" name="q" value="{{ request('q') }}" class="search-input"
                    placeholder="🔍 Rechercher par nom, email ou téléphone...">
                <button class="btn btn-primary" type="submit">Rechercher</button>
                <a href="{{ route('admin.security.users.index') }}" class="btn btn-outline">Réinitialiser</a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card-modern">
            <div class="table-wrapper">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>👤 Utilisateur</th>
                            <th>📌 Statut</th>
                            <th>💰 Crédits</th>
                            <th>📊 Plan</th>
                            <th>🌐 Dernière IP</th>
                            <th style="width: 50px;">⚙️</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="user-info">
                                    <strong style="font-size: 0.95rem;">{{ $user->name }}</strong><br>
                                    <span style="font-size: 0.75rem; color: var(--gray-500);">{{ $user->email }}</span><br>
                                    <span style="font-size: 0.7rem; color: var(--gray-400);">{{ $user->phone ?? 'Téléphone non renseigné' }}</span>
                                    <span style="font-size: 0.65rem; color: var(--gray-400); display: block;">ID: #{{ $user->id }}</span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        @if($user->is_admin)
                                            <span class="badge badge-info">👑 Admin</span>
                                        @endif
                                        @if($user->is_active)
                                            <span class="badge badge-success">🟢 Actif</span>
                                        @else
                                            <span class="badge badge-danger">🔴 Suspendu</span>
                                        @endif
                                        @if($user->email_verified_at)
                                            <span class="badge badge-success">📧 Vérifié</span>
                                        @else
                                            <span class="badge badge-gray">📧 Non vérifié</span>
                                        @endif
                                        @if($user->phone_verified_at)
                                            <span class="badge badge-success">📱 Vérifié</span>
                                        @else
                                            <span class="badge badge-gray">📱 Non vérifié</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="credits-number">{{ number_format($user->credits ?? 0, 0, ',', ' ') }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $user->plan === 'premium' ? 'badge-success' : 'badge-gray' }}">
                                        {{ ucfirst($user->plan ?? 'free') }}
                                    </span>
                                </td>
                                <td>
                                    <code style="font-size: 0.7rem;">{{ $user->last_login_ip ?? '-' }}</code><br>
                                    <small style="color: var(--gray-400);">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '-' }}</small>
                                </td>
                                <td>
                                    <!-- Action Menu (3 dots) -->
                                    <div class="action-menu" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                        <button class="menu-trigger" onclick="toggleMenu(this)">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="menu-dropdown">
                                            <button class="menu-item" onclick="openActionModal('edit', {{ $user->id }}, '{{ $user->name }}')">
                                                ✏️ Modifier l'utilisateur
                                            </button>
                                            <button class="menu-item" onclick="openActionModal('credits_add', {{ $user->id }}, '{{ $user->name }}')">
                                                ➕ Ajouter des crédits
                                            </button>
                                            <button class="menu-item" onclick="openActionModal('credits_remove', {{ $user->id }}, '{{ $user->name }}')">
                                                ➖ Retirer des crédits
                                            </button>
                                            <div class="menu-divider"></div>
                                            <button class="menu-item" onclick="openActionModal('status', {{ $user->id }}, '{{ $user->name }}', '{{ $user->is_active ? 'suspendre' : 'réactiver' }}')">
                                                {{ $user->is_active ? '⛔ Suspendre le compte' : '✅ Réactiver le compte' }}
                                            </button>
                                            @if(!$user->is_admin)
                                                <button class="menu-item" onclick="openActionModal('make_admin', {{ $user->id }}, '{{ $user->name }}')">
                                                    👑 Nommer administrateur
                                                </button>
                                            @else
                                                <button class="menu-item" onclick="openActionModal('remove_admin', {{ $user->id }}, '{{ $user->name }}')">
                                                    ⬇️ Retirer les droits admin
                                                </button>
                                            @endif
                                            <div class="menu-divider"></div>
                                            <button class="menu-item" onclick="openActionModal('verify_email', {{ $user->id }}, '{{ $user->name }}')">
                                                ✓ Vérifier l'email
                                            </button>
                                            <button class="menu-item" onclick="openActionModal('otp_bypass', {{ $user->id }}, '{{ $user->name }}', '{{ $user->otp_bypass ? 'désactiver' : 'activer' }}')">
                                                🔓 {{ $user->otp_bypass ? 'Désactiver' : 'Activer' }} le laissez-passer OTP
                                            </button>
                                            <div class="menu-divider"></div>
                                            <button class="menu-item danger" onclick="openActionModal('ban', {{ $user->id }}, '{{ $user->name }}')">
                                                🚫 Bannir l'utilisateur
                                            </button>
                                            @if($user->last_login_ip)
                                                <button class="menu-item danger" onclick="openActionModal('ban_ip', {{ $user->id }}, '{{ $user->name }}', '{{ $user->last_login_ip }}')">
                                                    🌍 Bloquer l'IP ({{ $user->last_login_ip }})
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem;">
                                    <div style="color: var(--gray-500);">
                                        🔍 Aucun utilisateur trouvé
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                {{ $users->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div id="actionModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modalTitle">Action utilisateur</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="actionForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p id="modalMessage"></p>
                    <div id="modalExtraFields" style="display: none;">
                        <div class="form-group">
                            <label id="extraLabel" for="extraValue"></label>
                            <input type="number" id="extraValue" name="amount" min="1" value="10">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="modalConfirmBtn">Confirmer</button>
                </div>
            </form>
        </div>
    </div>

  <script>
    // Menu toggle
    function toggleMenu(btn) {
        document.querySelectorAll('.action-menu').forEach(menu => {
            if (menu !== btn.closest('.action-menu')) {
                menu.classList.remove('active');
            }
        });
        const menu = btn.closest('.action-menu');
        menu.classList.toggle('active');
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-menu')) {
            document.querySelectorAll('.action-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    // ⭐⭐⭐ FONCTIONS POUR GÉNÉRER LES ROUTES SANS PARAMÈTRES MANQUANTS ⭐⭐⭐
    const BASE_URL = "{{ url('/admin/security/users') }}";

    function getRoute(path, userId = null) {
        if (userId) {
            return BASE_URL + '/' + userId + path;
        }
        return BASE_URL + path;
    }

    // Routes disponibles
    const ROUTES = {
        giveCredits: "{{ route('admin.security.users.giveCredits') }}",
        removeCredits: "{{ route('admin.security.users.removeCredits') }}",
        blockedStore: "{{ route('admin.security.blocked.store') }}"
    };

    // Modal elements
    const modal = document.getElementById('actionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const actionForm = document.getElementById('actionForm');
    const modalExtraFields = document.getElementById('modalExtraFields');
    const extraLabel = document.getElementById('extraLabel');
    const extraValue = document.getElementById('extraValue');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');

    let currentUserId = null;
    let currentAction = null;

    function openActionModal(action, userId, userName, extra = null) {
        currentUserId = userId;
        currentAction = action;

        // Reset modal
        modalExtraFields.style.display = 'none';
        modalConfirmBtn.className = 'btn btn-primary';

        // Clear previous hidden inputs
        const existingHidden = actionForm.querySelectorAll('input[type="hidden"]:not([name="_token"])');
        existingHidden.forEach(input => input.remove());

        switch(action) {
            case 'edit':
                // Redirection directe
                window.location.href = BASE_URL + '/' + userId + '/edit';
                return;

            case 'credits_add':
                actionForm.action = ROUTES.giveCredits;
                modalTitle.innerText = 'Ajouter des crédits';
                modalMessage.innerHTML = `Ajouter des crédits à <strong>${userName}</strong> :`;
                modalExtraFields.style.display = 'block';
                extraLabel.innerText = 'Nombre de crédits';
                extraValue.value = 10;
                break;

            case 'credits_remove':
                actionForm.action = ROUTES.removeCredits;
                modalTitle.innerText = 'Retirer des crédits';
                modalMessage.innerHTML = `Retirer des crédits à <strong>${userName}</strong> :`;
                modalExtraFields.style.display = 'block';
                extraLabel.innerText = 'Nombre de crédits';
                extraValue.value = 10;
                break;

            case 'status':
                const statusAction = extra === 'suspendre' ? 'suspendre' : 'réactiver';
                actionForm.action = BASE_URL + '/' + userId + '/toggle-active';
                modalTitle.innerText = statusAction === 'suspendre' ? 'Suspendre le compte' : 'Réactiver le compte';
                modalMessage.innerHTML = `Êtes-vous sûr de vouloir <strong>${statusAction}</strong> l'utilisateur <strong>${userName}</strong> ?`;
                modalConfirmBtn.className = 'btn btn-warning';
                break;

            case 'make_admin':
                actionForm.action = BASE_URL + '/' + userId + '/make-admin';
                modalTitle.innerText = 'Nommer administrateur';
                modalMessage.innerHTML = `Êtes-vous sûr de vouloir nommer <strong>${userName}</strong> comme administrateur ?`;
                break;

            case 'remove_admin':
                actionForm.action = BASE_URL + '/' + userId + '/remove-admin';
                modalTitle.innerText = 'Retirer les droits admin';
                modalMessage.innerHTML = `Êtes-vous sûr de vouloir retirer les droits administrateur à <strong>${userName}</strong> ?`;
                break;

            case 'verify_email':
                actionForm.action = BASE_URL + '/' + userId + '/verify-email';
                modalTitle.innerText = 'Vérifier l\'email';
                modalMessage.innerHTML = `Marquer l'email de <strong>${userName}</strong> comme vérifié ?`;
                break;

            case 'otp_bypass':
                const bypassAction = extra === 'activer' ? 'activer' : 'désactiver';
                actionForm.action = BASE_URL + '/' + userId + '/toggle-otp-bypass';
                modalTitle.innerText = bypassAction === 'activer' ? 'Activer le laissez-passer OTP' : 'Désactiver le laissez-passer OTP';
                modalMessage.innerHTML = `Êtes-vous sûr de vouloir <strong>${bypassAction}</strong> le laissez-passer OTP pour <strong>${userName}</strong> ?`;
                break;

            case 'ban':
                actionForm.action = BASE_URL + '/' + userId + '/ban';
                modalTitle.innerText = 'Bannir l\'utilisateur';
                modalMessage.innerHTML = `<strong style="color: #ef4444;">⚠️ Action irréversible</strong><br>Êtes-vous sûr de vouloir <strong>bannir définitivement</strong> l'utilisateur <strong>${userName}</strong> ?`;
                modalConfirmBtn.className = 'btn btn-danger';
                break;

            case 'ban_ip':
                actionForm.action = ROUTES.blockedStore;
                modalTitle.innerText = 'Bloquer l\'adresse IP';
                modalMessage.innerHTML = `Bloquer définitivement l'adresse IP <strong>${extra}</strong> associée à <strong>${userName}</strong> ?`;
                modalConfirmBtn.className = 'btn btn-danger';
                // Ajout des champs pour l'IP ban
                addHiddenField('type', 'ip');
                addHiddenField('value', extra);
                addHiddenField('reason', 'IP bannie depuis l\'interface admin');
                break;

            default:
                return;
        }

        // Ajouter user_id pour toutes les actions sauf edit et ban_ip
        if (action !== 'edit' && action !== 'ban_ip') {
            addHiddenField('user_id', userId);
        }

        // Ajouter reason pour ban
        if (action === 'ban') {
            addHiddenField('reason', 'Utilisateur banni depuis l\'interface admin');
        }

        // Afficher le modal
        modal.classList.add('active');
    }

    function addHiddenField(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        actionForm.appendChild(input);
    }

    function closeModal() {
        modal.classList.remove('active');
        actionForm.action = '';
        modalConfirmBtn.className = 'btn btn-primary';
    }

    // Close modal on overlay click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
</script>
@endsection
