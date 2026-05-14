@extends('back.layouts.app')

@section('title', 'Identités bloquées | Data Rocket')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    .blocked-page{padding:28px;background:#f8fafc;min-height:100vh}
    .blocked-container{max-width:1200px;margin:0 auto}
    .blocked-header{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:22px}
    .blocked-header h1{margin:0;color:#0f172a;font-size:30px;font-weight:950}
    .blocked-header p{margin:8px 0 0;color:#64748b}
    .panel{background:white;border:1px solid #e2e8f0;border-radius:24px;box-shadow:0 12px 35px rgba(15,23,42,.05);margin-bottom:20px;overflow:hidden}
    .panel-body{padding:22px}
    .btn{border:none;border-radius:14px;padding:11px 15px;font-weight:900;text-decoration:none;cursor:pointer;display:inline-flex;gap:8px;align-items:center}
    .btn-primary{background:#0053b3;color:white}
    .btn-gray{background:#f1f5f9;color:#334155}
    .btn-danger{background:#b91c1c;color:white}
    .filters{display:grid;grid-template-columns:1fr 220px auto auto;gap:12px}
    input,select{width:100%;border:1.5px solid #e2e8f0;border-radius:14px;padding:12px 14px;outline:none}
    input:focus,select:focus{border-color:#0053b3;box-shadow:0 0 0 4px rgba(0,83,179,.10)}
    .table-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    th{background:#f8fafc;color:#64748b;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.08em;padding:14px;border-bottom:1px solid #e2e8f0}
    td{padding:14px;border-bottom:1px solid #e2e8f0;color:#334155;vertical-align:middle}
    tr:hover td{background:#f8fafc}
    .badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900}
    .badge-red{background:#fee2e2;color:#991b1b}
    .badge-green{background:#dcfce7;color:#166534}
    .badge-gray{background:#f1f5f9;color:#475569}
    .alert{padding:14px 16px;border-radius:16px;margin-bottom:18px;font-weight:800}
    .alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
    .empty{text-align:center;padding:45px;color:#64748b}
    .pagination-wrap{padding:18px;display:flex;justify-content:center}
    .form-grid{display:grid;grid-template-columns:180px 1fr 1fr 180px auto;gap:12px;align-items:end}
    .form-group label{display:block;font-size:12px;color:#64748b;font-weight:900;margin-bottom:6px;text-transform:uppercase}
    @media(max-width:900px){.filters,.form-grid{grid-template-columns:1fr}.blocked-header{flex-direction:column}}
</style>

<div class="blocked-page">
    <div class="blocked-container">

        <div class="blocked-header">
            <div>
                <h1>Identités bloquées</h1>
                <p>Gérez les IP, emails, téléphones ou utilisateurs interdits d’accès.</p>
            </div>

            <a href="{{ route('admin.security.users.index') }}" class="btn btn-gray">
                <i class="fa-solid fa-arrow-left"></i>
                Retour utilisateurs
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="panel">
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.security.blocked.store') }}">
                    @csrf

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" required>
                                <option value="ip">IP</option>
                                <option value="email">Email</option>
                                <option value="phone">Téléphone</option>
                                <option value="user">Utilisateur</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Valeur</label>
                            <input type="text" name="value" placeholder="IP, email, téléphone ou ID user" required>
                        </div>

                        <div class="form-group">
                            <label>Raison</label>
                            <input type="text" name="reason" placeholder="Raison du blocage">
                        </div>

                        <div class="form-group">
                            <label>Expiration</label>
                            <input type="datetime-local" name="expires_at">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-ban"></i>
                            Bloquer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-body">
                <form method="GET" action="{{ route('admin.security.blocked.index') }}" class="filters">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher une valeur bloquée...">

                    <select name="type">
                        <option value="">Tous les types</option>
                        <option value="ip" {{ request('type') === 'ip' ? 'selected' : '' }}>IP</option>
                        <option value="email" {{ request('type') === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="phone" {{ request('type') === 'phone' ? 'selected' : '' }}>Téléphone</option>
                        <option value="user" {{ request('type') === 'user' ? 'selected' : '' }}>Utilisateur</option>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-search"></i>
                        Filtrer
                    </button>

                    <a href="{{ route('admin.security.blocked.index') }}" class="btn btn-gray">
                        Réinitialiser
                    </a>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Valeur</th>
                            <th>Raison</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($blockedIdentities as $blocked)
                            <tr>
                                <td>
                                    <span class="badge badge-gray">{{ strtoupper($blocked->type) }}</span>
                                </td>

                                <td>
                                    <strong>{{ $blocked->value }}</strong>
                                </td>

                                <td>{{ $blocked->reason ?? '-' }}</td>

                                <td>
                                    {{ $blocked->expires_at ? $blocked->expires_at->format('d/m/Y H:i') : 'Permanent' }}
                                </td>

                                <td>
                                    @if($blocked->is_active)
                                        <span class="badge badge-red">Actif</span>
                                    @else
                                        <span class="badge badge-green">Désactivé</span>
                                    @endif
                                </td>

                                <td style="text-align:right;">
                                    <form method="POST" action="{{ route('admin.security.blocked.toggle', $blocked) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-gray">
                                            {{ $blocked->is_active ? 'Désactiver' : 'Réactiver' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.security.blocked.destroy', $blocked) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce blocage ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty">
                                        Aucune identité bloquée.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">
                {{ $blockedIdentities->links() }}
            </div>
        </div>

    </div>
</div>

@endsection