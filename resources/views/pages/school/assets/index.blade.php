@extends('layouts.school')

@section('title', 'Data Aset Sekolah')
@section('page-heading', 'Data Aset')
@section('content')
<div class="mx-auto max-w-[1440px] space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">Inventaris sekolah</div><h1 class="mt-2 text-2xl font-bold tracking-tight">Data Aset</h1><p class="mt-2 text-xs leading-5 text-zinc-500">Periksa informasi aset dan ajukan pembaruan untuk diverifikasi dinas.</p></div><span class="w-fit rounded-lg border border-zinc-200 bg-white px-3 py-2 text-[10px] text-zinc-500">Pratinjau tampilan · Data contoh</span></div>
    <div class="grid gap-3 sm:grid-cols-3"><div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-[11px] text-zinc-500">Total aset</div><div class="mt-2 text-2xl font-bold">248</div></div><div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-[11px] text-zinc-500">Pengajuan aktif</div><div class="mt-2 text-2xl font-bold">6</div></div><div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-[11px] text-zinc-500">Perlu perbaikan</div><div class="mt-2 text-2xl font-bold text-rose-700">2</div></div></div>
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="flex flex-col gap-3 border-b border-zinc-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"><div><h2 class="text-sm font-bold">Daftar aset sekolah</h2><p class="mt-1 text-[11px] text-zinc-500">Tabel contoh untuk pratinjau tata letak.</p></div><label class="relative block w-full sm:max-w-xs"><span class="sr-only">Cari aset</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input id="asset-demo-search" type="search" placeholder="Cari nama atau kode aset..." class="h-10 w-full rounded-xl border border-zinc-200 pl-9 pr-3 text-xs outline-none focus:border-zinc-400"></label></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left"><thead class="bg-zinc-50 text-[10px] font-semibold text-zinc-500"><tr><th class="px-5 py-3">Kode aset</th><th class="px-4 py-3">Nama aset</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Kondisi</th><th class="px-4 py-3">Status pengajuan</th><th class="px-5 py-3">Aksi</th></tr></thead>
            <tbody id="asset-demo-rows" class="divide-y divide-zinc-100">
                <tr data-search="ast-0248 meja siswa ruang kelas"><td class="px-5 py-4 text-[10px] text-zinc-500">AST-0248</td><td class="px-4 py-4"><div class="text-[11px] font-semibold">Meja siswa</div><div class="mt-1 text-[10px] text-zinc-400">Ruang kelas 7A</div></td><td class="px-4 py-4 text-[10px] text-zinc-600">Peralatan</td><td class="px-4 py-4 text-[10px]">Baik</td><td class="px-4 py-4"><span class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-1.5 text-[9px] font-semibold text-amber-700">Menunggu Verifikasi</span></td><td class="px-5 py-4"><button type="button" class="asset-demo-detail text-[10px] font-bold text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-black" data-asset="Meja siswa · AST-0248">Lihat</button></td></tr>
                <tr data-search="ast-0219 komputer laboratorium tik"><td class="px-5 py-4 text-[10px] text-zinc-500">AST-0219</td><td class="px-4 py-4"><div class="text-[11px] font-semibold">Komputer laboratorium</div><div class="mt-1 text-[10px] text-zinc-400">Ruang lab komputer</div></td><td class="px-4 py-4 text-[10px] text-zinc-600">Elektronik</td><td class="px-4 py-4 text-[10px]">Baik</td><td class="px-4 py-4"><span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1.5 text-[9px] font-semibold text-emerald-700">Disetujui</span></td><td class="px-5 py-4"><button type="button" class="asset-demo-detail text-[10px] font-bold text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-black" data-asset="Komputer laboratorium · AST-0219">Lihat</button></td></tr>
                <tr data-search="ast-0182 lemari arsip ruang guru"><td class="px-5 py-4 text-[10px] text-zinc-500">AST-0182</td><td class="px-4 py-4"><div class="text-[11px] font-semibold">Lemari arsip</div><div class="mt-1 text-[10px] text-zinc-400">Ruang guru</div></td><td class="px-4 py-4 text-[10px] text-zinc-600">Perabot</td><td class="px-4 py-4 text-[10px]">Rusak ringan</td><td class="px-4 py-4"><span class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-1.5 text-[9px] font-semibold text-rose-700">Perlu Perbaikan</span></td><td class="px-5 py-4"><button type="button" class="asset-demo-detail text-[10px] font-bold text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-black" data-asset="Lemari arsip · AST-0182">Lihat</button></td></tr>
                <tr data-search="ast-0164 proyektor ruang multimedia"><td class="px-5 py-4 text-[10px] text-zinc-500">AST-0164</td><td class="px-4 py-4"><div class="text-[11px] font-semibold">Proyektor</div><div class="mt-1 text-[10px] text-zinc-400">Ruang multimedia</div></td><td class="px-4 py-4 text-[10px] text-zinc-600">Elektronik</td><td class="px-4 py-4 text-[10px]">Baik</td><td class="px-4 py-4 text-[10px] text-zinc-400">Tidak ada pengajuan</td><td class="px-5 py-4"><button type="button" class="asset-demo-detail text-[10px] font-bold text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-black" data-asset="Proyektor · AST-0164">Lihat</button></td></tr>
            </tbody></table></div>
        <div id="asset-demo-empty" hidden class="p-10 text-center text-xs text-zinc-500">Aset tidak ditemukan.</div>
        <div class="border-t border-zinc-100 bg-zinc-50/60 px-5 py-3 text-[10px] text-zinc-400">Pratinjau UI saja. Form edit dan pengajuan belum dihubungkan ke backend.</div>
    </section>
</div>
<div id="asset-demo-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-zinc-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="asset-demo-modal-title">
    <section class="w-full max-w-md rounded-2xl bg-white shadow-2xl"><div class="flex items-start justify-between border-b border-zinc-100 p-5"><div><div class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Detail aset demo</div><h2 id="asset-demo-modal-title" class="mt-1 text-base font-bold">Detail aset</h2></div><button type="button" data-close-asset-modal class="flex h-8 w-8 items-center justify-center rounded-lg text-lg text-zinc-500 hover:bg-zinc-100" aria-label="Tutup">×</button></div><div class="p-5"><p id="asset-demo-modal-copy" class="text-xs text-zinc-600"></p><div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] leading-5 text-amber-900">Pratinjau ini belum membaca data aset asli. Form edit dan pengajuan dibuat pada tahap backend berikutnya.</div></div><div class="flex justify-end border-t border-zinc-100 p-4"><button type="button" data-close-asset-modal class="rounded-xl bg-zinc-900 px-4 py-2.5 text-[11px] font-semibold text-white">Tutup</button></div></section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const search = document.getElementById('asset-demo-search');
    const rows = [...document.querySelectorAll('#asset-demo-rows tr')];
    const empty = document.getElementById('asset-demo-empty');
    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach(row => {
            const match = (row.dataset.search || '').includes(query);
            row.hidden = !match;
            if (match) visible++;
        });
        if (empty) empty.hidden = visible > 0;
    });
    const modal = document.getElementById('asset-demo-modal');
    const copy = document.getElementById('asset-demo-modal-copy');
    document.querySelectorAll('.asset-demo-detail').forEach(button => button.addEventListener('click', () => {
        copy.textContent = button.dataset.asset || 'Detail aset';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }));
    const closeModal = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
    document.querySelectorAll('[data-close-asset-modal]').forEach(button => button.addEventListener('click', closeModal));
    modal?.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeModal(); });
})();
</script>
@endpush
