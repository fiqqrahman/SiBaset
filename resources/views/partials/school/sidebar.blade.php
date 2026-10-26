<aside id="school-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-[255px] -translate-x-full flex-col border-r border-zinc-200 bg-white transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-16 items-center gap-3 border-b border-zinc-100 px-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-950 text-white">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/><path d="M3 7.5 12 12l9-4.5M12 12v9"/></svg>
        </div>
        <div class="min-w-0"><div class="text-lg font-extrabold tracking-tight">SiBaset</div><div class="text-[10px] font-medium text-zinc-500">Operator Sekolah</div></div>
        <button id="school-sidebar-close" type="button" class="ml-auto flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 lg:hidden" aria-label="Tutup menu">✕</button>
    </div>

    <div class="border-b border-zinc-100 p-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-3">
            <div class="flex items-center gap-2 text-[9px] font-bold uppercase tracking-[0.14em] text-zinc-400"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-3.5 w-3.5" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>Konteks sekolah</div>
            <div class="mt-2 break-words text-xs font-bold text-zinc-800">{{ $school?->nama ?? 'Sekolah belum terhubung' }}</div>
            <div class="mt-1 text-[10px] text-zinc-500">NPSN {{ $school?->npsn ?? '—' }}</div>
        </div>
    </div>

    <nav class="school-scrollbar flex-1 overflow-y-auto px-3 py-5" aria-label="Navigasi operator sekolah">
        <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-400">Menu utama</div>
        <a href="{{ route('school.dashboard') }}" class="{{ request()->routeIs('school.dashboard') ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }} mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-xs font-semibold transition">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="4" rx="1.5"/><rect x="14" y="10" width="7" height="11" rx="1.5"/><rect x="3" y="13" width="7" height="8" rx="1.5"/></svg>Dashboard
        </a>
        <a href="{{ route('school.assets.index') }}" class="{{ request()->routeIs('school.assets.*') ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }} mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-xs font-semibold transition">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/></svg>Data Aset
        </a>
        <a href="{{ route('school.profile.edit') }}" class="{{ request()->routeIs('school.profile.*') ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }} mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-xs font-semibold transition">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>Profil Sekolah
        </a>
        <a href="{{ route('school.dashboard') }}#pengajuan-terbaru" class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-xs font-semibold text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/><path d="M16 3h5v5M21 3 11 13"/><path d="M7 8h4M7 12h2"/></svg>Riwayat Pengajuan
        </a>
        <div class="px-3 pb-2 pt-7 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-400">Bantuan</div>
        <a href="{{ route('school.dashboard') }}#bantuan" class="flex items-center gap-3 rounded-xl px-3 py-3 text-xs font-semibold text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.6 9a2.5 2.5 0 1 1 4.2 1.8c-1.2 1-1.8 1.4-1.8 3M12 17h.01"/></svg>Pusat Bantuan
        </a>
    </nav>
</aside>
<div id="school-sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-zinc-950/40 lg:hidden"></div>
