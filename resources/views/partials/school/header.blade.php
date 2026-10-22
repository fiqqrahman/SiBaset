<header class="sticky top-0 z-20 flex h-[76px] items-center gap-3 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
    <button id="school-sidebar-open" type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-zinc-700 hover:bg-zinc-50 lg:hidden" aria-label="Buka menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="min-w-0"><div class="text-[10px] font-semibold text-zinc-400">PORTAL OPERATOR SEKOLAH</div><div class="mt-1 truncate text-sm font-bold tracking-tight text-zinc-900">@yield('page-heading', 'Dashboard')</div></div>
    <div class="ml-auto flex items-center gap-3">
        <div class="hidden text-right sm:block"><div class="text-[10px] font-semibold text-zinc-700">{{ now()->translatedFormat('l, d F Y') }}</div><div class="mt-1 text-[10px] text-zinc-400">SiBaset · Kota Palangka Raya</div></div>
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 text-xs font-bold text-white" title="{{ auth()->user()->name ?? 'Operator' }}">{{ strtoupper(substr(auth()->user()->name ?? 'OP', 0, 2)) }}</div>
    </div>
</header>
