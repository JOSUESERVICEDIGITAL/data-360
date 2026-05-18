@extends('back.layouts.app')

@section('title', 'Copropriétés')

@section('content')

    <style>
        :root {
            --copro-primary: #0f172a;
            --copro-secondary: #334155;
            --copro-muted: #64748b;
            --copro-soft: #f8fafc;
            --copro-border: #e2e8f0;
            --copro-blue: #2563eb;
            --copro-blue-dark: #1d4ed8;
            --copro-success: #16a34a;
            --copro-danger: #dc2626;
            --copro-warning: #f59e0b;
            --copro-white: #ffffff;
        }

        .copro-wrapper {
            padding: 24px;
        }

        .copro-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .copro-title h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: var(--copro-primary);
        }

        .copro-title p {
            margin: 6px 0 0;
            color: var(--copro-muted);
            font-size: .95rem;
        }

        .copro-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .copro-btn {
            border: none;
            border-radius: 14px;
            padding: 12px 18px;
            font-size: .85rem;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .copro-btn-primary {
            background: var(--copro-blue);
            color: white;
        }

        .copro-btn-primary:hover {
            background: var(--copro-blue-dark);
            transform: translateY(-2px);
        }

        .copro-btn-danger {
            background: #fee2e2;
            color: var(--copro-danger);
        }

        .copro-btn-danger:hover {
            background: #fecaca;
        }

        .copro-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .copro-stat-card {
            background: white;
            border: 1px solid var(--copro-border);
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, .04);
        }

        .copro-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: #eff6ff;
            color: var(--copro-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 14px;
        }

        .copro-stat-label {
            color: var(--copro-muted);
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 700;
        }

        .copro-stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--copro-primary);
            margin-top: 6px;
        }

        .copro-card {
            background: white;
            border-radius: 24px;
            border: 1px solid var(--copro-border);
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .05);
        }

        .copro-card-header {
            padding: 22px 24px;
            border-bottom: 1px solid var(--copro-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .copro-search {
            position: relative;
            width: 320px;
            max-width: 100%;
        }

        .copro-search input {
            width: 100%;
            border: 1px solid var(--copro-border);
            background: var(--copro-soft);
            border-radius: 14px;
            padding: 12px 14px 12px 42px;
            outline: none;
            transition: .2s;
        }

        .copro-search input:focus {
            border-color: var(--copro-blue);
            background: white;
        }

        .copro-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--copro-muted);
        }

        .copro-table-wrapper {
            overflow-x: auto;
        }

        .copro-table {
            width: 100%;
            border-collapse: collapse;
        }

        .copro-table thead {
            background: #f8fafc;
        }

        .copro-table th {
            padding: 16px;
            text-align: left;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--copro-muted);
            font-weight: 800;
            white-space: nowrap;
        }

        .copro-table td {
            padding: 18px 16px;
            border-top: 1px solid var(--copro-border);
            vertical-align: top;
        }

        .copro-table tbody tr {
            transition: .2s;
        }

        .copro-table tbody tr:hover {
            background: #f8fafc;
        }

        .copro-name {
            font-weight: 800;
            color: var(--copro-primary);
            margin-bottom: 6px;
        }

        .copro-sub {
            color: var(--copro-muted);
            font-size: .85rem;
        }

        .copro-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: .75rem;
            font-weight: 700;
        }

        .copro-badge-success {
            background: #dcfce7;
            color: var(--copro-success);
        }

        .copro-badge-warning {
            background: #fef3c7;
            color: #b45309;
        }

        .copro-code {
            background: #f1f5f9;
            padding: 6px 10px;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 700;
            color: var(--copro-primary);
        }

        .copro-dropdown {
            position: relative;
        }

        .copro-dropdown-btn {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--copro-border);
            background: white;
            cursor: pointer;
            transition: .2s;
        }

        .copro-dropdown-btn:hover {
            background: #f8fafc;
        }

        .copro-dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            width: 220px;
            background: white;
            border: 1px solid var(--copro-border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(15, 23, 42, .12);
            display: none;
            z-index: 100;
        }

        .copro-dropdown.active .copro-dropdown-menu {
            display: block;
        }

        .copro-dropdown-menu a,
        .copro-dropdown-menu button {
            width: 100%;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: none;
            background: white;
            text-decoration: none;
            color: var(--copro-secondary);
            font-size: .88rem;
            cursor: pointer;
            transition: .2s;
            text-align: left;
        }

        .copro-dropdown-menu a:hover,
        .copro-dropdown-menu button:hover {
            background: #f8fafc;
        }

        .copro-dropdown-menu .danger {
            color: var(--copro-danger);
        }

        .copro-empty {
            padding: 60px 20px;
            text-align: center;
        }

        .copro-empty i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .copro-empty h3 {
            margin-bottom: 8px;
            color: var(--copro-primary);
        }

        .copro-empty p {
            color: var(--copro-muted);
        }

        .copro-pagination {
            padding: 22px;
            border-top: 1px solid var(--copro-border);
        }

        @media(max-width: 768px) {
            .copro-wrapper {
                padding: 14px;
            }

            .copro-topbar {
                align-items: flex-start;
            }

            .copro-title h1 {
                font-size: 1.5rem;
            }
        }
    </style>

    <div class="copro-wrapper">

        <div class="copro-topbar">
            <div class="copro-title">
                <h1>Copropriétés</h1>
                <p>Gestion SaaS des copropriétés, syndics et immatriculations.</p>
            </div>

            <div class="copro-actions">
                <a href="{{ route('back.coproprietes.create') }}" class="copro-btn copro-btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter une copropriété
                </a>

                <form action="{{ route('back.coproprietes.reset') }}" method="POST"
                    onsubmit="return confirm('Supprimer toutes les copropriétés ?')">
                    @csrf
                    @method('DELETE')

                    <button class="copro-btn copro-btn-danger">
                        <i class="fa-solid fa-trash"></i>
                        Réinitialiser
                    </button>
                </form>
            </div>
        </div>

        <div class="copro-stats">

            <div class="copro-stat-card">
                <div class="copro-stat-icon">
                    <i class="fa-solid fa-city"></i>
                </div>
                <div class="copro-stat-label">Copropriétés</div>
                <div class="copro-stat-value">{{ $coproprietes->total() }}</div>
            </div>

            <div class="copro-stat-card">
                <div class="copro-stat-icon">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="copro-stat-label">Lots Totaux</div>
                <div class="copro-stat-value">
                    {{ $coproprietes->sum('nombre_lots_total') }}
                </div>
            </div>

            <div class="copro-stat-card">
                <div class="copro-stat-icon">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <div class="copro-stat-label">Syndics liés</div>
                <div class="copro-stat-value">
                    {{ $coproprietes->pluck('syndics')->flatten()->count() }}
                </div>
            </div>

        </div>

        <div class="copro-card">

            <div class="copro-card-header">

                <div>
                    <h2 style="margin:0;font-size:1.2rem;font-weight:800;color:var(--copro-primary)">
                        Liste des copropriétés
                    </h2>
                </div>

                <div class="copro-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="coproSearch" placeholder="Rechercher une copropriété...">
                </div>

            </div>

            <div class="copro-table-wrapper">

                <table class="copro-table">

                    <thead>
                        <tr>
                            <th>Copropriété</th>
                            <th>Immatriculation</th>
                            <th>SIREN</th>
                            <th>Lots</th>
                            <th>Syndics</th>
                            <th style="width:90px;">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="coproTableBody">

                        @forelse($coproprietes as $copropriete)

                            <tr>

                                <td>
                                    <div class="copro-name">
                                        {{ $copropriete->nom_copropriete ?? 'Sans nom' }}
                                    </div>

                                    <div class="copro-sub">
                                        {{ $copropriete->adresse->adresse_complete ?? 'Adresse inconnue' }}
                                    </div>
                                </td>

                                <td>
                                    <span class="copro-code">
                                        {{ $copropriete->numero_immatriculation ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="copro-code">
                                        {{ $copropriete->siren_copropriete ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="copro-badge copro-badge-success">
                                        <i class="fa-solid fa-building"></i>
                                        {{ $copropriete->nombre_lots_total ?? 0 }} lots
                                    </span>
                                </td>

                                <td>
                                    @if($copropriete->syndics->count())
                                        <div style="display:flex;flex-direction:column;gap:8px;">
                                            @foreach($copropriete->syndics as $syndic)
                                                <span class="copro-badge copro-badge-warning">
                                                    <i class="fa-solid fa-user-tie"></i>
                                                    {{ $syndic->nom }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="copro-sub">Aucun syndic</span>
                                    @endif
                                </td>

                                <td>

                                    <div class="copro-dropdown">

                                        <button type="button" class="copro-dropdown-btn">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>

                                        <div class="copro-dropdown-menu">

                                            <a href="{{ route('back.coproprietes.show', $copropriete) }}">
                                                <i class="fa-solid fa-eye"></i>
                                                Voir
                                            </a>

                                            <a href="{{ route('back.coproprietes.edit', $copropriete) }}">
                                                <i class="fa-solid fa-pen"></i>
                                                Modifier
                                            </a>

                                            <form action="{{ route('back.coproprietes.destroy', $copropriete) }}"
                                                method="POST"
                                                onsubmit="return confirm('Supprimer cette copropriété ?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                    Supprimer
                                                </button>

                                            </form>

                                        </div>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6">

                                    <div class="copro-empty">
                                        <i class="fa-solid fa-city"></i>
                                        <h3>Aucune copropriété</h3>
                                        <p>Aucune donnée disponible actuellement.</p>
                                    </div>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="copro-pagination">
                {{ $coproprietes->links() }}
            </div>

        </div>

    </div>

    <script>

        // SEARCH
        document.getElementById('coproSearch').addEventListener('keyup', function () {

            let value = this.value.toLowerCase();
            let rows = document.querySelectorAll('#coproTableBody tr');

            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(value)
                    ? ''
                    : 'none';
            });

        });

        // DROPDOWN
        document.querySelectorAll('.copro-dropdown-btn').forEach(btn => {

            btn.addEventListener('click', function (e) {

                e.stopPropagation();

                document.querySelectorAll('.copro-dropdown')
                    .forEach(drop => {
                        if (drop !== this.parentElement) {
                            drop.classList.remove('active');
                        }
                    });

                this.parentElement.classList.toggle('active');

            });

        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.copro-dropdown')
                .forEach(drop => drop.classList.remove('active'));
        });

    </script>

@endsection