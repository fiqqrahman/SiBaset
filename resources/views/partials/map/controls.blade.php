<div class="pointer-events-none absolute inset-0 z-[400]
            flex flex-col justify-between p-3 sm:p-5">

    <div class="flex items-start justify-between gap-3">

        <div class="pointer-events-none flex w-[min(340px,calc(100vw-68px))] flex-col gap-3">
            <section class="glass-panel pointer-events-auto rounded-2xl p-4">

                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-500">
                        Peta Interaktif
                    </span>
                </div>

                <h1 class="mt-2 text-lg font-bold tracking-tight sm:text-xl">
                    Persebaran Sekolah
                </h1>

                <p class="mt-1 text-[11px] leading-5 text-zinc-500">
                    Kota Palangka Raya, Kalimantan Tengah
                </p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-lg border border-zinc-200 bg-white/80 px-2.5 py-1.5 text-[10px] font-semibold text-zinc-700">
                        <span id="mapped-count">0</span> lokasi terverifikasi
                    </span>

                    <span class="rounded-lg border border-zinc-200 bg-white/80 px-2.5 py-1.5 text-[10px] font-semibold text-zinc-500">
                        <span id="unmapped-count">0</span> belum dipetakan
                    </span>
                </div>
            </section>

            <section class="glass-panel pointer-events-auto rounded-2xl p-3 sm:p-4" aria-labelledby="map-school-stats-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                            Ringkasan Wilayah
                        </div>
                        <h2 id="map-school-stats-title" class="mt-1 text-sm font-bold tracking-tight text-zinc-900">
                            Statistik Sekolah
                        </h2>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-1 text-[9px] font-semibold text-zinc-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                        Data simulasi
                    </span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Total sekolah</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">128</div>
                        <div class="mt-1 text-[9px] text-zinc-400">Terdaftar</div>
                    </div>

                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Terverifikasi</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">86</div>
                        <div class="mt-1 text-[9px] text-zinc-400">67% dari total</div>
                    </div>

                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Perlu verifikasi</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">18</div>
                        <div class="mt-1 text-[9px] text-zinc-400">Menunggu tinjauan</div>
                    </div>

                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Belum dipetakan</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">24</div>
                        <div class="mt-1 text-[9px] text-zinc-400">Tanpa lokasi valid</div>
                    </div>
                </div>

                <div class="mt-3 border-t border-zinc-200/80 pt-3">
                    <div class="flex items-center justify-between gap-3 text-[10px]">
                        <span class="font-medium text-zinc-600">Kelengkapan koordinat</span>
                        <span class="font-bold tabular-nums text-zinc-800">67%</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200">
                        <div class="h-full w-[67%] rounded-full bg-zinc-800"></div>
                    </div>
                </div>
            </section>
        </div>

        <div class="pointer-events-auto flex shrink-0 flex-col gap-2">
            <button type="button" id="open-users-modal" class="map-control" aria-label="Manajemen pengguna" title="Manajemen pengguna" aria-haspopup="dialog" aria-controls="users-modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M20 8v6M23 11h-6" />
                </svg>
            </button>

            <button type="button" id="open-policies-modal" class="map-control" aria-label="Kebijakan dan aspek hukum" title="Kebijakan dan aspek hukum" aria-haspopup="dialog" aria-controls="policies-modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true">
                    <path d="M12 3 20 6v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6l8-3Z" />
                    <path d="m9 12 2 2 4-4" />
                </svg>
            </button>

            <button type="button" id="zoom-in" class="map-control" aria-label="Perbesar peta" title="Perbesar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </button>

            <button type="button" id="zoom-out" class="map-control" aria-label="Perkecil peta" title="Perkecil">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path d="M5 12h14" />
                </svg>
            </button>

            <button type="button" id="reset-map" class="map-control" aria-label="Kembalikan cakupan peta" title="Cakupan awal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path d="M3 11a9 9 0 1 1 2.6 6.4" />
                    <path d="M3 4v7h7" />
                </svg>
            </button>
        </div>
    </div>

    <div class="flex items-end justify-between gap-3">
        <section class="glass-panel pointer-events-auto max-w-[260px] rounded-xl p-3 sm:max-w-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-600">
                Status Koordinat
            </div>

            <div class="mt-2 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-zinc-900"></span>
                    <span class="text-[10px] text-zinc-600">Terverifikasi</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full border-2 border-zinc-400 bg-white"></span>
                    <span class="text-[10px] text-zinc-600">Perlu verifikasi</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-zinc-300"></span>
                    <span class="text-[10px] text-zinc-600">Belum dipetakan</span>
                </div>
            </div>
        </section>

        <div class="glass-panel pointer-events-auto rounded-xl px-3 py-2">
            <div class="text-[9px] font-semibold text-zinc-500">KOORDINAT KURSOR</div>
            <div id="cursor-coordinates" class="mt-1 font-mono text-[10px] text-zinc-800">—, —</div>
        </div>
    </div>
</div>
