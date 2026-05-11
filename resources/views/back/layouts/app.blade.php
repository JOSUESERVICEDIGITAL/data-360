<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Back Office - Data 360')</title>

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
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
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

@include('back.partials.scripts')
@stack('scripts')

</body>
</html>