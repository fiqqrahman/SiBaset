<div id="notifications-modal"
    class="fixed inset-0 z-[2200] hidden items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-[3px]"
    role="dialog" aria-modal="true" aria-labelledby="notifications-modal-title" aria-hidden="true">
    <section class="flex max-h-[82vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl">
        <header class="flex items-start justify-between gap-4 border-b border-zinc-200 px-5 py-4 sm:px-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Pusat Informasi</span>
                <h2 id="notifications-modal-title" class="mt-1 text-lg font-bold tracking-tight">Notifikasi</h2>
                <p class="mt-1 text-xs text-zinc-500">Ringkasan aktivitas dan informasi terkait SiBaset.</p>
            </div>
            <button type="button" data-close-modal
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-xl text-zinc-500 hover:bg-zinc-100"
                aria-label="Tutup notifikasi">&times;</button>
        </header>

        <div class="overflow-y-auto p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <p class="text-xs font-semibold text-zinc-800">Aktivitas terbaru</p>
                <span class="rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[10px] font-medium text-zinc-500">Pratinjau UI</span>
            </div>

            <div class="space-y-3">
                <article class="flex gap-3 rounded-xl border border-zinc-200 bg-white p-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true">
                            <path d="M12 3 20 7v5c0 4.5-3 7-8 9-5-2-8-4.5-8-9V7l8-4Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-semibold text-zinc-800">Verifikasi data sekolah</p>
                            <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[9px] font-semibold text-zinc-600">Contoh</span>
                        </div>
                        <p class="mt-1 text-[11px] leading-5 text-zinc-500">Pengajuan pembaruan data menunggu pemeriksaan administrator.</p>
                        <p class="mt-2 text-[10px] text-zinc-400">2 jam lalu</p>
                    </div>
                </article>

                <article class="flex gap-3 rounded-xl border border-zinc-200 bg-white p-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true">
                            <path d="M4 5h16v14H4z" />
                            <path d="M8 9h8M8 13h5" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-semibold text-zinc-800">Pembaruan informasi sekolah</p>
                            <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[9px] font-semibold text-zinc-600">Contoh</span>
                        </div>
                        <p class="mt-1 text-[11px] leading-5 text-zinc-500">Perubahan profil sekolah dapat ditinjau dari daftar pengajuan.</p>
                        <p class="mt-2 text-[10px] text-zinc-400">Kemarin</p>
                    </div>
                </article>

                <article class="flex gap-3 rounded-xl border border-zinc-200 bg-white p-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true">
                            <path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" />
                            <circle cx="12" cy="9" r="2.3" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-semibold text-zinc-800">Status lokasi sekolah</p>
                            <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[9px] font-semibold text-zinc-600">Contoh</span>
                        </div>
                        <p class="mt-1 text-[11px] leading-5 text-zinc-500">Koordinat sekolah yang telah diverifikasi akan ditampilkan pada peta.</p>
                        <p class="mt-2 text-[10px] text-zinc-400">2 hari lalu</p>
                    </div>
                </article>
            </div>

            <p class="mt-4 rounded-xl bg-zinc-50 p-3 text-[10px] leading-5 text-zinc-500">
                Informasi di atas hanya contoh tampilan. Notifikasi belum terhubung ke data atau peristiwa dari server.
            </p>
        </div>

        <footer class="flex justify-end border-t border-zinc-200 bg-zinc-50/80 px-5 py-3 sm:px-6">
            <button type="button" data-close-modal class="h-9 rounded-xl border border-zinc-200 bg-white px-4 text-xs font-semibold text-zinc-700 hover:bg-zinc-100">Tutup</button>
        </footer>
    </section>
</div>
