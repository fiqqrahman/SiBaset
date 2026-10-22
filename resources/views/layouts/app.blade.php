<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'SiBaset') — Sistem Informasi Aset</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        :root {
            font-family: 'Inter', sans-serif;
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        button,
        input,
        select {
            font: inherit;
        }

        #map {
            width: 100%;
            height: 100%;
            min-height: 400px;
            background: #e8e9e7;
            z-index: 1;
        }

        .leaflet-container {
            font-family: 'Inter', sans-serif;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 12px 35px rgb(0 0 0 / 10%);
        }

        .leaflet-popup-content {
            margin: 14px;
        }

        .glass-panel {
            background: rgb(255 255 255 / 94%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgb(255 255 255 / 80%);
            box-shadow: 0 8px 32px rgb(0 0 0 / 7%);
        }

        .map-control {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
            border-radius: 10px;
            transition: background .15s ease;
        }

        .map-control:hover {
            background: #f3f4f6;
        }

        .kib-tab[aria-selected="true"] {
            color: #111827;
            background: white;
            box-shadow: 0 1px 3px rgb(0 0 0 / 8%);
            border: 1px solid #e5e7eb;
        }

        .kib-tab[aria-selected="false"] {
            color: #6b7280;
            border: 1px solid transparent;
        }

        .kib-tab[aria-selected="false"]:hover {
            background: #f3f4f6;
        }

        .modal-open {
            overflow: hidden;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 640px) {
            #map {
                min-height: 350px;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="bg-white text-zinc-900 antialiased">

    @yield('content')

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @stack('scripts')
</body>

</html>
