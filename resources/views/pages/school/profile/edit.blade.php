@extends('layouts.school')

@section('title', 'Profil Sekolah')
@section('page-heading', 'Profil Sekolah')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div><div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">Identitas satuan pendidikan</div><h1 class="mt-2 text-2xl font-bold tracking-tight">Profil Sekolah</h1><p class="mt-2 text-xs leading-5 text-zinc-500">Periksa informasi sekolah dan ajukan koreksi apabila ada perubahan.</p></div>
    <div class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 ring-1 ring-amber-200"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg></div><div><div class="text-xs font-bold text-amber-950">Perubahan perlu diverifikasi</div><p class="mt-1 text-[11px] leading-5 text-amber-900/80">Data profil resmi tidak langsung berubah saat operator mengajukan pembaruan. Dinas Pendidikan akan memeriksa pengajuan terlebih dahulu.</p></div></div>
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-bold">Informasi sekolah</h2><p class="mt-1 text-[11px] text-zinc-500">Form pratinjau. Perubahan belum disimpan ke server.</p></div>
        <form id="school-profile-demo-form" class="space-y-5 p-5 sm:p-6">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">Nama sekolah</span><input name="school_name" value="SMP Negeri Contoh Palangka Raya" class="h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400" required><span class="mt-1.5 block text-[10px] text-zinc-400">Nama sesuai data resmi sekolah.</span></label>
                <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">NPSN</span><input name="npsn" value="3020XXXX" readonly class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-xs text-zinc-500 outline-none"><span class="mt-1.5 block text-[10px] text-zinc-400">Nomor identitas sekolah ditampilkan sebagai contoh.</span></label>
                <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">Email sekolah</span><input name="email" type="email" value="sekolah@example.sch.id" class="h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400" required></label>
                <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">Nomor telepon</span><input name="phone" value="(0536) 000000" class="h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400"></label>
                <label class="block sm:col-span-2"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">Alamat sekolah</span><textarea name="address" rows="3" class="w-full resize-y rounded-xl border border-zinc-200 px-3 py-3 text-xs leading-5 outline-none focus:border-zinc-400">Jalan Contoh No. 10, Kota Palangka Raya, Kalimantan Tengah</textarea></label>
            </div>
            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 sm:flex-row sm:items-center sm:justify-between"><div><div class="text-[11px] font-semibold text-zinc-800">Status profil saat ini</div><div class="mt-1 text-[10px] text-zinc-500">Belum ada pengajuan perubahan pada pratinjau ini.</div></div><span class="w-fit rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-zinc-500">Tidak ada pengajuan</span></div>
            <div id="school-profile-demo-notice" hidden class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-[11px] leading-5 text-emerald-800" role="status"></div>
            <div class="flex flex-col-reverse gap-2 border-t border-zinc-100 pt-5 sm:flex-row sm:justify-end"><button type="reset" class="rounded-xl border border-zinc-200 px-4 py-3 text-[11px] font-semibold text-zinc-700 hover:bg-zinc-50">Reset contoh</button><button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-zinc-900 px-4 py-3 text-[11px] font-semibold text-white hover:bg-zinc-800">Pratinjau pengajuan <span aria-hidden="true">→</span></button></div>
        </form>
    </section>
    <p class="text-center text-[10px] text-zinc-400">Seluruh nilai pada formulir merupakan contoh dan tidak mewakili profil sekolah yang sebenarnya.</p>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('school-profile-demo-form');
    const notice = document.getElementById('school-profile-demo-notice');
    form?.addEventListener('submit', event => {
        event.preventDefault();
        notice.hidden = false;
        notice.textContent = 'Ini hanya pratinjau tampilan. Pengajuan profil belum dikirim atau disimpan karena backend belum diimplementasikan.';
        notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
    form?.addEventListener('reset', () => { if (notice) notice.hidden = true; });
})();
</script>
@endpush
