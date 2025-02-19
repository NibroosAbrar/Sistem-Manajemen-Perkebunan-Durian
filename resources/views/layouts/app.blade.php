<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DuriGeo')</title>

    <!-- Tailwind -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ url ('assets/css/style.css') }}">

    <!-- AlpineJS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="{{ url ('static/L.Control.Sidebar.css') }}">
    <link rel="stylesheet" href="{{ url('static/L.Control.MousePosition.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.css">
    <link rel="stylesheet" href="https://unpkg.com/{{ '@' }}geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.css"/>

    @stack('styles')
</head>
<body class="bg-gray-100 font-family-karla flex" x-data="{ isDropdownOpen: false, isAccountDropdownOpen: false, showModal: false }" onload="hideLoadingScreen()">


    @yield('content')

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ url('static/L.Control.Sidebar.js') }}"></script>
    <script src="{{ url('static/L.Control.MousePosition.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js"></script>


    <script src="https://unpkg.com/{{ '@' }}geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.js"></script>

    <script src="{{ url('static/leaflet.ajax.js') }}"></script>

    <!-- Custom JavaScript -->
    <script src="{{ url('assets/js/scripts.js') }}"></script>

    @stack('scripts')
</body>
</html>
