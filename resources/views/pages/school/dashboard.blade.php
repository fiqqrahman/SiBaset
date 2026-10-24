@extends('layouts.school')

@section('title', 'Dashboard Operator Sekolah')
@section('page-heading', 'Dashboard')
@section('content')
@php
    $schoolName = $school?->nama ?? 'Sekolah belum terhubung';
    $schoolNpsn = $school?->npsn ?? '—';
    $summary = [
        ['label' => 'Total aset sekolah', 'value' => '248', 'meta' => 'Seluruh kategori aset', 'icon' => 'box', 'tone' => 'zinc'],
        ['label' => 'Menunggu verifikasi', 'value' => '06', 'meta' => 'Sedang ditinjau dinas', 'icon' => 'clock', 'tone' => 'amber'],
        ['label' => 'Disetujui', 'value' => '32', 'meta' => 'Pengajuan disetujui', 'icon' => 'check', 'tone' => 'emerald'],
        ['label' => 'Perlu perbaikan', 'value' => '02', 'meta' => 'Perlu tindak lanjut', 'icon' => 'alert', 'tone' => 'rose'],
    ];
    $activities = [
        ['code' => 'AST-0248', 'title' => 'Pembaruan kondisi meja siswa', 'type' => 'Data aset', 'date' => '23 Okt 2026, 09.42', 'status' => 'Menunggu Verifikasi', 'tone' => 'amber'],
        ['code' => 'PROFIL-003', 'title' => 'Perubahan nomor kontak sekolah', 'type' => 'Profil sekolah', 'date' => '22 Okt 2026, 14.16', 'status' => 'Perlu Perbaikan', 'tone' => 'rose'],
        ['code' => 'AST-0219', 'title' => 'Pembaruan data komputer laboratorium', 'type' => 'Data aset', 'date' => '18 Okt 2026, 10.30', 'status' => 'Disetujui', 'tone' => 'emerald'],
    ];
@endphp

