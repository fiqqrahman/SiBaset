<div id="notifications-modal"
    class="fixed inset-0 z-[2200] hidden items-center justify-center bg-zinc-950/40 p-3 backdrop-blur-[3px] sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="notifications-modal-title" aria-hidden="true">
    <section class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl">
        <header class="flex items-start justify-between gap-4 border-b border-zinc-200 px-4 py-4 sm:px-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Aktivitas seluruh sekolah</span>
                <h2 id="notifications-modal-title" class="mt-1 text-lg font-bold tracking-tight text-zinc-900">Notifikasi</h2>
                <p class="mt-1 text-xs leading-5 text-zinc-500">Pengajuan aset, dokumen, perubahan aset, dan koordinat dari operator sekolah.</p>
            </div>
            <button type="button" data-close-modal
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-xl text-zinc-500 hover:bg-zinc-100"
                aria-label="Tutup notifikasi">&times;</button>
        </header>
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 bg-zinc-50/70 px-4 py-3 sm:px-6">
            <div>
                <p class="text-xs font-semibold text-zinc-800">Aktivitas terbaru</p>
                <p id="notifications-feed-meta" class="mt-1 text-[10px] text-zinc-500">Memuat pembaruan dari server…</p>
            </div>
            <button type="button" id="notifications-refresh" class="min-h-9 rounded-lg border border-zinc-200 bg-white px-3 text-[10px] font-semibold text-zinc-700 hover:bg-zinc-100">Muat ulang</button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div id="notifications-list" class="space-y-3" aria-live="polite" aria-busy="true">
                <div class="rounded-xl border border-zinc-200 px-4 py-8 text-center text-xs text-zinc-500">Memuat aktivitas terbaru…</div>
            </div>
            <p class="mt-4 rounded-xl bg-zinc-50 p-3 text-[10px] leading-5 text-zinc-500">Counter lonceng menunjukkan jumlah pekerjaan yang masih perlu ditindaklanjuti, bukan status sudah dibaca. Feed menampilkan aktivitas 14 hari terakhir.</p>
        </div>
        <footer class="flex justify-end border-t border-zinc-200 bg-zinc-50/80 px-4 py-3 sm:px-6">
            <button type="button" data-close-modal class="min-h-9 rounded-xl border border-zinc-200 bg-white px-4 text-xs font-semibold text-zinc-700 hover:bg-zinc-100">Tutup</button>
        </footer>
    </section>
</div>