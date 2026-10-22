<div id="users-modal"
    class="fixed inset-0 z-[2200] hidden items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-[3px]"
    role="dialog" aria-modal="true" aria-labelledby="users-modal-title" aria-hidden="true">
    <section class="flex max-h-[82vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl">
        <header class="flex items-start justify-between gap-4 border-b border-zinc-200 px-5 py-4 sm:px-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Tools Administrator</span>
                <h2 id="users-modal-title" class="mt-1 text-lg font-bold tracking-tight">Manajemen Pengguna</h2>
                <p class="mt-1 text-xs text-zinc-500">Kontrol akun pengguna yang terdaftar pada SiBaset.</p>
            </div>
            <button type="button" data-close-modal class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-xl text-zinc-500 hover:bg-zinc-100" aria-label="Tutup manajemen pengguna">&times;</button>
        </header>
        <div class="overflow-y-auto p-5 sm:p-6">
            <label for="users-search" class="mb-1.5 block text-[10px] font-semibold text-zinc-600">Cari pengguna</label>
            <input id="users-search" type="search" autocomplete="off" placeholder="Nama atau email pengguna..."
                class="h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs outline-none focus:border-zinc-400 focus:ring-2 focus:ring-zinc-100">
            <div class="mt-4 flex items-center justify-between gap-3">
                <p class="text-xs font-semibold text-zinc-800">Daftar akun</p>
                <span id="users-count" class="rounded-lg border border-zinc-200 px-2.5 py-1 text-[10px] text-zinc-500">0 pengguna</span>
            </div>
            <div id="users-list" class="mt-3 space-y-2" aria-live="polite">
                <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-8 text-center">
                    <p class="text-xs font-semibold text-zinc-700">Data pengguna belum terhubung</p>
                    <p class="mt-1 text-[11px] leading-5 text-zinc-500">Modal ini memerlukan endpoint Laravel yang dilindungi autentikasi dan otorisasi administrator.</p>
                </div>
            </div>
            <p class="mt-4 rounded-xl bg-zinc-50 p-3 text-[10px] leading-5 text-zinc-500">
                Informasi sensitif seperti kata sandi atau token tidak ditampilkan. Operasi pengelolaan akun harus divalidasi dan diotorisasi oleh server.
            </p>
        </div>
        <footer class="flex justify-end border-t border-zinc-200 bg-zinc-50/80 px-5 py-3 sm:px-6">
            <button type="button" data-close-modal class="h-9 rounded-xl border border-zinc-200 bg-white px-4 text-xs font-semibold text-zinc-700 hover:bg-zinc-100">Tutup</button>
        </footer>
    </section>
</div>
