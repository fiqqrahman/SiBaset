<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
    <button id="school-sidebar-open" type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-zinc-700 hover:bg-zinc-50 lg:hidden" aria-label="Buka menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="min-w-0">
        <div class="text-xs font-extrabold tracking-tight text-zinc-900">SiBaset <span class="font-medium text-zinc-400">/ Portal Operator Sekolah</span></div>
        <div class="mt-1 truncate text-[10px] text-zinc-500">Beranda <span class="mx-1 text-zinc-300">/</span> @yield('page-heading', 'Dashboard')</div>
    </div>
    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <div class="hidden rounded-xl border border-zinc-200 bg-white px-3 py-2 text-right md:block">
            <div class="text-[10px] font-semibold text-zinc-700">{{ now()->translatedFormat('D, d M Y') }}</div>
            <div id="school-header-clock" class="mt-0.5 text-[10px] tabular-nums text-zinc-400">{{ now()->format('H:i') }}</div>
        </div>
        <div class="relative">
            <button id="school-notification-trigger" type="button" aria-expanded="false" aria-controls="school-notification-panel" class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:bg-zinc-50" aria-label="Buka notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                <span class="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-zinc-700 ring-2 ring-white"></span>
            </button>
            <div id="school-notification-panel" hidden class="absolute right-0 top-12 z-50 w-[min(300px,calc(100vw-32px))] rounded-2xl border border-zinc-200 bg-white p-4 shadow-xl">
                <div class="flex items-center justify-between"><div class="text-xs font-bold">Notifikasi</div><span class="rounded-md bg-zinc-100 px-2 py-1 text-[9px] text-zinc-500">Pratinjau</span></div>
                <div class="mt-3 rounded-xl border border-dashed border-zinc-200 px-3 py-5 text-center">
                    <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg></div>
                    <p class="mt-2 text-[11px] font-semibold text-zinc-700">Belum ada notifikasi terhubung</p>
                    <p class="mt-1 text-[10px] leading-5 text-zinc-400">Notifikasi pengajuan akan muncul setelah backend tersedia.</p>
                </div>
            </div>
        </div>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-900 text-[11px] font-bold text-white" title="{{ auth()->user()->name ?? 'Operator' }}">{{ strtoupper(substr(auth()->user()->name ?? 'OP', 0, 2)) }}</div>
    </div>
</header>

@push('scripts')
<script>
(() => {
    const trigger = document.getElementById('school-notification-trigger');
    const panel = document.getElementById('school-notification-panel');
    if (!trigger || !panel) return;
    const closePanel = () => {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };
    trigger.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        trigger.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('#school-notification-trigger') && !event.target.closest('#school-notification-panel')) closePanel();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closePanel();
    });
})();
</script>
@endpush
