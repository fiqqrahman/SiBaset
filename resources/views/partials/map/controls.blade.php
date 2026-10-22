<div class="pointer-events-none absolute inset-0 z-[400]
            flex flex-col justify-between p-3 sm:p-5">

    <div class="flex items-start justify-between gap-3">

        <section class="glass-panel pointer-events-auto
                        max-w-[calc(100%-65px)] rounded-2xl p-4">

            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-zinc-900"></span>

                <span
                    class="text-[10px] font-bold uppercase
                             tracking-[0.16em] text-zinc-500">
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

                <span
                    class="rounded-lg border border-zinc-200
                             bg-white/80 px-2.5 py-1.5
                             text-[10px] font-semibold text-zinc-700">
                    <span id="mapped-count">0</span> lokasi terverifikasi
                </span>

                <span
                    class="rounded-lg border border-zinc-200
                             bg-white/80 px-2.5 py-1.5
                             text-[10px] font-semibold text-zinc-500">
                    <span id="unmapped-count">0</span> belum dipetakan
                </span>
            </div>
        </section>

        <div class="pointer-events-auto flex flex-col gap-2">

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

            <button type="button" id="reset-map" class="map-control" aria-label="Kembalikan cakupan peta"
                title="Cakupan awal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path d="M3 11a9 9 0 1 1 2.6 6.4" />
                    <path d="M3 4v7h7" />
                </svg>
            </button>

            <button type="button" id="toggle-layers" class="map-control" aria-label="Pengaturan layer peta"
                aria-expanded="false" aria-controls="layer-panel" title="Layer peta">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                    <path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
                </svg>
            </button>

            <div id="layer-panel" hidden class="glass-panel w-48 rounded-xl p-3">

                <div class="text-xs font-bold">Tampilan Peta</div>

                <label
                    class="mt-3 flex cursor-pointer items-center
                              gap-2 text-[11px] text-zinc-600">
                    <input type="radio" name="map-layer" value="street" checked>
                    OpenStreetMap
                </label>

                <label
                    class="mt-2 flex cursor-pointer items-center
                              gap-2 text-[11px] text-zinc-600">
                    <input type="radio" name="map-layer" value="gray">
                    Monokrom
                </label>

                <p class="mt-3 text-[9px] leading-4 text-zinc-400">
                    Peta dasar dapat diubah sesuai layanan tile yang
                    digunakan pada lingkungan produksi.
                </p>
            </div>
        </div>
    </div>

    <div class="flex items-end justify-between gap-3">

        <section
            class="glass-panel pointer-events-auto
                        max-w-[260px] rounded-xl p-3 sm:max-w-xs">

            <div class="text-[10px] font-bold uppercase
                        tracking-wider text-zinc-600">
                Status Koordinat
            </div>

            <div class="mt-2 space-y-2">

                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-zinc-900"></span>
                    <span class="text-[10px] text-zinc-600">
                        Terverifikasi
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full
                                 border-2 border-zinc-400 bg-white"></span>
                    <span class="text-[10px] text-zinc-600">
                        Perlu verifikasi
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-zinc-300"></span>
                    <span class="text-[10px] text-zinc-600">
                        Belum dipetakan
                    </span>
                </div>
            </div>
        </section>

        <div class="glass-panel pointer-events-auto rounded-xl px-3 py-2">
            <div class="text-[9px] font-semibold text-zinc-500">
                KOORDINAT KURSOR
            </div>

            <div id="cursor-coordinates" class="mt-1 font-mono text-[10px] text-zinc-800">
                —, —
            </div>
        </div>
    </div>
</div>
