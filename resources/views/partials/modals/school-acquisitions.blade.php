<div id="school-acquisitions-modal"
    class="fixed inset-0 z-[2200] hidden items-center justify-center bg-zinc-950/45 p-2 backdrop-blur-[3px] sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="school-acquisitions-modal-title" aria-hidden="true">
    <section class="flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl">
        <header class="flex items-start justify-between gap-4 border-b border-zinc-200 px-4 py-4 sm:px-6">
            <div class="min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Verifikasi inventaris sekolah</span>
                <h2 id="school-acquisitions-modal-title" class="mt-2 text-lg font-bold tracking-tight text-zinc-900 sm:text-xl">Pengajuan inventaris</h2>
                <p id="school-acquisitions-school-name" class="mt-1 text-xs text-zinc-500">Pilih sekolah dari peta.</p>
            </div>
            <button type="button" data-close-modal class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-xl text-zinc-500 hover:bg-zinc-100" aria-label="Tutup modal pengajuan inventaris">&times;</button>
        </header>
        <div id="school-acquisitions-status" hidden role="status" class="mx-4 mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900 sm:mx-6"></div>
        <div id="school-acquisitions-content" class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6">
            <div class="rounded-xl border border-zinc-200 px-5 py-10 text-center text-xs text-zinc-500">Pilih sekolah untuk memuat pengajuan inventaris.</div>
        </div>
        <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-4 py-3 sm:px-6">
            <p class="text-[10px] text-zinc-500">Keputusan tetap dicatat melalui alur audit admin. Dokumen hanya diakses pengguna berwenang.</p>
            <div class="flex items-center gap-2">
                <button type="button" id="school-acquisitions-open-kib" class="min-h-9 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100">Lihat data KIB</button>
                <button type="button" data-close-modal class="min-h-9 rounded-xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800">Tutup</button>
            </div>
        </footer>
    </section>
</div>