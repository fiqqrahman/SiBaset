@extends('layouts.school')

@section('title', 'Data Aset Sekolah')
@section('page-heading', 'Data Aset')

@section('content')
@php
    $statusLabels = [
        'pending_verification' => ['Menunggu verifikasi', 'border-amber-200 bg-amber-50 text-amber-800'],
        'approved' => ['Disetujui', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
        'needs_revision' => ['Perlu perbaikan', 'border-rose-200 bg-rose-50 text-rose-800'],
        'rejected' => ['Ditolak', 'border-zinc-200 bg-zinc-100 text-zinc-700'],
    ];
    $ownershipLabels = [
        'pending_verification' => 'Menunggu verifikasi kepemilikan',
        'verified_bmd' => 'Terverifikasi sebagai BMD',
        'verified_non_bmd' => 'Terverifikasi bukan BMD Kota',
        'needs_revision' => 'Dokumen perlu perbaikan',
        'rejected' => 'Perolehan ditolak',
    ];
@endphp

<div class="mx-auto max-w-[1440px] space-y-6">
    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <div class="font-bold">Data belum dapat disimpan</div>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">Inventaris sekolah</div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight">Data Aset</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500">Catat setiap unit aset, simpan bukti perolehan, dan pantau pemeriksaan Dinas Pendidikan.</p>
            <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-500">
                <span class="rounded-lg border border-zinc-200 bg-white px-3 py-2">{{ $school->nama }}</span>
                <span class="rounded-lg border border-zinc-200 bg-white px-3 py-2">NPSN {{ $school->npsn }}</span>
            </div>
        </div>
        <a href="#form-aset" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-zinc-950 px-4 py-2.5 text-xs font-semibold text-white hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2">+ Catat perolehan</a>
    </header>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan aset">
        @foreach ([
            ['label' => 'Total aset', 'value' => $summary['total'], 'tone' => 'zinc'],
            ['label' => 'Menunggu verifikasi', 'value' => $summary['pending'], 'tone' => 'amber'],
            ['label' => 'Disetujui', 'value' => $summary['approved'], 'tone' => 'emerald'],
            ['label' => 'Perlu perbaikan', 'value' => $summary['needs_revision'], 'tone' => 'rose'],
        ] as $item)
            @php $borderTone = ['zinc' => 'border-zinc-200', 'amber' => 'border-amber-200', 'emerald' => 'border-emerald-200', 'rose' => 'border-rose-200']; @endphp
            <article class="rounded-2xl border bg-white p-4 {{ $borderTone[$item['tone']] }}">
                <div class="text-xs font-medium text-zinc-500">{{ $item['label'] }}</div>
                <div class="mt-3 text-3xl font-bold tracking-tight tabular-nums">{{ number_format($item['value'], 0, ',', '.') }}</div>
                <div class="mt-2 text-[11px] text-zinc-400">Data inventaris tersimpan</div>
            </article>
        @endforeach
    </section>

    @if ($revisionAcquisitions->isNotEmpty())
        <section class="rounded-2xl border border-rose-200 bg-white p-4 sm:p-5">
            <h2 class="text-sm font-bold">Perolehan yang perlu diperbaiki</h2>
            <p class="mt-1 text-xs text-zinc-500">Baca catatan admin lalu lengkapi data atau dokumen tanpa membuat salinan aset.</p>
            <div class="mt-4 space-y-3">
                @foreach ($revisionAcquisitions as $acquisition)
                    <article class="rounded-xl border border-zinc-200 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="text-xs font-bold">Perolehan #{{ $acquisition->id }} · {{ $acquisition->assets->count() }} unit</div>
                                <div class="mt-1 text-[10px] text-zinc-500">{{ $acquisition->document_reference ?: 'Tanpa nomor referensi' }} · {{ $acquisition->getRawOriginal('funding_source') }}</div>
                                @if ($acquisition->review_note)
                                    <p class="mt-3 rounded-lg bg-rose-50 p-3 text-xs leading-5 text-rose-900"><strong>Catatan admin:</strong> {{ $acquisition->review_note }}</p>
                                @endif
                            </div>
                            <a href="{{ route('school.assets.acquisitions.edit', $acquisition) }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl border border-zinc-300 px-4 py-2 text-xs font-semibold hover:bg-zinc-50">Perbaiki data</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section id="form-aset" class="scroll-mt-24 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <details>
            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-4 py-4 transition hover:bg-zinc-50 sm:px-5">
                <span><span class="block text-sm font-bold">Catat kelompok perolehan baru</span><span class="mt-1 block text-xs text-zinc-500">Satu kelompok perolehan dapat memuat beberapa unit dan satu BAST bersama.</span></span>
                <span class="rounded-xl bg-zinc-950 px-3 py-2 text-xs font-semibold text-white">Buka formulir</span>
            </summary>

            <form method="POST" action="{{ route('school.assets.store') }}" enctype="multipart/form-data" class="space-y-6 border-t border-zinc-100 p-4 sm:p-6">
                @csrf
                <section class="space-y-4">
                    <div><h2 class="text-sm font-bold">A. Informasi perolehan</h2><p class="mt-1 text-xs text-zinc-500">Asal dana tidak otomatis menentukan siapa pemilik barang.</p></div>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Sumber dana <span class="text-rose-600">*</span></span>
                            <select name="funding_source" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">
                                <option value="">Pilih sumber dana</option>
                                @foreach (['apbd_kota' => 'APBD Kota', 'apbd_provinsi' => 'APBD Provinsi', 'apbn' => 'APBN / Pemerintah Pusat', 'bos_bosp' => 'BOS / BOSP', 'hibah' => 'Hibah', 'bantuan_pemerintah' => 'Bantuan Pemerintah', 'swadaya' => 'Swadaya Sekolah', 'lainnya' => 'Lainnya'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('funding_source') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Cara perolehan <span class="text-rose-600">*</span></span>
                            <select name="acquisition_method" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">
                                <option value="">Pilih cara perolehan</option>
                                @foreach (['pembelian' => 'Pembelian', 'bantuan' => 'Bantuan', 'hibah' => 'Hibah', 'transfer' => 'Transfer / penyerahan', 'donasi' => 'Donasi', 'lainnya' => 'Lainnya'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('acquisition_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Pemilik menurut dokumen <span class="text-rose-600">*</span></span>
                            <select name="owner_entity" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">
                                @foreach (['belum_ditentukan' => 'Belum ditentukan / perlu verifikasi', 'pemkot' => 'Pemerintah Kota Palangka Raya', 'pemprov' => 'Pemerintah Provinsi', 'pemerintah_pusat' => 'Pemerintah Pusat', 'yayasan' => 'Yayasan', 'pihak_lain' => 'Pihak lain'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('owner_entity', 'belum_ditentukan') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Pemberi bantuan / penyedia</span><input name="provider_name" value="{{ old('provider_name') }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="Contoh: kementerian / penyedia"></label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Nomor dokumen / referensi</span><input name="document_reference" value="{{ old('document_reference') }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="BAST / kontrak / bukti"></label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Tanggal perolehan</span><input type="date" name="acquired_at" value="{{ old('acquired_at') }}" max="{{ now()->toDateString() }}" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                        <label class="block"><span class="mb-1.5 block text-xs font-semibold">Nilai total kelompok (Rp)</span><input type="number" name="total_value" value="{{ old('total_value') }}" min="0" step="0.01" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="Opsional"></label>
                        <label class="block sm:col-span-2"><span class="mb-1.5 block text-xs font-semibold">Catatan perolehan</span><textarea name="acquisition_notes" rows="2" maxlength="5000" class="w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">{{ old('acquisition_notes') }}</textarea></label>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-950">Status BMD atau non-BMD ditetapkan setelah dokumen dan aturan perolehan diperiksa Dinas.</div>
                </section>

                <section class="space-y-4 border-t border-zinc-100 pt-5">
                    <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-sm font-bold">B. Unit aset</h2><p class="mt-1 text-xs text-zinc-500">Setiap barang memiliki kode sendiri; isikan nomor seri untuk perangkat yang memilikinya.</p></div><button type="button" id="add-asset-row" class="min-h-10 rounded-xl border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50">+ Tambah unit</button></div>
                    <div id="asset-items" class="space-y-4">
                        @foreach (old('items', [[]]) as $index => $item)
                            <article data-asset-item class="rounded-xl border border-zinc-200 p-4">
                                <div class="mb-4 flex items-center justify-between gap-3"><h3 class="text-xs font-bold">Unit aset <span data-item-number>{{ $index + 1 }}</span></h3><button type="button" data-remove-item class="text-xs font-semibold text-rose-700 hover:underline" @disabled($loop->first)>Hapus unit</button></div>
                                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Kode inventaris *</span><input name="items[{{ $index }}][asset_code]" value="{{ $item['asset_code'] ?? '' }}" required maxlength="80" pattern="[A-Za-z0-9][A-Za-z0-9._/-]*" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm uppercase" placeholder="INV-0001"></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Nama barang *</span><input name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" required maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="Laptop pembelajaran"></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Jenis / klasifikasi *</span><select name="items[{{ $index }}][asset_type_id]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm"><option value="">Pilih jenis barang</option>@foreach ($assetTypes as $type)<option value="{{ $type->id }}" @selected((string) ($item['asset_type_id'] ?? '') === (string) $type->id)>{{ $type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$type->category->code }} · {{ $type->category->name }} — {{ $type->name }}</option>@endforeach</select></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Merek / model</span><input name="items[{{ $index }}][brand_model]" value="{{ $item['brand_model'] ?? '' }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Nomor seri</span><input name="items[{{ $index }}][serial_number]" value="{{ $item['serial_number'] ?? '' }}" maxlength="160" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="Disarankan untuk laptop / IFP"></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Lokasi</span><input name="items[{{ $index }}][location]" value="{{ $item['location'] ?? '' }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="Ruang kelas / lab"></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Kondisi *</span><select name="items[{{ $index }}][condition]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach (['baik' => 'Baik', 'rusak_ringan' => 'Rusak ringan', 'rusak_berat' => 'Rusak berat', 'tidak_diketahui' => 'Belum diketahui'] as $value => $label)<option value="{{ $value }}" @selected(($item['condition'] ?? 'baik') === $value)>{{ $label }}</option>@endforeach</select></label>
                                    <label class="block"><span class="mb-1.5 block text-xs font-semibold">Tahun perolehan</span><input type="number" name="items[{{ $index }}][year_acquired]" value="{{ $item['year_acquired'] ?? '' }}" min="1900" max="{{ now()->year + 1 }}" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                                    <label class="block sm:col-span-2"><span class="mb-1.5 block text-xs font-semibold">Catatan barang</span><textarea name="items[{{ $index }}][notes]" rows="2" maxlength="3000" class="w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">{{ $item['notes'] ?? '' }}</textarea></label>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="space-y-4 border-t border-zinc-100 pt-5">
                    <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-sm font-bold">C. Dokumen pendukung</h2><p class="mt-1 text-xs text-zinc-500">Unggah BAST, bukti pembelian, BAPP, dokumen hibah/bantuan atau bukti lain yang sesuai.</p></div><button type="button" id="add-document-row" class="min-h-10 rounded-xl border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50">+ Tambah dokumen</button></div>
                    <div id="asset-documents" class="space-y-3">
                        @foreach (old('documents', [['kind' => 'bast']]) as $index => $document)
                            <article data-document-item class="grid gap-3 rounded-xl border border-zinc-200 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end">
                                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Jenis dokumen *</span><select name="documents[{{ $index }}][kind]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach (['bast' => 'BAST', 'bapp' => 'BAPP', 'bukti_pembelian' => 'Bukti pembelian', 'kontrak_pesanan' => 'Kontrak / pesanan', 'dokumen_hibah' => 'Dokumen hibah', 'dokumen_bantuan' => 'Dokumen bantuan', 'dokumen_pendukung' => 'Dokumen pendukung lain'] as $value => $label)<option value="{{ $value }}" @selected(($document['kind'] ?? 'bast') === $value)>{{ $label }}</option>@endforeach</select></label>
                                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Berkas maks. 10 MB *</span><input type="file" name="documents[{{ $index }}][file]" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs"></label>
                                <button type="button" data-remove-document class="min-h-10 rounded-xl px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50" @disabled($loop->first)>Hapus</button>
                            </article>
                        @endforeach
                    </div>
                    <p class="text-xs leading-5 text-zinc-500">Format PDF, JPG, PNG, WEBP. File disimpan privat dan hanya dapat diakses pengguna berwenang.</p>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-xl text-xs leading-5 text-zinc-500">Aset akan menunggu pemeriksaan. Sistem tidak otomatis menetapkan status BMD Kota.</p>
                    <button type="submit" class="min-h-11 rounded-xl bg-zinc-950 px-5 py-3 text-xs font-semibold text-white hover:bg-zinc-800">Simpan dan kirim verifikasi</button>
                </div>
            </form>
        </details>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-100 p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><h2 class="text-sm font-bold">Daftar inventaris</h2><p class="mt-1 text-xs text-zinc-500">Setiap baris adalah satu unit; klasifikasi berasal dari master jenis barang.</p></div>
                <form method="GET" action="{{ route('school.assets.index') }}" class="grid gap-2 sm:grid-cols-3">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode, nama, nomor seri..." aria-label="Cari aset" class="min-h-10 rounded-xl border border-zinc-200 px-3 text-xs focus:border-zinc-500 focus:outline-none">
                    <select name="category" aria-label="Kategori aset" class="min-h-10 rounded-xl border border-zinc-200 bg-white px-3 text-xs"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->code }}" @selected(request('category') === $category->code)>{{ $category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$category->code }} · {{ $category->name }}</option>@endforeach</select>
                    <select name="status" aria-label="Status aset" class="min-h-10 rounded-xl border border-zinc-200 bg-white px-3 text-xs"><option value="">Semua status</option>@foreach ($statusLabels as $value => [$label, $classes])<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
                    <div class="flex gap-2 sm:col-span-3 sm:justify-end"><button class="min-h-10 rounded-xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white">Terapkan filter</button><a href="{{ route('school.assets.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold">Reset</a></div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="bg-zinc-50 text-[10px] font-semibold uppercase tracking-wide text-zinc-500"><tr><th class="px-5 py-3">Identitas aset</th><th class="px-4 py-3">Klasifikasi</th><th class="px-4 py-3">Lokasi / tahun</th><th class="px-4 py-3">Kondisi</th><th class="px-4 py-3">Status / kepemilikan</th><th class="px-4 py-3">Dokumen</th><th class="px-5 py-3">Aksi</th></tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($assets as $asset)
                        @php
                            $assetStatus = $statusLabels[$asset->status] ?? ['Belum diketahui', 'border-zinc-200 bg-zinc-50 text-zinc-700'];
                            $ownership = $ownershipLabels[$asset->acquisition->ownership_status] ?? 'Status belum diketahui';
                        @endphp
                        <tr class="align-top hover:bg-zinc-50/60">
                            <td class="px-5 py-4"><div class="text-xs font-bold">{{ $asset->name }}</div><div class="mt-1 text-[11px] text-zinc-500">{{ $asset->asset_code }}</div>@if($asset->serial_number)<div class="mt-1 text-[10px] text-zinc-400">Seri: {{ $asset->serial_number }}</div>@endif
@if($asset->brand_model)<div class="mt-1 text-[10px] text-zinc-400">{{ $asset->brand_model }}</div>@endif</td>
                            <td class="px-4 py-4"><div class="text-xs font-semibold">{{ $asset->type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$asset->type->category->code }}</div><div class="mt-1 max-w-[220px] text-xs text-zinc-500">{{ $asset->type->category->name }}</div><div class="mt-1 text-xs">{{ $asset->type->name }}</div></td>
                            <td class="px-4 py-4 text-xs text-zinc-600">{{ $asset->location ?: 'Belum diisi' }}<div class="mt-1 text-[10px] text-zinc-400">Tahun {{ $asset->year_acquired ?: '—' }}</div></td>
                            <td class="px-4 py-4 text-xs">{{ ['baik' => 'Baik', 'rusak_ringan' => 'Rusak ringan', 'rusak_berat' => 'Rusak berat', 'tidak_diketahui' => 'Belum diketahui'][$asset->condition] ?? $asset->condition }}</td>
                            <td class="px-4 py-4"><span class="inline-flex rounded-lg border px-2 py-1.5 text-[10px] font-semibold {{ $assetStatus[1] }}">{{ $assetStatus[0] }}</span><div class="mt-2 max-w-[230px] text-[10px] leading-4 text-zinc-500">{{ $ownership }}</div>@if($asset->review_note)<p class="mt-2 max-w-[230px] text-[10px] leading-4 text-rose-700">{{ $asset->review_note }}</p>@endif</td>
                            <td class="px-4 py-4"><div class="space-y-2">@forelse($asset->acquisition->documents as $document)<a class="block max-w-[180px] break-words text-[10px] font-semibold text-zinc-700 underline decoration-zinc-300 underline-offset-4" href="{{ route('school.assets.documents.download', $document) }}">{{ $document->original_name }}</a>@empty<span class="text-[10px] text-rose-700">Belum ada dokumen</span>@endforelse</div></td>
                            <td class="px-5 py-4">
                                @if ($asset->status === 'approved' && $asset->pending_change_requests_count === 0)
                                    <details class="min-w-[170px]"><summary class="cursor-pointer text-xs font-semibold underline decoration-zinc-300 underline-offset-4">Ajukan perubahan</summary><form method="POST" action="{{ route('school.assets.changes.store', $asset) }}" class="mt-3 space-y-3 rounded-xl border border-zinc-200 p-3">@csrf
                                        <label class="block"><span class="mb-1 block text-[10px] font-semibold">Kondisi baru</span><select name="condition" required class="min-h-9 w-full rounded-lg border border-zinc-200 px-2 text-xs">@foreach(['baik'=>'Baik','rusak_ringan'=>'Rusak ringan','rusak_berat'=>'Rusak berat','tidak_diketahui'=>'Belum diketahui'] as $value=>$label)<option value="{{ $value }}" @selected($asset->condition === $value)>{{ $label }}</option>@endforeach</select></label>
                                        <label class="block"><span class="mb-1 block text-[10px] font-semibold">Lokasi</span><input name="location" value="{{ $asset->location }}" maxlength="255" class="min-h-9 w-full rounded-lg border border-zinc-200 px-2 text-xs"></label>
                                        <label class="block"><span class="mb-1 block text-[10px] font-semibold">Catatan</span><textarea name="notes" rows="2" maxlength="3000" class="w-full rounded-lg border border-zinc-200 px-2 py-2 text-xs">{{ $asset->notes }}</textarea></label>
                                        <label class="block"><span class="mb-1 block text-[10px] font-semibold">Alasan perubahan *</span><textarea name="request_note" rows="2" required maxlength="2000" class="w-full rounded-lg border border-zinc-200 px-2 py-2 text-xs"></textarea></label>
                                        <button class="min-h-9 w-full rounded-lg bg-zinc-950 px-3 py-2 text-[10px] font-semibold text-white">Kirim pengajuan</button>
                                    </form></details>
                                @elseif ($asset->pending_change_requests_count > 0)
                                    <span class="text-[10px] text-amber-700">Perubahan menunggu pemeriksaan</span>
                                @else
                                    <span class="text-[10px] text-zinc-400">Tidak tersedia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center"><div class="text-sm font-semibold text-zinc-800">Belum ada aset yang cocok</div><p class="mt-1 text-xs text-zinc-500">Catat aset pertama atau sesuaikan filter.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-zinc-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"><p class="text-xs text-zinc-500">Menampilkan {{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }} dari {{ $assets->total() }} aset.</p><div>{{ $assets->links() }}</div></div>
    </section>
</div>

<template id="asset-item-template">
    <article data-asset-item class="rounded-xl border border-zinc-200 p-4">
        <div class="mb-4 flex items-center justify-between gap-3"><h3 class="text-xs font-bold">Unit aset <span data-item-number></span></h3><button type="button" data-remove-item class="text-xs font-semibold text-rose-700">Hapus unit</button></div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <label class="block"><span class="mb-1 block text-xs font-semibold">Kode inventaris *</span><input name="items[__INDEX__][asset_code]" required maxlength="80" pattern="[A-Za-z0-9][A-Za-z0-9._/-]*" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm uppercase"></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold">Nama barang *</span><input name="items[__INDEX__][name]" required maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold">Jenis / klasifikasi *</span><select name="items[__INDEX__][asset_type_id]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm"><option value="">Pilih jenis barang</option>@foreach($assetTypes as $type)<option value="{{ $type->id }}">{{ $type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$type->category->code }} · {{ $type->category->name }} — {{ $type->name }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold">Merek / model</span><input name="items[__INDEX__][brand_model]" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold">Nomor seri</span><input name="items[__INDEX__][serial_number]" maxlength="160" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold">Lokasi</span><input name="items[__INDEX__][location]" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold">Kondisi *</span><select name="items[__INDEX__][condition]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach(['baik'=>'Baik','rusak_ringan'=>'Rusak ringan','rusak_berat'=>'Rusak berat','tidak_diketahui'=>'Belum diketahui'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold">Tahun perolehan</span><input type="number" name="items[__INDEX__][year_acquired]" min="1900" max="{{ now()->year + 1 }}" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
            <label class="block sm:col-span-2"><span class="mb-1 block text-xs font-semibold">Catatan</span><textarea name="items[__INDEX__][notes]" rows="2" maxlength="3000" class="w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm"></textarea></label>
        </div>
    </article>
</template>
<template id="asset-document-template">
    <article data-document-item class="grid gap-3 rounded-xl border border-zinc-200 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end">
        <label class="block"><span class="mb-1 block text-xs font-semibold">Jenis dokumen *</span><select name="documents[__INDEX__][kind]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach(['bast'=>'BAST','bapp'=>'BAPP','bukti_pembelian'=>'Bukti pembelian','kontrak_pesanan'=>'Kontrak / pesanan','dokumen_hibah'=>'Dokumen hibah','dokumen_bantuan'=>'Dokumen bantuan','dokumen_pendukung'=>'Dokumen pendukung lain'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label class="block"><span class="mb-1 block text-xs font-semibold">Berkas maks. 10 MB *</span><input type="file" name="documents[__INDEX__][file]" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 py-2 text-xs"></label>
        <button type="button" data-remove-document class="min-h-10 rounded-xl px-3 py-2 text-xs font-semibold text-rose-700">Hapus</button>
    </article>
</template>
@endsection

@push('scripts')
<script>
(() => {
    const items = document.getElementById('asset-items');
    const docs = document.getElementById('asset-documents');
    const itemTemplate = document.getElementById('asset-item-template');
    const docTemplate = document.getElementById('asset-document-template');
    const reindex = (container, selector, prefix) => {
        [...container.querySelectorAll(selector)].forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(input => input.name = input.name.replace(new RegExp(prefix + '\\[\\d+\\]'), prefix + '[' + index + ']'));
            const number = row.querySelector('[data-item-number]');
            if (number) number.textContent = String(index + 1);
            const remove = row.querySelector('[data-remove-item], [data-remove-document]');
            if (remove) remove.disabled = index === 0;
        });
    };
    document.getElementById('add-asset-row')?.addEventListener('click', () => {
        if (items.querySelectorAll('[data-asset-item]').length >= 50) return alert('Maksimal 50 unit per kelompok.');
        items.append(itemTemplate.content.cloneNode(true));
        reindex(items, '[data-asset-item]', 'items');
    });
    document.getElementById('add-document-row')?.addEventListener('click', () => {
        if (docs.querySelectorAll('[data-document-item]').length >= 10) return alert('Maksimal 10 dokumen per kelompok.');
        docs.append(docTemplate.content.cloneNode(true));
        reindex(docs, '[data-document-item]', 'documents');
    });
    document.addEventListener('click', event => {
        const removeItem = event.target.closest('[data-remove-item]');
        const removeDoc = event.target.closest('[data-remove-document]');
        if (removeItem && items.contains(removeItem) && items.querySelectorAll('[data-asset-item]').length > 1) {
            removeItem.closest('[data-asset-item]').remove();
            reindex(items, '[data-asset-item]', 'items');
        }
        if (removeDoc && docs.contains(removeDoc) && docs.querySelectorAll('[data-document-item]').length > 1) {
            removeDoc.closest('[data-document-item]').remove();
            reindex(docs, '[data-document-item]', 'documents');
        }
    });
    reindex(items, '[data-asset-item]', 'items');
    reindex(docs, '[data-document-item]', 'documents');
})();
</script>
@endpush
