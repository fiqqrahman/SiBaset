<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Operator Sekolah') — SiBaset</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { font-family: 'Inter', sans-serif; color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; }
        button, input, select, textarea { font: inherit; }
        [hidden] { display: none !important; }
        .school-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .school-scrollbar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 99px; }
    </style>
    @stack('styles')
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased">
    <div class="min-h-screen">
        @include('partials.school.sidebar')
        <div class="min-h-screen lg:pl-64">
            @include('partials.school.header')
            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>
            <footer class="px-4 pb-6 text-center text-[11px] text-zinc-400 sm:px-6 lg:px-8">
                SiBaset · Sistem Informasi Pemetaan Barang dan Aset
            </footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
