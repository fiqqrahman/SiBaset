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
                        <span id="mapped-count">{{ $schoolStats['verified'] ?? 0 }}</span> lokasi terverifikasi
                    </span>

                    <span class="rounded-lg border border-zinc-200 bg-white/80 px-2.5 py-1.5 text-[10px] font-semibold text-zinc-500">
                        <span id="unmapped-count">{{ max(0, ($schoolStats['total'] ?? 0) - ($schoolStats['verified'] ?? 0)) }}</span> belum terverifikasi
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
                    <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-1 text-[9px] font-semibold {{ ($boundaryAvailable ?? false) ? 'text-emerald-700' : 'text-amber-800' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ ($boundaryAvailable ?? false) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        {{ ($boundaryAvailable ?? false) ? 'Batas wilayah aktif' : 'Batas belum tersedia' }}
                    </span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Total sekolah</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">{{ $schoolStats['total'] ?? 0 }}</div>
                        <div class="mt-1 text-[9px] text-zinc-400">Terdaftar</div>
                    </div>

                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Terverifikasi</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">{{ $schoolStats['verified'] ?? 0 }}</div>
                        <div class="mt-1 text-[9px] text-zinc-400">Dari {{ $schoolStats['total'] ?? 0 }} sekolah</div>
                    </div>

                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Perlu verifikasi</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">{{ $schoolStats['needs_review'] ?? 0 }}</div>
                        <div class="mt-1 text-[9px] text-zinc-400">Usulan koordinat</div>
                    </div>

                    <div class="rounded-xl border border-zinc-200/80 bg-white/80 p-3">
                        <div class="text-[10px] leading-4 text-zinc-500">Belum dipetakan</div>
                        <div class="mt-1 text-xl font-bold tracking-tight text-zinc-900">{{ $schoolStats['missing'] ?? 0 }}</div>
                        <div class="mt-1 text-[9px] text-zinc-400">Koordinat belum tersedia</div>
                    </div>
                </div>

                <div class="mt-3 border-t border-zinc-200/80 pt-3">
                    <div class="flex items-center justify-between gap-3 text-[10px]">
                        <span class="font-medium text-zinc-600">Kelengkapan koordinat</span>
                        <span class="font-bold tabular-nums text-zinc-800">{{ ($schoolStats['total'] ?? 0) > 0 ? round((($schoolStats['verified'] ?? 0) / $schoolStats['total']) * 100) : 0 }}%</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200">
                        <div class="h-full rounded-full bg-zinc-800" style="width: {{ ($schoolStats['total'] ?? 0) > 0 ? round((($schoolStats['verified'] ?? 0) / $schoolStats['total']) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </section>
        </div>

        <div class="pointer-events-auto flex shrink-0 flex-col gap-2">
            <a href="{{ route('admin.schools.import.index') }}" class="map-control" aria-label="Impor data sekolah JSON" title="Impor JSON">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><path d="M12 15V3m0 0-4 4m4-4 4 4"/><path d="M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"/></svg>
            </a>
            <a href="{{ route('admin.schools.coordinates.index') }}" class="map-control" aria-label="Verifikasi koordinat" title="Verifikasi koordinat">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            </a>
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
            <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-600">Jenjang Sekolah</div>

            <div class="mt-2 space-y-2">
                <div class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-700 text-white"><svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8.5 12 4l8 4.5-8 4.5-8-4.5Z"/><path d="M7 10v5M17 10v5"/></svg></span><span class="text-[10px] text-zinc-600">TK · Taman Kanak-kanak</span></div>
                <div class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-md bg-blue-600 text-white"><svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5.5c3-.8 5.7 0 8 2v12c-2.3-2-5-2.8-8-2v-12ZM20 5.5c-3-.8-5.7 0-8 2v12c2.3-2 5-2.8 8-2v-12Z"/></svg></span><span class="text-[10px] text-zinc-600">SD · Sekolah Dasar</span></div>
                <div class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-lg bg-violet-700 text-white"><svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m2.5 9 9.5-5 9.5 5-9.5 5-9.5-5Z"/><path d="M6.5 11v4c3.5 2.3 7.5 2.3 11 0v-4"/></svg></span><span class="text-[10px] text-zinc-600">SMP · Sekolah Menengah Pertama</span></div>
                <div class="flex items-center gap-2 border-t border-zinc-200 pt-2"><span class="h-2 w-2 rounded-full bg-zinc-900"></span><span class="text-[10px] text-zinc-600">Marker hanya untuk koordinat terverifikasi</span></div>
            </div>
        </section>

        <div class="glass-panel pointer-events-auto rounded-xl px-3 py-2">
            <div class="text-[9px] font-semibold text-zinc-500">KOORDINAT KURSOR</div>
            <div id="cursor-coordinates" class="mt-1 font-mono text-[10px] text-zinc-800">—, —</div>
        </div>
    </div>
</div>
