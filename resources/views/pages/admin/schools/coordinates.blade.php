@extends('layouts.app')

@section('title', 'Verifikasi Koordinat Sekolah')
@section('content')
<div class="min-h-screen bg-zinc-50">
    <header class="flex min-h-[68px] items-center justify-between gap-4 border-b border-zinc-200 bg-white px-4 sm:px-6">
        <div class="flex items-center gap-3"><a href="{{ route('admin.dashboard') }}" class="flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 text-zinc-700 hover:bg-zinc-50" aria-label="Kembali ke peta">←</a><div><div class="text-xs font-extrabold">SiBaset <span class="font-medium text-zinc-400">/ Administrator</span></div><div class="mt-1 text-[10px] text-zinc-500">Data sekolah / Verifikasi koordinat</div></div></div>
        <div class="flex flex-wrap justify-end gap-2"><a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-[11px] font-semibold text-zinc-700 hover:bg-zinc-50">Peta dashboard</a><a href="{{ route('admin.schools.import.index') }}" class="rounded-xl bg-zinc-900 px-3 py-2 text-[11px] font-semibold text-white hover:bg-zinc-700">Impor JSON</a></div>
    </header>
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div><div class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-400">Kualitas data geospasial</div><h1 class="mt-2 text-2xl font-bold tracking-tight">Verifikasi koordinat sekolah</h1><p class="mt-2 max-w-3xl text-xs leading-5 text-zinc-500">Koordinat resmi hanya dipublikasikan setelah admin memverifikasi bahwa titik berada di dalam poligon Kota Palangka Raya. Pengajuan operator tidak langsung mengubah titik resmi.</p></div>
        @if (session('status'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if (!($boundaryAvailable ?? false))<div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900"><strong>Batas wilayah belum tersedia.</strong> Koordinat belum bisa diverifikasi dan tidak akan ditampilkan sebagai titik resmi. Pastikan server dapat mengakses layanan BIG atau jalankan <code>php artisan sibaset:cache-city-boundary</code>.</div>@endif

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="border-b border-zinc-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-bold">Pengajuan koordinat dari operator</h2><p class="mt-1 text-[11px] text-zinc-500">Persetujuan memperbarui koordinat resmi; penolakan mempertahankan koordinat resmi sebelumnya.</p></div>
            <div class="divide-y divide-zinc-100">
            @forelse ($pendingRequests as $coordinateRequest)
                <article class="grid gap-4 p-5 lg:grid-cols-[1fr_1.1fr] sm:px-6">
                    <div class="min-w-0"><div class="text-xs font-bold text-zinc-900">{{ $coordinateRequest->school?->nama ?? 'Sekolah tidak tersedia' }}</div><div class="mt-1 text-[10px] text-zinc-500">NPSN {{ $coordinateRequest->school?->npsn ?? '—' }} · Diajukan {{ $coordinateRequest->created_at->format('d M Y H:i') }}</div><div class="mt-3 rounded-xl bg-zinc-50 p-3 text-[10px] leading-5 text-zinc-600"><div><span class="font-semibold">Operator:</span> {{ $coordinateRequest->submittedBy?->name ?? 'Akun dihapus' }}</div><div><span class="font-semibold">Koordinat usulan:</span> {{ $coordinateRequest->latitude }}, {{ $coordinateRequest->longitude }}</div><div><span class="font-semibold">Koordinat sebelumnya:</span> {{ $coordinateRequest->previous_latitude ?? '—' }}, {{ $coordinateRequest->previous_longitude ?? '—' }}</div>@if ($coordinateRequest->request_note)<div class="mt-1"><span class="font-semibold">Catatan:</span> {{ $coordinateRequest->request_note }}</div>@endif</div></div>
                    <div class="flex flex-col gap-2">
                        <form action="{{ route('admin.schools.coordinate-requests.approve', $coordinateRequest) }}" method="POST" class="space-y-2">@csrf<label class="block text-[10px] font-semibold text-zinc-600">Catatan persetujuan (opsional)<input name="review_note" maxlength="2000" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400"></label><button onclick="return confirm('Setujui titik ini sebagai koordinat resmi sekolah?')" class="rounded-xl bg-zinc-900 px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-zinc-700">Setujui koordinat</button></form>
                        <form action="{{ route('admin.schools.coordinate-requests.reject', $coordinateRequest) }}" method="POST" class="space-y-2">@csrf<label class="block text-[10px] font-semibold text-zinc-600">Alasan penolakan (wajib)<input name="review_note" maxlength="2000" required class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400"></label><button onclick="return confirm('Tolak usulan? Koordinat resmi tidak akan berubah.')" class="rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-[11px] font-semibold text-zinc-700 hover:bg-zinc-50">Tolak usulan</button></form>
                    </div>
                </article>
            @empty
                <div class="px-5 py-8 text-center"><div class="text-xs font-semibold text-zinc-700">Tidak ada pengajuan yang menunggu</div><p class="mt-1 text-[10px] text-zinc-500">Pengajuan baru dari operator akan muncul di sini.</p></div>
            @endforelse
            </div>
            <div class="border-t border-zinc-100 px-5 py-3">{{ $pendingRequests->links() }}</div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="border-b border-zinc-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-bold">Sekolah yang perlu pemeriksaan</h2><p class="mt-1 text-[11px] text-zinc-500">Masukkan koordinat yang sudah diperiksa secara manual. Tombol verifikasi hanya berhasil jika titik masuk poligon wilayah kota.</p></div>
            <div class="divide-y divide-zinc-100">
            @forelse ($schools as $school)
                <article class="grid gap-4 p-5 lg:grid-cols-[1fr_1.2fr] sm:px-6">
                    <div><div class="text-xs font-bold text-zinc-900">{{ $school->nama }}</div><div class="mt-1 text-[10px] text-zinc-500">NPSN {{ $school->npsn }} · {{ $school->bentuk_pendidikan }} · {{ $school->kecamatan ?? 'Kecamatan belum tersedia' }}</div><div class="mt-3 flex flex-wrap gap-2"><span class="rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 text-[9px] font-semibold text-zinc-600">{{ match($school->review_coordinate_status ?? $school->coordinate_status) {'missing' => 'Belum ada koordinat', 'pending' => 'Belum diverifikasi', 'outside_boundary' => 'Di luar wilayah', 'needs_review' => 'Perlu pemeriksaan', default => $school->coordinate_status} }}</span><span class="rounded-md bg-zinc-100 px-2 py-1 text-[9px] text-zinc-600">Saat ini: {{ $school->lintang ?? '—' }}, {{ $school->bujur ?? '—' }}</span></div></div>
                    <form action="{{ route('admin.schools.coordinates.verify', $school) }}" method="POST" class="grid gap-3 sm:grid-cols-2">@csrf
                        <label class="block text-[10px] font-semibold text-zinc-600">Lintang (latitude)<input name="latitude" type="number" step="any" min="-90" max="90" required value="{{ old('latitude', $school->lintang) }}" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400"></label>
                        <label class="block text-[10px] font-semibold text-zinc-600">Bujur (longitude)<input name="longitude" type="number" step="any" min="-180" max="180" required value="{{ old('longitude', $school->bujur) }}" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400"></label>
                        <label class="block text-[10px] font-semibold text-zinc-600 sm:col-span-2">Catatan admin (opsional)<input name="review_note" maxlength="2000" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400"></label>
                        <div class="sm:col-span-2"><button onclick="return confirm('Simpan koordinat sebagai titik resmi setelah validasi batas wilayah?')" class="rounded-xl bg-zinc-900 px-4 py-2.5 text-[11px] font-semibold text-white hover:bg-zinc-700">Verifikasi koordinat</button></div>
                    </form>
                </article>
            @empty
                <div class="px-5 py-8 text-center text-xs text-zinc-500">Semua sekolah dalam daftar ini sudah memiliki status terverifikasi.</div>
            @endforelse
            </div>
            <div class="border-t border-zinc-100 px-5 py-3">{{ $schools->links() }}</div>
        </section>
    </main>
</div>
@endsection
