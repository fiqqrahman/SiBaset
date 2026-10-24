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
        <button id="school-profile-trigger" type="button" aria-expanded="false" aria-controls="school-profile-modal" aria-label="Buka profil akun" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-zinc-900 text-[11px] font-bold text-white transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2">
            {{ strtoupper(substr(auth()->user()->name ?? 'OP', 0, 2)) }}
        </button>
    </div>
</header>

<div id="school-profile-modal" hidden class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-zinc-950/40 p-4 backdrop-blur-[2px]">
    <section role="dialog" aria-modal="true" aria-labelledby="school-profile-modal-title" class="my-auto w-full max-w-md overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-zinc-100 px-5 py-4">
            <div>
                <h2 id="school-profile-modal-title" class="text-sm font-bold text-zinc-900">Profil akun</h2>
                <p class="mt-1 text-[11px] text-zinc-500">Informasi akun operator yang sedang masuk.</p>
            </div>
            <button id="school-profile-close" type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900" aria-label="Tutup profil">✕</button>
        </div>
        <div class="p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-zinc-900 text-sm font-bold text-white">{{ strtoupper(substr(auth()->user()->name ?? 'OP', 0, 2)) }}</div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-bold text-zinc-900">{{ auth()->user()->name ?? 'Operator Sekolah' }}</div>
                    <div class="mt-1 break-all text-xs text-zinc-500">{{ auth()->user()->email ?? 'Email belum tersedia' }}</div>
                </div>
            </div>
            <dl class="mt-5 divide-y divide-zinc-100 rounded-xl border border-zinc-200 px-3">
                <div class="flex items-start justify-between gap-4 py-3">
                    <dt class="text-xs text-zinc-500">Peran</dt>
                    <dd class="text-right text-xs font-semibold text-zinc-800">Operator Sekolah</dd>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <dt class="text-xs text-zinc-500">Status</dt>
                    <dd class="text-right text-xs font-semibold text-zinc-800">Sesi aktif</dd>
                </div>
            </dl>
            <div class="mt-5 flex justify-end gap-2">
                <button id="school-profile-dismiss" type="button" class="rounded-xl border border-zinc-200 px-4 py-2.5 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50">Tutup</button>
                <a href="{{ route('school.profile.edit') }}" class="rounded-xl bg-zinc-900 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-zinc-700">Profil sekolah</a>
            </div>
            <div class="mt-4 border-t border-zinc-100 pt-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-10 w-full items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-xs font-semibold text-zinc-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2">
                        Keluar dari akun
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
(() => {
    const notificationTrigger = document.getElementById('school-notification-trigger');
    const notificationPanel = document.getElementById('school-notification-panel');
    const profileTrigger = document.getElementById('school-profile-trigger');
    const profileModal = document.getElementById('school-profile-modal');
    const profileClose = document.getElementById('school-profile-close');
    const profileDismiss = document.getElementById('school-profile-dismiss');

    const closeNotifications = () => {
        if (!notificationTrigger || !notificationPanel) return;
        notificationPanel.hidden = true;
        notificationTrigger.setAttribute('aria-expanded', 'false');
    };

    const closeProfile = (restoreFocus = true) => {
        if (!profileTrigger || !profileModal) return;
        profileModal.hidden = true;
        profileTrigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) profileTrigger.focus();
    };

    notificationTrigger?.addEventListener('click', () => {
        if (!notificationPanel) return;
        notificationPanel.hidden = !notificationPanel.hidden;
        notificationTrigger.setAttribute('aria-expanded', String(!notificationPanel.hidden));
    });

    profileTrigger?.addEventListener('click', () => {
        if (!profileModal) return;
        const isOpening = profileModal.hidden;
        profileModal.hidden = !isOpening;
        profileTrigger.setAttribute('aria-expanded', String(isOpening));
        if (isOpening) profileClose?.focus();
    });

    profileClose?.addEventListener('click', () => closeProfile());
    profileDismiss?.addEventListener('click', () => closeProfile());

    profileModal?.addEventListener('click', event => {
        if (event.target === profileModal) closeProfile();
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('#school-notification-trigger') && !event.target.closest('#school-notification-panel')) closeNotifications();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeNotifications();
            if (profileModal && !profileModal.hidden) closeProfile();
        }
    });
})();
</script>
@endpush
