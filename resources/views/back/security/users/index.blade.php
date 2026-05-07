@extends('back.layouts.app')

@section('title', 'Gestion des Utilisateurs | Data Rocket')

@section('content')
    <style>
        .sec-page {
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .sec-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .sec-title h1 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 800;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .sec-title p {
            color: #64748b;
            margin: 0.5rem 0 0;
            font-size: 0.9rem;
        }

        .sec-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
        }

        .sec-card:hover {
            box-shadow: 0 25px 40px -15px rgba(0, 0, 0, 0.1);
        }

        .sec-search {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .sec-input {
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.7rem 1rem;
            min-width: 280px;
            outline: none;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .sec-input:focus {
            border-color: #0053b3;
            box-shadow: 0 0 0 3px rgba(0, 83, 179, 0.1);
        }

        .sec-btn {
            border: none;
            border-radius: 0.75rem;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            cursor: pointer;
            background: #0053b3;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            letter-spacing: 0.3px;
        }

        .sec-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
            box-shadow: 0 6px 14px rgba(0, 83, 179, 0.25);
        }

        .sec-btn:active {
            transform: translateY(0);
        }

        .sec-btn.green {
            background: #10b981;
        }

        .sec-btn.red {
            background: #ef4444;
        }

        .sec-btn.orange {
            background: #f59e0b;
        }

        .sec-btn.gray {
            background: #64748b;
        }

        .sec-btn.dark {
            background: #1e293b;
        }

        .sec-btn.purple {
            background: #8b5cf6;
        }

        .sec-table-wrap {
            overflow-x: auto;
            border-radius: 1rem;
        }

        .sec-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .sec-table th {
            background: #f8fafc;
            text-align: left;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .sec-table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid #eef2f6;
            vertical-align: middle;
            font-size: 0.85rem;
            color: #1e293b;
        }

        .sec-table tr:hover td {
            background-color: #fafcff;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-block;
            white-space: nowrap;
        }

        .badge.green {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.red {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.gray {
            background: #f1f5f9;
            color: #475569;
        }

        .badge.yellow {
            background: #fed7aa;
            color: #9a3412;
        }

        .credit-form {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        .credit-form input {
            width: 75px;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.5rem;
            text-align: center;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .alert-success {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            color: #065f46;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        .alert-error {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
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

        .credit-number {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0053b3, #3b82f6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tooltip {
            position: relative;
            cursor: help;
            border-bottom: 1px dashed #cbd5e1;
        }

        .tooltip::after {
            content: attr(data-tip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 0.5rem;
            font-size: 0.7rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 100;
        }

        .tooltip:hover::after {
            opacity: 1;
        }

        .pagination {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .sec-page {
                padding: 1rem;
            }

            .sec-card {
                padding: 1rem;
            }

            .actions,
            .credit-form {
                flex-direction: column;
            }
        }
    </style>

    <div class="sec-page">
        <div class="sec-header">
            <div class="sec-title">
                <h1>🔐 Gestion des utilisateurs</h1>
                <p>Gérez les comptes, crédits, suspensions, accès admin et sécurité</p>
            </div>

            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="{{ route('admin.security.blocked.index') }}" class="sec-btn dark">
                    🚫 Identités bloquées
                </a>
                <a href="#" class="sec-btn purple">
                    📥 Export CSV
                </a>
            </div>

            <!-- Dans le sec-header, après le lien 'Identités bloquées' -->
            <div class="sec-header">
                <div class="sec-title">
                    <h1>🔐 Gestion des utilisateurs</h1>
                    <p>Gérez les comptes, crédits, suspensions, accès admin et sécurité</p>
                </div>

                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="{{ route('admin.security.users.create') }}" class="sec-btn green" style="background: #10b981;">
                        ➕ Ajouter un utilisateur
                    </a>
                    <a href="{{ route('admin.security.blocked.index') }}" class="sec-btn dark">
                        🚫 Identités bloquées
                    </a>
                    <a href="#" class="sec-btn purple">
                        📥 Export CSV
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert-success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert-error">
                ❌ {{ session('error') }}
            </div>
        @endif

        <div class="sec-card">
            <form method="GET" action="{{ route('admin.security.users.index') }}" class="sec-search">
                <input type="text" name="q" value="{{ request('q') }}" class="sec-input"
                    placeholder="🔍 Rechercher par nom, email ou téléphone...">
                <button class="sec-btn" type="submit">Rechercher</button>
                <a href="{{ route('admin.security.users.index') }}" class="sec-btn gray">Réinitialiser</a>
            </form>
        </div>

        <div class="sec-card">
            <div class="sec-table-wrap">
                <table class="sec-table">
                    <thead>
                        <tr>
                            <th>👤 Utilisateur</th>
                            <th>📌 Statut</th>
                            <th>💰 Crédits</th>
                            <th>📊 Plan</th>
                            <th>🌐 Dernière IP</th>
                            <th>🎁 Attribution</th>
                            <th>⚙️ Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <strong class="tooltip" data-tip="{{ $user->id }}">{{ $user->name }}</strong><br>
                                    <span style="font-size: 0.75rem; color: #475569;">{{ $user->email }}</span><br>
                                    <span
                                        style="font-size: 0.7rem; color: #94a3b8;">{{ $user->phone ?? 'Téléphone non renseigné' }}</span>
                                </td>

                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        @if($user->is_admin)
                                            <span class="badge blue">👑 Admin</span>
                                        @endif
                                        @if($user->is_active)
                                            <span class="badge green">🟢 Actif</span>
                                        @else
                                            <span class="badge red">🔴 Suspendu</span>
                                        @endif
                                        @if($user->email_verified_at)
                                            <span class="badge green">📧 Vérifié</span>
                                        @else
                                            <span class="badge gray">📧 Non vérifié</span>
                                        @endif
                                        @if($user->phone_verified_at)
                                            <span class="badge green">📱 Vérifié</span>
                                        @else
                                            <span class="badge gray">📱 Non vérifié</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <span class="credit-number">{{ number_format($user->credits ?? 0, 0, ',', ' ') }}</span>
                                </td>

                                <td>
                                    <span class="badge {{ $user->plan === 'premium' ? 'green' : 'gray' }}">
                                        {{ ucfirst($user->plan ?? 'free') }}
                                    </span>
                                </td>

                                <td>
                                    <code style="font-size: 0.7rem;">{{ $user->last_login_ip ?? '-' }}</code><br>
                                    <small
                                        style="color: #94a3b8;">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?? '-' }}</small>
                                </td>

                                <td>
                                    <form method="POST" action="{{ route('admin.security.users.giveCredits') }}"
                                        class="credit-form">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                        <input type="number" name="amount" min="1" value="10" required>
                                        <input type="hidden" name="reason" value="Crédits attribués manuellement">
                                        <button class="sec-btn green" type="submit">➕ Ajouter</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.security.users.removeCredits') }}"
                                        class="credit-form">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                        <input type="number" name="amount" min="1" value="10" required>
                                        <input type="hidden" name="reason" value="Crédits retirés manuellement">
                                        <button class="sec-btn orange" type="submit">➖ Retirer</button>
                                    </form>
                                </td>

                                <td>
                                    <div class="actions">
                                        <form method="POST" action="{{ route('admin.security.users.toggleActive', $user) }}"
                                            class="action-form">
                                            @csrf
                                            <button class="sec-btn {{ $user->is_active ? 'red' : 'green' }}" type="submit">
                                                {{ $user->is_active ? '⛔ Suspendre' : '✅ Réactiver' }}
                                            </button>
                                        </form>

                                        @if(!$user->is_admin)
                                            <form method="POST" action="{{ route('admin.security.users.makeAdmin', $user) }}">
                                                @csrf
                                                <button class="sec-btn purple" type="submit">
                                                    👑 Admin
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.security.users.removeAdmin', $user) }}">
                                                @csrf
                                                <button class="sec-btn gray" type="submit">
                                                    ⬇️ Retirer admin
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.security.blocked.store') }}"
                                            class="ban-form">
                                            @csrf
                                            <input type="hidden" name="type" value="user">
                                            <input type="hidden" name="value" value="{{ $user->id }}">
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="reason" value="Utilisateur banni depuis la sécurité">
                                            <button class="sec-btn red" type="submit">
                                                🚫 Bannir
                                            </button>
                                        </form>

                                        <form method="POST" action="#" class="action-form">
                                            @csrf
                                            <button class="sec-btn blue" type="submit">
                                                ✓ Vérifier email
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.security.users.toggleOtpBypass', $user) }}">
                                            @csrf
                                            <button class="sec-btn {{ $user->otp_bypass ? 'green' : 'gray' }}" type="submit">
                                                🔓 {{ $user->otp_bypass ? 'Passerelle ON' : 'Passerelle OFF' }}
                                            </button>
                                        </form>

                                        @if($user->last_login_ip)
                                            <form method="POST" action="{{ route('admin.security.blocked.store') }}"
                                                class="ip-ban-form">
                                                @csrf
                                                <input type="hidden" name="type" value="ip">
                                                <input type="hidden" name="value" value="{{ $user->last_login_ip }}">
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <input type="hidden" name="reason" value="IP bannie depuis sécurité">
                                                <button class="sec-btn red" type="submit">
                                                    🌍 Bloc IP
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Lien vers l'édition -->
                                        <a href="{{ route('admin.security.users.edit', $user) }}" class="sec-btn blue"
                                            style="background: #3b82f6; text-decoration: none; padding: 0.4rem 0.8rem;">
                                            ✏️ Modifier
                                        </a>

                                        <!-- Vos autres boutons existants -->
                                        <form method="POST" action="{{ route('admin.security.users.toggleActive', $user) }}"
                                            class="action-form">
                                            @csrf
                                            <button class="sec-btn {{ $user->is_active ? 'red' : 'green' }}" type="submit">
                                                {{ $user->is_active ? '⛔ Suspendre' : '✅ Réactiver' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <div style="color: #64748b;">
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

    <script>
        (function () {
            // Confirmation avant action sensible
            const confirmForms = document.querySelectorAll('.action-form, .ban-form, .ip-ban-form');
            confirmForms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const action = this.querySelector('button')?.innerText || 'cette action';
                    if (!confirm(`⚠️ Êtes-vous sûr de vouloir effectuer "${action}" ?`)) {
                        e.preventDefault();
                    }
                });
            });

            // Auto-effacement des alerts
            setTimeout(() => {
                document.querySelectorAll('.alert-success, .alert-error').forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);
        })();
    </script>
@endsection