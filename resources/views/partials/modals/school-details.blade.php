<div id="school-details-modal"
    class="fixed inset-0 z-[2100] hidden items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-[3px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="school-details-modal-title"
    aria-hidden="true">

    <section class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl">
        <header class="flex items-start justify-between border-b border-zinc-200 p-5">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Data Sekolah</span>
                <h2 id="school-details-modal-title" class="mt-2 text-lg font-bold text-zinc-900">Profil Sekolah</h2>
                <p class="mt-1 text-xs text-zinc-500">Ringkasan sekolah, statistik inventaris, dan status pengajuan.</p>
            </div>

            <button type="button" data-close-modal
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-xl text-zinc-500 transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2"
                aria-label="Tutup profil sekolah">
                &times;
            </button>
        </header>

        <div id="school-details-content" class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
            <p class="text-xs text-zinc-500">Pilih sekolah dari peta untuk melihat detailnya.</p>
        </div>

        <footer class="flex justify-end border-t border-zinc-200 p-4">
            <button type="button" data-close-modal
                class="min-h-10 rounded-xl border border-zinc-200 px-5 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2">
                Tutup
            </button>
        </footer>
    </section>
</div>