<div class="mx-auto max-w-[1440px] space-y-6">
    @if (auth()->user()->password_change_recommended && !session('school_password_reminder_dismissed'))
        <section role="status" class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div>
                <h2 class="text-sm font-bold text-amber-950">Kata sandi akun perlu diperbarui</h2>
                <p class="mt-1 text-xs leading-5 text-amber-900/80">Ganti kata sandi awal atau kata sandi yang telah diatur ulang agar akun operator tetap aman. Pengingat ini tidak menghalangi penggunaan dashboard.</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('school.profile.edit') }}#ubah-kata-sandi" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800">Ganti kata sandi</a>
                <form action="{{ route('school.password.reminder.dismiss') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2 text-xs font-semibold text-amber-950 hover:bg-amber-100">Nanti saja</button>
                </form>
            </div>
        </section>
    @endif

    <section class="relative overflow-hidden rounded-2xl bg-zinc-950 px-5 py-6 text-white sm:px-7 sm:py-8">
        <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full border border-white/10"></div>
        <div class="pointer-events-none absolute -right-2 -top-10 h-44 w-44 rounded-full border border-white/10"></div>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[10px] font-semibold text-zinc-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Portal operator sekolah</div>
                <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-3xl">Selamat datang, {{ auth()->user()->name ?? 'Operator Sekolah' }}</h1>
                <p class="mt-2 max-w-xl text-xs leading-6 text-zinc-300 sm:text-sm">Kelola data aset dan profil sekolah dengan lebih mudah. Setiap perubahan akan melalui proses verifikasi Dinas Pendidikan.</p>
            </div>
            <a href="{{ route('school.assets.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-white px-4 py-3 text-xs font-bold text-zinc-900 transition hover:bg-zinc-100">Lihat data aset <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
        </div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-4 sm:p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-700"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-6 w-6" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg></div>
            <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h2 class="text-sm font-bold">{{ $schoolName }}</h2><span class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[9px] font-semibold text-emerald-700">Sekolah terdaftar</span></div><div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-zinc-500"><span>NPSN: {{ $schoolNpsn }}</span><span>{{ $school?->kecamatan ?? 'Wilayah belum tersedia' }}, {{ $school?->kabupaten ?? 'Kota Palangka Raya' }}</span></div></div>
            <a href="{{ route('school.profile.edit') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 px-3 py-2.5 text-[11px] font-semibold text-zinc-700 hover:bg-zinc-50"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="m15 5 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z"/></svg>Lihat profil</a>
        </div>
    </section>

    <section>
        <div class="mb-3 flex items-end justify-between gap-3"><div><h2 class="text-sm font-bold tracking-tight">Ringkasan data</h2><p class="mt-1 text-[11px] text-zinc-500">Ikhtisar aset dan status pengajuan sekolah.</p></div><span class="text-[10px] text-zinc-400">Ringkasan aset masih pratinjau</span></div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summary as $item)
                @php $iconClasses = ['zinc' => 'bg-zinc-100 text-zinc-700', 'amber' => 'bg-amber-50 text-amber-700', 'emerald' => 'bg-emerald-50 text-emerald-700', 'rose' => 'bg-rose-50 text-rose-700']; @endphp
                <article class="rounded-2xl border border-zinc-200 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div class="text-[11px] font-medium text-zinc-500">{{ $item['label'] }}</div><div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $iconClasses[$item['tone']] }}">
                        @if ($item['icon'] === 'box')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/></svg>
                        @elseif ($item['icon'] === 'clock')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        @elseif ($item['icon'] === 'check')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg>
                        @endif
                    </div></div>
                    <div class="mt-4 flex items-end justify-between gap-2"><div class="text-3xl font-bold tracking-tight">{{ $item['value'] }}</div><div class="pb-1 text-right text-[10px] text-zinc-400">{{ $item['meta'] }}</div></div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(300px,0.9fr)]">
        <div id="pengajuan-terbaru" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="flex flex-col gap-2 border-b border-zinc-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-sm font-bold">Aktivitas pengajuan terbaru</h2><p class="mt-1 text-[11px] text-zinc-500">Pantau perkembangan pengajuan data sekolah.</p></div><a href="{{ route('school.assets.index') }}" class="text-[11px] font-semibold text-zinc-700 hover:text-black">Lihat semua <span aria-hidden="true">→</span></a></div>
            <div class="school-scrollbar overflow-x-auto"><table class="w-full min-w-[590px] text-left">
                <thead class="bg-zinc-50/80 text-[10px] font-semibold text-zinc-500"><tr><th class="px-5 py-3">Pengajuan</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-zinc-100">
                @foreach ($activities as $activity)
                    @php $statusClasses = ['amber' => 'border-amber-200 bg-amber-50 text-amber-700', 'rose' => 'border-rose-200 bg-rose-50 text-rose-700', 'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700']; @endphp
                    <tr class="transition hover:bg-zinc-50/70"><td class="px-5 py-4"><div class="text-[11px] font-semibold text-zinc-800">{{ $activity['title'] }}</div><div class="mt-1 text-[10px] text-zinc-400">{{ $activity['code'] }} · {{ $activity['type'] }}</div></td><td class="px-4 py-4 text-[10px] text-zinc-500">{{ $activity['date'] }}</td><td class="px-4 py-4"><span class="inline-flex whitespace-nowrap rounded-lg border px-2 py-1.5 text-[9px] font-semibold {{ $statusClasses[$activity['tone']] }}">{{ $activity['status'] }}</span></td></tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="border-t border-zinc-100 bg-zinc-50/60 px-5 py-3 text-[10px] text-zinc-400">Contoh tampilan · data belum diambil dari database.</div>
        </div>

        <div class="space-y-5">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                <div class="flex items-center justify-between"><h2 class="text-sm font-bold">Aksi cepat</h2><span class="text-[10px] text-zinc-400">Pintasan</span></div>
                <div class="mt-4 space-y-2">
                    <a href="{{ route('school.assets.index') }}" class="group flex items-center gap-3 rounded-xl border border-zinc-200 p-3 transition hover:border-zinc-300 hover:bg-zinc-50"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/></svg></span><span class="min-w-0 flex-1"><span class="block text-[11px] font-semibold">Periksa data aset</span><span class="mt-1 block text-[10px] text-zinc-500">Lihat daftar aset yang terdaftar</span></span><span class="text-zinc-400 transition group-hover:translate-x-0.5">→</span></a>
                    <a href="{{ route('school.profile.edit') }}" class="group flex items-center gap-3 rounded-xl border border-zinc-200 p-3 transition hover:border-zinc-300 hover:bg-zinc-50"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg></span><span class="min-w-0 flex-1"><span class="block text-[11px] font-semibold">Perbarui profil sekolah</span><span class="mt-1 block text-[10px] text-zinc-500">Ajukan perubahan informasi sekolah</span></span><span class="text-zinc-400 transition group-hover:translate-x-0.5">→</span></a>
                </div>
            </section>
            <section class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5"><div class="flex items-start gap-3"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 ring-1 ring-amber-200"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg></div><div><h2 class="text-xs font-bold text-amber-950">Perlu diketahui</h2><p class="mt-1.5 text-[11px] leading-5 text-amber-900/80">Setiap perubahan harus diperiksa sebelum dikirim. Pengajuan yang sudah tersimpan akan terkunci sampai Dinas Pendidikan memberikan keputusan.</p><a href="#pengajuan-terbaru" class="mt-3 inline-flex items-center gap-1 text-[10px] font-bold text-amber-950 hover:underline">Pantau pengajuan <span aria-hidden="true">→</span></a></div></div></section>
        </div>
    </section>
    <p class="text-center text-[10px] text-zinc-400">Pratinjau antarmuka · Angka dan aktivitas di halaman ini merupakan data contoh.</p>
</div>
@endsection

