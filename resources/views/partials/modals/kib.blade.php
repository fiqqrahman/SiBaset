<div id="kib-modal"
    class="fixed inset-0 z-[2000] hidden items-center justify-center
            bg-zinc-950/40 p-2 backdrop-blur-[3px] sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="kib-modal-title" aria-hidden="true">

    <section
        class="flex max-h-[94vh] w-full max-w-6xl flex-col
                    overflow-hidden rounded-2xl border border-white/70
                    bg-white shadow-2xl">

        <header
            class="flex items-start justify-between gap-4
                       border-b border-zinc-200 px-4 py-4 sm:px-6">

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-md bg-zinc-100 px-2 py-1
                                 text-[9px] font-bold uppercase
                                 tracking-wider text-zinc-600">
                        Inventaris Aset
                    </span>

                    <span id="kib-school-status" class="text-[10px] text-zinc-500">
                        Status lokasi tidak diketahui
                    </span>
                </div>

                <h2 id="kib-modal-title" class="mt-2 text-lg font-bold tracking-tight sm:text-xl">
                    Data KIB
                </h2>

                <p id="kib-school-name" class="mt-1 text-xs text-zinc-500">
                    Pilih sekolah dari peta
                </p>
            </div>

            <button type="button" data-close-modal
                class="flex h-9 w-9 shrink-0 items-center
                           justify-center rounded-xl border
                           border-zinc-200 text-xl text-zinc-500
                           hover:bg-zinc-100"
                aria-label="Tutup modal inventaris">
                &times;
            </button>
        </header>

        <div id="kib-modal-body" class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div id="kib-primary-tabs" class="grid grid-cols-2 gap-2 rounded-xl bg-zinc-100 p-1" role="tablist" aria-label="Bagian inventaris sekolah">
                <button type="button" id="kib-inventory-tab" class="kib-view-tab rounded-lg border border-transparent px-3 py-3 text-left text-xs transition" data-kib-view="inventory" role="tab" aria-selected="true" aria-controls="kib-inventory-panel">
                    <span class="block font-bold text-zinc-900">Inventaris KIB</span>
                    <span class="mt-1 block text-[10px] text-zinc-500">Data barang sekolah</span>
                </button>
                @if (auth()->user()?->role === 'admin')
                    <button type="button" class="kib-view-tab rounded-lg border border-transparent px-3 py-3 text-left text-xs transition" data-kib-view="management" role="tab" aria-selected="false" aria-controls="kib-management-panel">
                        <span class="block font-bold text-zinc-900">Kelola Aset</span>
                        <span class="mt-1 block text-[10px] text-zinc-500">Administrasi dan audit</span>
                    </button>
                @endif
            </div>

            <section id="kib-inventory-panel" class="mt-4" role="tabpanel" aria-labelledby="kib-inventory-tab">
            <div id="kib-tabs"
                class="grid grid-cols-3 gap-2 rounded-xl
                        bg-zinc-100 p-1 sm:grid-cols-6">

                @foreach ([
        'A' => 'Tanah',
        'B' => 'Peralatan & Mesin',
        'C' => 'Gedung & Bangunan',
        'D' => 'Jalan, Irigasi & Jaringan',
        'E' => 'Aset Tetap Lainnya',
        'F' => 'Konstruksi dalam Pengerjaan',
    ] as $code => $label)
                    <button type="button"
                        class="kib-tab min-h-[58px] rounded-lg
                                   px-2 py-2 text-left transition"
                        data-kib-tab="{{ $code }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        aria-controls="kib-table-panel">

                        <span class="block text-sm font-bold">
                            KIB {{ $code }}
                        </span>

                        <span
                            class="mt-1 block text-[9px]
                                     leading-3 text-zinc-500">
                            {{ $label }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">

                <label class="sm:col-span-2">
                    <span class="mb-1.5 block text-[10px]
                                 font-semibold text-zinc-600">
                        Cari barang atau kode inventaris
                    </span>

                    <input id="kib-search" type="search" placeholder="Contoh: laptop, meja, INV-001..."
                        class="h-10 w-full rounded-xl border
                                  border-zinc-200 px-3 text-xs
                                  outline-none focus:border-zinc-400
                                  focus:ring-2 focus:ring-zinc-100">
                </label>

                <label>
                    <span class="mb-1.5 block text-[10px]
                                 font-semibold text-zinc-600">
                        Kondisi barang
                    </span>

                    <select id="kib-condition"
                        class="h-10 w-full rounded-xl border
                                   border-zinc-200 bg-white px-3
                                   text-xs outline-none
                                   focus:border-zinc-400">
                        <option value="">Semua kondisi</option>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center
                        justify-between gap-3">

                <div>
                    <div id="kib-result-count" class="text-xs font-semibold text-zinc-800">
                        0 barang
                    </div>

                    <p class="mt-1 text-[10px] text-zinc-500">
                        Data inventaris berdasarkan sekolah terpilih.
                    </p>
                </div>

                <button type="button" id="add-asset-button" hidden
                    class="inline-flex h-9 items-center gap-2
                               rounded-xl bg-zinc-950 px-3
                               text-xs font-semibold text-white
                               hover:bg-zinc-800">

                    <span class="text-base">+</span>
                    Kelola Aset
                </button>
            </div>

            <div id="kib-table-panel"
                class="mt-4 overflow-hidden rounded-xl
                        border border-zinc-200"
                aria-live="polite">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left">
                        <thead class="bg-zinc-50">
                            <tr class="border-b border-zinc-200">
                                <th
                                    class="px-4 py-3 text-[10px]
                                           font-bold text-zinc-500">
                                    KODE
                                </th>
                                <th
                                    class="px-4 py-3 text-[10px]
                                           font-bold text-zinc-500">
                                    NAMA BARANG
                                </th>
                                <th
                                    class="px-4 py-3 text-[10px]
                                           font-bold text-zinc-500">
                                    LOKASI PENEMPATAN
                                </th>
                                <th
                                    class="px-4 py-3 text-[10px]
                                           font-bold text-zinc-500">
                                    KONDISI
                                </th>
                                <th
                                    class="px-4 py-3 text-[10px]
                                           font-bold text-zinc-500">
                                    TAHUN
                                </th>
                            </tr>
                        </thead>

                        <tbody id="kib-table-body" class="divide-y divide-zinc-100 bg-white">
                        </tbody>
                    </table>
                </div>

                <div id="kib-empty-state" class="hidden px-5 py-12 text-center">

                    <div
                        class="mx-auto flex h-12 w-12 items-center
                                justify-center rounded-2xl bg-zinc-100">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            class="h-6 w-6 text-zinc-500">
                            <path d="M4 7h16v13H4z" />
                            <path d="M8 7V4h8v3M8 12h8M8 16h5" />
                        </svg>
                    </div>

                    <p class="mt-3 text-sm font-semibold text-zinc-800">
                        Tidak ada data inventaris
                    </p>

                    <p class="mt-1 text-xs text-zinc-500">
                        Coba ubah pencarian atau kategori kondisi.
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center
                        justify-between gap-3">

                <p id="kib-pagination-info" class="text-[10px] text-zinc-500">
                    Menampilkan 0 data
                </p>

                <div class="flex items-center gap-2">
                    <button type="button" id="kib-prev-page"
                        class="h-9 rounded-lg border border-zinc-200
                                   px-3 text-xs font-medium
                                   disabled:cursor-not-allowed
                                   disabled:opacity-40">
                        Sebelumnya
                    </button>

                    <span id="kib-page-number"
                        class="min-w-10 text-center text-xs
                                 font-semibold text-zinc-600">
                        0 / 0
                    </span>

                    <button type="button" id="kib-next-page"
                        class="h-9 rounded-lg border border-zinc-200
                                   px-3 text-xs font-medium
                                   disabled:cursor-not-allowed
                                   disabled:opacity-40">
                        Berikutnya
                    </button>
                </div>
            </div>
            </section>

            @if (auth()->user()?->role === 'admin')
                <section id="kib-management-panel" class="mt-4" role="tabpanel" aria-label="Kelola aset sekolah" hidden>
                    <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900">Kelola Aset</h3>
                            <p id="asset-admin-school-name" class="mt-1 text-xs text-zinc-500">Pengelolaan mengikuti sekolah terpilih.</p>
                        </div>
                        <span class="w-fit rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-zinc-500">Konteks sekolah aktif</span>
                    </div>

                    <nav id="asset-admin-tabs" class="grid grid-cols-2 gap-2 rounded-xl border border-zinc-200 bg-white p-1 sm:grid-cols-4" role="tablist" aria-label="Fungsi pengelolaan aset">
                        <button type="button" data-asset-admin-tab="perolehan" role="tab" aria-selected="true" class="asset-admin-tab rounded-lg bg-zinc-950 px-3 py-2.5 text-xs font-semibold text-white">Perolehan</button>
                        <button type="button" data-asset-admin-tab="perubahan" role="tab" aria-selected="false" class="asset-admin-tab rounded-lg px-3 py-2.5 text-xs font-semibold text-zinc-600 hover:bg-zinc-100">Pengajuan Perubahan</button>
                        <button type="button" data-asset-admin-tab="riwayat" role="tab" aria-selected="false" class="asset-admin-tab rounded-lg px-3 py-2.5 text-xs font-semibold text-zinc-600 hover:bg-zinc-100">Riwayat &amp; Audit</button>
                        <button type="button" data-asset-admin-tab="jenis" role="tab" aria-selected="false" class="asset-admin-tab rounded-lg px-3 py-2.5 text-xs font-semibold text-zinc-600 hover:bg-zinc-100">Master Jenis Barang</button>
                    </nav>

                    <div id="asset-admin-feedback" hidden role="status" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-900"></div>
                    <div id="asset-admin-tab-content" class="mt-4 space-y-4" aria-live="polite">
                        <div class="rounded-xl border border-zinc-200 px-5 py-10 text-center text-xs text-zinc-500">Pilih tab untuk memuat data pengelolaan aset.</div>
                    </div>
                </section>
            @endif
        </div>

        <footer
            class="flex items-center justify-between gap-3
                       border-t border-zinc-200 bg-zinc-50/80
                       px-4 py-3 sm:px-6">

            <div class="hidden min-w-0 items-center gap-3 sm:flex">
                <p class="text-[10px] leading-5 text-zinc-500">Inventaris, verifikasi, perubahan, dan audit dapat diakses dari tab sesuai peran pengguna.</p>
            </div>

            <button type="button" data-close-modal
                class="ml-auto h-9 rounded-xl border
                           border-zinc-200 bg-white px-4
                           text-xs font-semibold text-zinc-700
                           hover:bg-zinc-100">
                Tutup
            </button>
        </footer>
    </section>
</div>
