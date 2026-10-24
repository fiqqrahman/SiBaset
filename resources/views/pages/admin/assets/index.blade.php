@extends('layouts.app')

@section('title', 'Manajemen Aset')

@section('content')
<div class="min-h-screen bg-zinc-50">
    @include('partials.header')
    <main class="mx-auto max-w-[1500px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><div class="font-bold">Perubahan belum disimpan</div><ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">Administrasi inventaris</div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight">Manajemen Aset</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500">Periksa dokumen perolehan, tetapkan status kepemilikan berdasarkan bukti, proses pengajuan perubahan, dan kelola master jenis barang.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50">Kembali ke peta</a>
        </header>

        <section class="grid gap-3 sm:grid-cols-3">
            @foreach ([['label' => 'Perolehan menunggu verifikasi', 'value' => $summary['pending_acquisitions'], 'hint' => 'Kelompok dan dokumen'], ['label' => 'Perubahan menunggu verifikasi', 'value' => $summary['pending_changes'], 'hint' => 'Kondisi / lokasi aset'], ['label' => 'Unit aset disetujui', 'value' => $summary['approved_assets'], 'hint' => 'Status aset yang aktif']] as $item)
                <article class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-xs font-medium text-zinc-500">{{ $item['label'] }}</div><div class="mt-3 text-3xl font-bold tracking-tight tabular-nums">{{ number_format($item['value'], 0, ',', '.') }}</div><div class="mt-2 text-[11px] text-zinc-400">{{ $item['hint'] }}</div></article>
            @endforeach
        </section>

        <nav class="flex flex-wrap gap-2 border-b border-zinc-200 pb-3" aria-label="Bagian manajemen aset">
            @foreach (['perolehan' => 'Verifikasi perolehan', 'perubahan' => 'Pengajuan perubahan', 'jenis' => 'Master jenis barang', 'riwayat' => 'Riwayat & audit'] as $value => $label)
                <a href="{{ route('admin.assets.index', ['tab' => $value]) }}" @class(['rounded-xl px-4 py-2.5 text-xs font-semibold transition', 'bg-zinc-950 text-white' => $tab === $value, 'border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-100' => $tab !== $value]) @if($tab === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        @if ($tab === 'perolehan')
            <section class="space-y-4">
                <div><h2 class="text-sm font-bold">Antrean perolehan dan dokumen</h2><p class="mt-1 text-xs text-zinc-500">Pemeriksaan berlaku untuk satu kelompok perolehan beserta semua unit dan dokumennya.</p></div>
                @forelse ($acquisitions as $acquisition)
                    <article class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                        <header class="flex flex-col gap-3 border-b border-zinc-100 bg-zinc-50/70 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
                            <div>
                                <div class="flex flex-wrap items-center gap-2"><h3 class="text-sm font-bold">{{ $acquisition->school->nama }}</h3><span class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-[10px] text-zinc-500">NPSN {{ $acquisition->school->npsn }}</span></div>
                                <p class="mt-1 text-xs text-zinc-500">Perolehan #{{ $acquisition->id }} · {{ $acquisition->assets->count() }} unit · Diajukan {{ $acquisition->created_at?->translatedFormat('d M Y, H.i') }}</p>
                                @if ($acquisition->review_note)
                                    <p class="mt-3 max-w-3xl rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs leading-5 text-rose-900"><strong>Catatan sebelumnya:</strong> {{ $acquisition->review_note }}</p>
                                @endif
                            </div>
                            <span class="w-fit rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[10px] font-semibold text-amber-800">{{ $acquisition->ownership_status === 'needs_revision' ? 'Perlu perbaikan' : 'Menunggu verifikasi' }}</span>
                        </header>

                        <div class="grid gap-5 p-4 sm:p-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                            <div class="space-y-5">
                                <section>
                                    <h4 class="text-xs font-bold">Informasi perolehan</h4>
                                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                                        @foreach ([
                                            ['Sumber dana', ['apbd_kota' => 'APBD Kota', 'apbd_provinsi' => 'APBD Provinsi', 'apbn' => 'APBN / Pemerintah Pusat', 'bos_bosp' => 'BOS / BOSP', 'hibah' => 'Hibah', 'bantuan_pemerintah' => 'Bantuan Pemerintah', 'swadaya' => 'Swadaya Sekolah', 'lainnya' => 'Lainnya'][$acquisition->funding_source] ?? $acquisition->funding_source],
                                            ['Cara perolehan', str_replace('_', ' ', $acquisition->acquisition_method)],
                                            ['Entitas pemilik yang diajukan', ['pemkot' => 'Pemerintah Kota Palangka Raya', 'pemprov' => 'Pemerintah Provinsi', 'pemerintah_pusat' => 'Pemerintah Pusat', 'yayasan' => 'Yayasan', 'pihak_lain' => 'Pihak lain', 'belum_ditentukan' => 'Belum ditentukan'][$acquisition->owner_entity] ?? $acquisition->owner_entity],
                                            ['Pemberi bantuan / penyedia', $acquisition->provider_name ?: '—'],
                                            ['Nomor referensi', $acquisition->document_reference ?: '—'],
                                            ['Tanggal perolehan', $acquisition->acquired_at?->format('d M Y') ?? '—'],
                                            ['Nilai total', $acquisition->total_value !== null ? 'Rp '.number_format((float) $acquisition->total_value, 2, ',', '.') : '—'],
                                        ] as [$label, $value])
                                            <div class="rounded-xl border border-zinc-200 p-3"><dt class="text-[10px] text-zinc-500">{{ $label }}</dt><dd class="mt-1 break-words text-xs font-semibold text-zinc-800">{{ $value }}</dd></div>
                                        @endforeach
                                    </dl>
                                    @if ($acquisition->notes)
                                        <p class="mt-3 text-xs leading-5 text-zinc-600">{{ $acquisition->notes }}</p>
                                    @endif
                                </section>

                                <section>
                                    <h4 class="text-xs font-bold">Unit aset pada kelompok ini</h4>
                                    <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200">
                                        <table class="w-full min-w-[560px] text-left">
                                            <thead class="bg-zinc-50 text-[10px] font-semibold text-zinc-500"><tr><th class="px-3 py-2.5">Kode / nama</th><th class="px-3 py-2.5">Klasifikasi</th><th class="px-3 py-2.5">Nomor seri</th><th class="px-3 py-2.5">Kondisi</th></tr></thead>
                                            <tbody class="divide-y divide-zinc-100">
                                                @foreach ($acquisition->assets as $asset)
                                                    <tr><td class="px-3 py-3"><div class="text-xs font-semibold">{{ $asset->name }}</div><div class="mt-1 text-[10px] text-zinc-500">{{ $asset->asset_code }}</div></td><td class="px-3 py-3"><div class="text-xs">{{ $asset->type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$asset->type->category->code }}</div><div class="mt-1 text-[10px] text-zinc-500">{{ $asset->type->name }}</div></td><td class="px-3 py-3 text-xs text-zinc-600">{{ $asset->serial_number ?: '—' }}</td><td class="px-3 py-3 text-xs text-zinc-600">{{ ['baik' => 'Baik', 'rusak_ringan' => 'Rusak ringan', 'rusak_berat' => 'Rusak berat', 'tidak_diketahui' => 'Belum diketahui'][$asset->condition] ?? $asset->condition }}</td></tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            </div>

                            <div class="space-y-5">
                                <section>
                                    <h4 class="text-xs font-bold">Dokumen pendukung</h4>
                                    <div class="mt-3 space-y-2">
                                        @forelse ($acquisition->documents as $document)
                                            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-[10px] font-bold text-zinc-600">{{ strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}</div>
                                                <div class="min-w-0 flex-1"><div class="break-words text-xs font-semibold">{{ $document->original_name }}</div><div class="mt-1 text-[10px] text-zinc-500">{{ str_replace('_', ' ', $document->document_kind) }} · {{ number_format($document->size_bytes / 1024, 0, ',', '.') }} KB</div></div>
                                                <a href="{{ route('admin.assets.documents.download', $document) }}" class="shrink-0 text-xs font-semibold underline decoration-zinc-300 underline-offset-4">Lihat</a>
                                            </div>
                                        @empty
                                            <p class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">Belum ada dokumen terlampir. Jangan menetapkan kepemilikan sebelum bukti pendukung diperiksa.</p>
                                        @endforelse
                                    </div>
                                </section>

                                <form method="POST" action="{{ route('admin.assets.acquisitions.review', $acquisition) }}" class="space-y-3 rounded-xl border border-zinc-200 p-4">
                                    @csrf
                                    <div><h4 class="text-xs font-bold">Keputusan pemeriksaan</h4><p class="mt-1 text-[10px] leading-5 text-zinc-500">Pilih status berdasarkan dokumen dan aturan perolehan; sumber dana saja tidak menetapkan pemilik.</p></div>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Hasil</span><select name="decision" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs">
                                        <option value="">Pilih keputusan</option>
                                        <option value="verified_bmd">Terverifikasi sebagai BMD</option>
                                        <option value="verified_non_bmd">Terverifikasi bukan BMD Kota</option>
                                        <option value="needs_revision">Kembalikan untuk perbaikan</option>
                                        <option value="rejected">Tolak pencatatan</option>
                                    </select></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Pemilik yang disahkan admin</span><select name="verified_owner_entity" class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs">
                                        @foreach (['belum_ditentukan' => 'Belum ditentukan', 'pemkot' => 'Pemerintah Kota Palangka Raya', 'pemprov' => 'Pemerintah Provinsi', 'pemerintah_pusat' => 'Pemerintah Pusat', 'yayasan' => 'Yayasan', 'pihak_lain' => 'Pihak lain'] as $value => $label)
                                            <option value="{{ $value }}" @selected($acquisition->owner_entity === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select><span class="mt-1 block text-[10px] leading-4 text-zinc-500">Untuk BMD, pilih Pemerintah Kota Palangka Raya. Untuk non-BMD Kota, pilih entitas lain yang didukung dokumen.</span></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Catatan pemeriksa</span><textarea name="review_note" rows="3" maxlength="3000" class="w-full rounded-xl border border-zinc-200 px-3 py-2 text-xs focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-100" placeholder="Tuliskan alasan atau berkas yang perlu dilengkapi. Wajib untuk keputusan perbaikan/penolakan."></textarea></label>
                                    <button type="submit" class="min-h-11 w-full rounded-xl bg-zinc-950 px-4 py-3 text-xs font-semibold text-white hover:bg-zinc-800">Simpan keputusan</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-5 py-16 text-center"><div class="text-sm font-semibold text-zinc-800">Tidak ada perolehan menunggu pemeriksaan</div><p class="mt-1 text-xs text-zinc-500">Kelompok perolehan yang dikirim sekolah akan muncul di bagian ini.</p></div>
                @endforelse
                <div>{{ $acquisitions->links() }}</div>
            </section>
        @elseif ($tab === 'perubahan')
            <section class="space-y-4">
                <div><h2 class="text-sm font-bold">Pengajuan perubahan aset</h2><p class="mt-1 text-xs text-zinc-500">Data resmi tidak berubah sampai admin menyetujui pengajuan.</p></div>
                @forelse ($changeRequests as $change)
                    <article class="rounded-2xl border border-zinc-200 bg-white p-4 sm:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div><h3 class="text-sm font-bold">{{ $change->asset->name }} · {{ $change->asset->asset_code }}</h3><p class="mt-1 text-xs text-zinc-500">{{ $change->asset->school->nama }} · NPSN {{ $change->asset->school->npsn }}</p><p class="mt-2 text-xs leading-5">{{ $change->request_note }}</p><div class="mt-3 rounded-xl bg-zinc-50 p-3"><div class="text-[10px] font-semibold text-zinc-500">Perubahan yang diajukan</div><dl class="mt-2 grid gap-2 sm:grid-cols-2">@foreach ($change->proposed_changes ?? [] as $key => $value)<div><dt class="text-[10px] text-zinc-500">{{ ['condition' => 'Kondisi', 'location' => 'Lokasi', 'notes' => 'Catatan'][$key] ?? $key }}</dt><dd class="mt-1 whitespace-pre-line break-words text-xs text-zinc-800">{{ is_scalar($value) ? ($value === '' ? '—' : $value) : '—' }}</dd></div>@endforeach</dl></div></div>
                            <span class="w-fit rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[10px] font-semibold text-amber-800">Menunggu pemeriksaan</span>
                        </div>
                        <form method="POST" action="{{ route('admin.assets.changes.review', $change) }}" class="mt-5 grid gap-3 border-t border-zinc-100 pt-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)_auto] sm:items-end">
                            @csrf
                            <label class="block"><span class="mb-1.5 block text-xs font-semibold">Hasil</span><select name="decision" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs"><option value="approved">Setujui perubahan</option><option value="needs_revision">Minta perbaikan</option><option value="rejected">Tolak</option></select></label>
                            <label class="block"><span class="mb-1.5 block text-xs font-semibold">Catatan admin</span><input name="review_note" maxlength="3000" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs" placeholder="Opsional; wajib bila perlu perbaikan/ditolak"></label>
                            <button class="min-h-11 rounded-xl bg-zinc-950 px-4 py-3 text-xs font-semibold text-white hover:bg-zinc-800">Simpan</button>
                        </form>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-5 py-16 text-center"><div class="text-sm font-semibold text-zinc-800">Tidak ada pengajuan perubahan</div><p class="mt-1 text-xs text-zinc-500">Pengajuan kondisi atau lokasi dari sekolah muncul di sini.</p></div>
                @endforelse
                <div>{{ $changeRequests->links() }}</div>
            </section>
        @elseif ($tab === 'jenis')
            <section class="grid gap-5 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 sm:p-5">
                    <h2 class="text-sm font-bold">Tambah jenis barang</h2>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">Gunakan nama jenis yang konsisten. Isi kode klasifikasi hanya jika sudah diverifikasi dari standar resmi yang digunakan.</p>
                    <form method="POST" action="{{ route('admin.assets.types.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Kategori <span class="text-rose-600">*</span></span><select name="asset_category_id" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs">@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$category->code }} · {{ $category->name }}</option>@endforeach</select></label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Nama jenis barang <span class="text-rose-600">*</span></span><input name="name" value="{{ old('name') }}" required maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs" placeholder="Contoh: Perangkat audio"></label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Kode klasifikasi resmi</span><input name="classification_code" value="{{ old('classification_code') }}" maxlength="100" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs" placeholder="Opsional"></label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Deskripsi</span><textarea name="description" rows="3" maxlength="2000" class="w-full rounded-xl border border-zinc-200 px-3 py-2 text-xs">{{ old('description') }}</textarea></label>
                        <button class="min-h-11 w-full rounded-xl bg-zinc-950 px-4 py-3 text-xs font-semibold text-white hover:bg-zinc-800">Tambahkan ke master</button>
                    </form>
                </div>
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                    <div class="border-b border-zinc-100 p-4 sm:p-5"><h2 class="text-sm font-bold">Master kategori dan jenis barang</h2><p class="mt-1 text-xs text-zinc-500">Kategori dipilih dari master untuk mencegah teks klasifikasi bebas dan duplikat.</p></div>
                    <div class="divide-y divide-zinc-100">
                        @foreach ($categories as $category)
                            <section class="p-4 sm:p-5">
                                <div class="flex flex-wrap items-center justify-between gap-3"><div><div class="text-xs font-bold">{{ $category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$category->code }} · {{ $category->name }}</div><div class="mt-1 text-[10px] text-zinc-500">{{ $category->types_count }} jenis pada kategori ini</div></div><span class="rounded-md border border-zinc-200 px-2 py-1 text-[10px] text-zinc-500">{{ $category->scheme }}</span></div>
                            </section>
                        @endforeach
                    </div>
                    <div class="border-t border-zinc-100 p-4">
                        <div class="space-y-2">
                            @foreach ($types as $type)
                                <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0"><div class="text-xs font-semibold">{{ $type->name }}</div><div class="mt-1 text-[10px] text-zinc-500">{{ $type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$type->category->code }} · {{ $type->category->name }}{{ $type->classification_code ? ' · '.$type->classification_code : '' }}</div><div class="mt-1 text-[10px] {{ $type->is_active ? 'text-emerald-700' : 'text-zinc-500' }}">{{ $type->is_active ? 'Aktif' : 'Nonaktif' }}</div></div>
                                    <form method="POST" action="{{ route('admin.assets.types.toggle', $type) }}">@csrf<button class="min-h-9 rounded-lg border border-zinc-200 px-3 py-2 text-[10px] font-semibold hover:bg-zinc-50">{{ $type->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $types->links() }}</div>
                    </div>
                </div>
            </section>
        @else
            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-bold">Riwayat perolehan dan jejak pemeriksaan</h2>
                    <p class="mt-1 text-xs text-zinc-500">Admin tetap dapat membuka dokumen yang telah diperiksa dan menelusuri keputusan sebelumnya.</p>
                </div>
                @forelse ($history as $acquisition)
                    @php
                        $historyStatus = [
                            'verified_bmd' => ['Terverifikasi sebagai BMD', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
                            'verified_non_bmd' => ['Terverifikasi bukan BMD Kota', 'border-sky-200 bg-sky-50 text-sky-800'],
                            'rejected' => ['Ditolak', 'border-zinc-300 bg-zinc-100 text-zinc-700'],
                        ][$acquisition->ownership_status] ?? ['Status tidak diketahui', 'border-zinc-200 bg-zinc-50 text-zinc-700'];
                    @endphp
                    <article class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                        <header class="flex flex-col gap-3 border-b border-zinc-100 bg-zinc-50/70 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
                            <div>
                                <h3 class="text-sm font-bold">{{ $acquisition->school->nama }} · Perolehan #{{ $acquisition->id }}</h3>
                                <p class="mt-1 text-xs text-zinc-500">NPSN {{ $acquisition->school->npsn }} · {{ $acquisition->assets->count() }} unit · diperiksa {{ $acquisition->reviewed_at?->translatedFormat('d M Y, H.i') ?? '—' }}</p>
                                <p class="mt-2 text-xs text-zinc-600">{{ in_array($acquisition->ownership_status, ['verified_bmd', 'verified_non_bmd'], true) ? 'Pemilik terverifikasi:' : 'Pemilik yang diajukan:' }} {{ ['pemkot' => 'Pemerintah Kota Palangka Raya', 'pemprov' => 'Pemerintah Provinsi', 'pemerintah_pusat' => 'Pemerintah Pusat', 'yayasan' => 'Yayasan', 'pihak_lain' => 'Pihak lain', 'belum_ditentukan' => 'Belum ditentukan'][$acquisition->owner_entity] ?? $acquisition->owner_entity }}</p>
                            </div>
                            <span class="w-fit rounded-lg border px-2.5 py-1.5 text-[10px] font-semibold {{ $historyStatus[1] }}">{{ $historyStatus[0] }}</span>
                        </header>
                        <div class="grid gap-5 p-4 sm:p-5 xl:grid-cols-2">
                            <section>
                                <h4 class="text-xs font-bold">Dokumen perolehan</h4>
                                <div class="mt-3 space-y-2">
                                    @forelse ($acquisition->documents as $document)
                                        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-[10px] font-bold text-zinc-600">{{ strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}</div>
                                            <div class="min-w-0 flex-1"><div class="break-words text-xs font-semibold">{{ $document->original_name }}</div><div class="mt-1 text-[10px] text-zinc-500">{{ str_replace('_', ' ', $document->document_kind) }} · {{ number_format($document->size_bytes / 1024, 0, ',', '.') }} KB</div></div>
                                            <a href="{{ route('admin.assets.documents.download', $document) }}" class="shrink-0 text-xs font-semibold underline decoration-zinc-300 underline-offset-4">Lihat dokumen</a>
                                        </div>
                                    @empty
                                        <p class="text-xs text-zinc-500">Tidak ada dokumen yang terlampir.</p>
                                    @endforelse
                                </div>
                            </section>
                            <section>
                                <h4 class="text-xs font-bold">Jejak aktivitas</h4>
                                <ol class="mt-3 space-y-3">
                                    @forelse ($acquisition->events->sortByDesc('created_at')->take(8) as $event)
                                        <li class="flex gap-3">
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-zinc-400"></span>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-semibold text-zinc-800">{{ [
                                                    'asset_submitted' => 'Unit aset dicatat',
                                                    'acquisition_submitted' => 'Perolehan dikirim untuk pemeriksaan',
                                                    'acquisition_resubmitted' => 'Perolehan dikirim ulang setelah perbaikan',
                                                    'asset_resubmitted' => 'Data unit aset diperbaiki dan dikirim ulang',
                                                    'acquisition_reviewed' => 'Keputusan pemeriksaan perolehan dicatat',
                                                    'document_uploaded' => 'Dokumen pendukung diunggah',
                                                    'asset_reviewed' => 'Status unit aset diperbarui oleh admin',
                                                    'change_requested' => 'Permintaan perubahan aset diajukan',
                                                    'change_approved' => 'Perubahan aset disetujui',
                                                    'change_needs_revision' => 'Permintaan perubahan perlu diperbaiki',
                                                    'change_rejected' => 'Permintaan perubahan ditolak',
                                                ][$event->event_type] ?? str_replace('_', ' ', $event->event_type) }}</div>
                                                <div class="mt-1 text-[10px] text-zinc-500">{{ $event->created_at?->translatedFormat('d M Y, H.i') ?? '—' }} · {{ $event->actor?->name ?? 'Akun tidak tersedia' }}</div>
                                                @if (is_array($event->details) && isset($event->details['previous'], $event->details['current']) && is_array($event->details['previous']) && is_array($event->details['current']))
                                                    <div class="mt-2 space-y-1">
                                                        @foreach ($event->details['previous'] as $key => $oldValue)
                                                            @php $newValue = $event->details['current'][$key] ?? null; @endphp
                                                            @if (($oldValue ?? null) != $newValue)
                                                                <p class="break-words text-[10px] leading-4 text-zinc-600"><span class="font-semibold">{{ str_replace('_', ' ', $key) }}:</span> {{ $oldValue ?? '—' }} → {{ $newValue ?? '—' }}</p>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if (is_array($event->details) && !empty($event->details['review_note']))
                                                    <p class="mt-1 whitespace-pre-line text-[10px] leading-4 text-zinc-600">{{ $event->details['review_note'] }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @empty
                                        <li class="text-xs text-zinc-500">Belum ada riwayat aktivitas.</li>
                                    @endforelse
                                </ol>
                                @if ($acquisition->review_note)
                                    <p class="mt-4 rounded-xl bg-zinc-50 p-3 text-xs leading-5 text-zinc-700"><strong>Catatan pemeriksaan terakhir:</strong> {{ $acquisition->review_note }}</p>
                                @endif
                            </section>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-5 py-16 text-center"><div class="text-sm font-semibold text-zinc-800">Belum ada riwayat verifikasi</div><p class="mt-1 text-xs text-zinc-500">Perolehan yang disetujui atau ditolak akan tercatat di sini.</p></div>
                @endforelse
                <div>{{ $history->links() }}</div>
            </section>
        @endif
    </main>
</div>
@endsection
