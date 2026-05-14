<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Data Rocket') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #0053b3;
            --primary-dark: #003d85;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --soft: #f8fafc;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(0, 83, 179, 0.16), transparent 30%),
                linear-gradient(135deg, #eef7ff 0%, #ffffff 55%, #f8fafc 100%);
        }

        .guest-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .guest-brand-panel {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .guest-brand-panel::before,
        .guest-brand-panel::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
        }

        .guest-brand-panel::before {
            top: -130px;
            right: -120px;
        }

        .guest-brand-panel::after {
            bottom: -160px;
            left: -140px;
        }

        .guest-logo-link {
            position: absolute;
            top: 34px;
            left: 46px;
            z-index: 2;
            display: inline-flex;
            text-decoration: none;
        }

        .guest-logo-link img,
        .guest-mobile-logo img {
            object-fit: contain;
            max-width: 190px;
        }

        .guest-logo-link img {
            height: 46px;
            background: white;
            padding: 8px 12px;
            border-radius: 14px;
        }

        .guest-hero {
            position: relative;
            z-index: 2;
            max-width: 560px;
        }

        .guest-hero h1 {
            font-size: 42px;
            line-height: 1.08;
            margin: 0 0 18px;
            font-weight: 900;
            letter-spacing: -0.8px;
        }

        .guest-hero p {
            color: #eaf2ff;
            line-height: 1.8;
            font-size: 16px;
            margin: 0;
        }

        .guest-features {
            display: grid;
            gap: 12px;
            margin-top: 28px;
        }

        .guest-feature {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 14px;
            padding: 13px 15px;
            color: #f8fbff;
            font-weight: 650;
        }

        .guest-form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px 20px;
        }

        .guest-card {
            width: 100%;
            max-width: 460px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 34px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.13);
        }

        .guest-mobile-logo {
            display: none;
            text-align: center;
            margin-bottom: 24px;
        }

        .guest-mobile-logo img {
            height: 52px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 22px;
        }

        .auth-header h2 {
            font-size: 27px;
            font-weight: 850;
            margin: 0 0 7px;
            color: var(--text);
        }

        .auth-header p {
            color: var(--muted);
            font-size: 14px;
            margin: 0;
        }

        .auth-alert {
            margin-bottom: 12px;
            font-size: 13px;
            color: #166534;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .auth-group {
            display: flex;
            flex-direction: column;
        }

        .auth-group label {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #334155;
        }

        .auth-group input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            outline: none;
            font-size: 14px;
            transition: 0.2s;
            background: white;
        }

        .auth-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 83, 179, 0.11);
        }

        .auth-error {
            font-size: 12px;
            color: #dc2626;
            margin-top: 5px;
        }

        .auth-remember {
            font-size: 13px;
            color: #475569;
        }

        .auth-remember label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .auth-remember input {
            width: 15px;
            height: 15px;
            accent-color: var(--primary);
        }

        .auth-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .auth-link {
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 650;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .auth-btn {
            background: var(--primary);
            color: white;
            padding: 11px 18px;
            border-radius: 12px;
            border: none;
            font-weight: 750;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 10px 24px rgba(0, 83, 179, 0.22);
        }

        .auth-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .auth-footer {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }

        .auth-footer p {
            margin: 0 0 4px;
        }

        .auth-footer a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .guest-page {
                grid-template-columns: 1fr;
            }

            .guest-brand-panel {
                display: none;
            }

            .guest-form-panel {
                min-height: 100vh;
                padding: 24px 16px;
            }

            .guest-mobile-logo {
                display: block;
            }

            .guest-card {
                padding: 26px;
                border-radius: 20px;
            }

            .auth-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .auth-btn {
                width: 100%;
            }

            .auth-link {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <main class="guest-page">
        <section class="guest-brand-panel">
            <a href="{{ url('/') }}" class="guest-logo-link">
                <img src="{{ asset('data1.png') }}" alt="{{ config('app.name', 'Data Rocket') }}">
            </a>

            <div class="guest-hero">
                <h1>Votre moteur intelligent d’adresses</h1>
                <p>
                    Analysez rapidement les adresses, bâtiments, copropriétés,
                    syndics, données cadastrales et informations d’entreprises.
                </p>

                <div class="guest-features">
                    <div class="guest-feature">. Recherche d’adresse enrichie</div>
                    <div class="guest-feature">. Copropriétés, syndics, SIREN/SIRET</div>
                    <div class="guest-feature">. Données utiles pour la prospection</div>
                </div>
            </div>
        </section>

        <section class="guest-form-panel">
            <div class="guest-card">
                <a href="{{ url('/') }}" class="guest-mobile-logo">
                    <img src="{{ asset('data1.png') }}" alt="{{ config('app.name', 'data 360') }}">
                </a>

                {{ $slot }}
            </div>
        </section>
    </main>
</body>
</html>
