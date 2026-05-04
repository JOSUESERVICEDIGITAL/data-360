<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Data Rocket - Données des adresses françaises')</title>

    @include('front.partials.styles')
</head>

<body>

@include('front.partials.header')

<main>
    @yield('content')
</main>

@include('front.partials.footer')
@include('front.partials.scripts')

</body>
</html>