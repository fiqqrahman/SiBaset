@extends('layouts.school')

@section('title', 'Perbaiki Data Perolehan')
@section('page-heading', 'Perbaikan Data Aset')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('school.assets.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-600 hover:text-black">← Kembali ke Data Aset</a>

    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><div class="font-bold">Perbaikan belum dapat dikirim</div><ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <header>
        <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">Perolehan #{{ $acquisition->id }}</div>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">Perbaiki data aset dan dokumen</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-500">Unit yang sudah tercatat akan diperbarui pada record yang sama. Dokumen lama tidak dihapus saat Ente mengirimkan tambahan berkas.</p>
    </header>

    <section class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 sm:p-5">
        <h2 class="text-sm font-bold text-rose-950">Catatan pemeriksaan Dinas</h2>
        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-rose-900">{{ $acquisition->review_note ?: 'Data atau dokumen perlu dilengkapi.' }}</p>
        <div class="mt-3 text-xs text-rose-900">{{ $acquisition->assets->count() }} unit · NPSN {{ $school->npsn }} · {{ $school->nama }}</div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-4 sm:p-5">
        <h2 class="text-sm font-bold">Dokumen yang sudah terlampir</h2>
        <div class="mt-3 space-y-2">
            @forelse($acquisition->documents as $document)
                <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0"><div class="break-words text-xs font-semibold">{{ $document->original_name }}</div><div class="mt-1 text-[10px] text-zinc-500">{{ str_replace('_', ' ', $document->document_kind) }} · {{ number_format($document->size_bytes / 1024, 0, ',', '.') }} KB</div></div>
                    <a href="{{ route('school.assets.documents.download', $document) }}" class="text-xs font-semibold underline decoration-zinc-300 underline-offset-4">Unduh dokumen</a>
                </div>
            @empty
                <p class="text-xs text-rose-700">Belum ada dokumen tersimpan.</p>
            @endforelse
        </div>
    </section>

    <form method="POST" action="{{ route('school.assets.acquisitions.resubmit', $acquisition) }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-4 sm:p-6">
        @csrf
        @method('PUT')
        <section class="space-y-4">
            <h2 class="text-sm font-bold">A. Informasi perolehan</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Sumber dana *</span><select name="funding_source" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach(['apbd_kota'=>'APBD Kota','apbd_provinsi'=>'APBD Provinsi','apbn'=>'APBN / Pemerintah Pusat','bos_bosp'=>'BOS / BOSP','hibah'=>'Hibah','bantuan_pemerintah'=>'Bantuan Pemerintah','swadaya'=>'Swadaya Sekolah','lainnya'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}" @selected(old('funding_source', $acquisition->funding_source) === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Cara perolehan *</span><select name="acquisition_method" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach(['pembelian'=>'Pembelian','bantuan'=>'Bantuan','hibah'=>'Hibah','transfer'=>'Transfer / penyerahan','donasi'=>'Donasi','lainnya'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}" @selected(old('acquisition_method', $acquisition->acquisition_method) === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Pemilik menurut dokumen *</span><select name="owner_entity" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach(['belum_ditentukan'=>'Belum ditentukan / perlu verifikasi','pemkot'=>'Pemerintah Kota Palangka Raya','pemprov'=>'Pemerintah Provinsi','pemerintah_pusat'=>'Pemerintah Pusat','yayasan'=>'Yayasan','pihak_lain'=>'Pihak lain'] as $value=>$label)<option value="{{ $value }}" @selected(old('owner_entity', $acquisition->owner_entity) === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Pemberi bantuan / penyedia</span><input name="provider_name" value="{{ old('provider_name', $acquisition->provider_name) }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Nomor referensi</span><input name="document_reference" value="{{ old('document_reference', $acquisition->document_reference) }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Tanggal perolehan</span><input type="date" name="acquired_at" value="{{ old('acquired_at', $acquisition->acquired_at?->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold">Nilai total (Rp)</span><input type="number" name="total_value" value="{{ old('total_value', $acquisition->total_value) }}" min="0" step="0.01" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                <label class="block sm:col-span-2"><span class="mb-1.5 block text-xs font-semibold">Catatan perolehan</span><textarea name="acquisition_notes" rows="2" maxlength="5000" class="w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">{{ old('acquisition_notes', $acquisition->notes) }}</textarea></label>
            </div>
        </section>

        <section class="space-y-4 border-t border-zinc-100 pt-5">
            <div><h2 class="text-sm font-bold">B. Periksa dan perbaiki unit aset</h2><p class="mt-1 text-xs text-zinc-500">ID internal unit tetap dipertahankan. Semua unit yang ada pada kelompok ini harus dikirim kembali.</p></div>
            <div class="space-y-4">
                @foreach($acquisition->assets as $index => $asset)
                    <article class="rounded-xl border border-zinc-200 p-4">
                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ old('items.'.$index.'.id', $asset->id) }}">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><h3 class="text-xs font-bold">Unit {{ $index + 1 }} · {{ $asset->asset_code }}</h3><span class="text-[10px] text-zinc-500">ID #{{ $asset->id }}</span></div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Kode inventaris *</span><input name="items[{{ $index }}][asset_code]" value="{{ old('items.'.$index.'.asset_code', $asset->asset_code) }}" required maxlength="80" pattern="[A-Za-z0-9][A-Za-z0-9._/-]*" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm uppercase"></label>
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Nama barang *</span><input name="items[{{ $index }}][name]" value="{{ old('items.'.$index.'.name', $asset->name) }}" required maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Jenis / klasifikasi *</span><select name="items[{{ $index }}][asset_type_id]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach($assetTypes as $type)<option value="{{ $type->id }}" @selected((string) old('items.'.$index.'.asset_type_id', $asset->asset_type_id) === (string) $type->id)>{{ $type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$type->category->code }} · {{ $type->category->name }} — {{ $type->name }}{{ $type->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></label>
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Merek / model</span><input name="items[{{ $index }}][brand_model]" value="{{ old('items.'.$index.'.brand_model', $asset->brand_model) }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Nomor seri</span><input name="items[{{ $index }}][serial_number]" value="{{ old('items.'.$index.'.serial_number', $asset->serial_number) }}" maxlength="160" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Lokasi</span><input name="items[{{ $index }}][location]" value="{{ old('items.'.$index.'.location', $asset->location) }}" maxlength="255" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Kondisi *</span><select name="items[{{ $index }}][condition]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach(['baik'=>'Baik','rusak_ringan'=>'Rusak ringan','rusak_berat'=>'Rusak berat','tidak_diketahui'=>'Belum diketahui'] as $value=>$label)<option value="{{ $value }}" @selected(old('items.'.$index.'.condition', $asset->condition) === $value)>{{ $label }}</option>@endforeach</select></label>
                            <label class="block"><span class="mb-1 block text-xs font-semibold">Tahun perolehan</span><input type="number" name="items[{{ $index }}][year_acquired]" value="{{ old('items.'.$index.'.year_acquired', $asset->year_acquired) }}" min="1900" max="{{ now()->year + 1 }}" class="min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm"></label>
                            <label class="block sm:col-span-2"><span class="mb-1 block text-xs font-semibold">Catatan unit</span><textarea name="items[{{ $index }}][notes]" rows="2" maxlength="3000" class="w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">{{ old('items.'.$index.'.notes', $asset->notes) }}</textarea></label>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="space-y-3 border-t border-zinc-100 pt-5">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-sm font-bold">C. Tambahkan dokumen (opsional)</h2><p class="mt-1 text-xs text-zinc-500">Dokumen lama tetap disimpan. Berkas baru ditambahkan sebagai jejak pendukung.</p></div><button type="button" id="add-revision-document" class="min-h-10 rounded-xl border border-zinc-300 px-3 py-2 text-xs font-semibold">+ Tambah dokumen</button></div>
            <div id="revision-documents" class="space-y-3"></div>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs leading-5 text-zinc-500">Setelah dikirim, aset kembali menunggu pemeriksaan Dinas.</p><button class="min-h-11 rounded-xl bg-zinc-950 px-5 py-3 text-xs font-semibold text-white hover:bg-zinc-800">Kirim perbaikan</button></div>
    </form>
</div>

<template id="revision-document-template">
    <article data-revision-document class="grid gap-3 rounded-xl border border-zinc-200 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end">
        <label class="block"><span class="mb-1 block text-xs font-semibold">Jenis dokumen *</span><select name="documents[__INDEX__][kind]" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">@foreach(['bast'=>'BAST','bapp'=>'BAPP','bukti_pembelian'=>'Bukti pembelian','kontrak_pesanan'=>'Kontrak / pesanan','dokumen_hibah'=>'Dokumen hibah','dokumen_bantuan'=>'Dokumen bantuan','dokumen_pendukung'=>'Dokumen pendukung lain'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label class="block"><span class="mb-1 block text-xs font-semibold">Berkas maks. 10 MB *</span><input type="file" name="documents[__INDEX__][file]" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required class="min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs"></label>
        <button type="button" data-remove-revision-document class="min-h-10 rounded-xl px-3 py-2 text-xs font-semibold text-rose-700">Hapus</button>
    </article>
</template>
@endsection

@push('scripts')
<script>
(() => {
    const container = document.getElementById('revision-documents');
    const template = document.getElementById('revision-document-template');
    const add = document.getElementById('add-revision-document');
    if (!container || !template || !add) return;
    const reindex = () => [...container.querySelectorAll('[data-revision-document]')].forEach((row, index) => {
        row.querySelectorAll('[name]').forEach(input => input.name = input.name.replace(/documents\[\d+\]/, 'documents[' + index + ']'));
        const button = row.querySelector('[data-remove-revision-document]');
        if (button) button.disabled = index === 0;
    });
    add.addEventListener('click', () => {
        if (container.querySelectorAll('[data-revision-document]').length >= 10) return alert('Maksimal 10 dokumen.');
        container.append(template.content.cloneNode(true));
        reindex();
    });
    container.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-revision-document]');
        if (button && container.querySelectorAll('[data-revision-document]').length > 1) {
            button.closest('[data-revision-document]').remove();
            reindex();
        }
    });
})();
</script>
@endpush
