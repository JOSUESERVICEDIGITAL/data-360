<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Back Office - Data 360')</title>

    {{-- =========================================
        BOOTSTRAP 5
    ========================================== --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- =========================================
        FONT AWESOME
    ========================================== --}}
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />

    {{-- =========================================
        GOOGLE FONTS
    ========================================== --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    {{-- =========================================
        STYLE GLOBAL
    ========================================== --}}
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
        }

        .back-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .back-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .back-content {
            padding: 24px;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .alert-error ul {
            margin-top: 10px;
            margin-bottom: 0;
        }
    </style>

    @include('back.partials.styles')

    @stack('styles')
</head>

<body>

    <div class="back-wrapper">

        @include('back.partials.sidebar')

        <main class="back-main">

            @include('back.partials.header')

            <section class="back-content">

                @if (session('success'))
                    <div class="alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-error">
                        <strong>Erreur :</strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')

            </section>

        </main>

    </div>

    {{-- =========================================
        BOOTSTRAP JS
    ========================================== --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    {{-- =========================================
        JQUERY
    ========================================== --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    @include('back.partials.scripts')

    @stack('scripts')

</body>

</html>