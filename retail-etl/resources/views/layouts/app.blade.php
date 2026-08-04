<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema ETL - Retail')</title>
    @vite(['resources/css/app.css', 'resources/js/dashboard.js'])
</head>

<body class="bg-gray-100">
    @yield('content')
</body>

</html>