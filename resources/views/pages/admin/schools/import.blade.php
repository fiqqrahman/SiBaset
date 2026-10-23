@extends('layouts.app')

@section('title', 'Impor Data Sekolah')
@section('content')
<div class="min-h-screen bg-zinc-50">
    <header class="flex min-h-[68px] items-center justify-between gap-4 border-b border-zinc-200 bg-white px-4 sm:px-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" class="flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 text-zinc-700 hover:bg-zinc-50" aria-label="Kembali ke peta">←</a>
            <div><div class="text-xs font-extrabold">SiBaset <span class="font-medium text-zinc-400">/ Administrator</span></div><div class="mt-1 text-[10px] text-zinc-500">Data sekolah / Impor JSON</div></div>
        </div>
        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-[11px] font-semibold text-zinc-700 hover:bg-zinc-50">Peta dashboard</a>
            <a href="{{ route('admin.schools.coordinates.index') }}" class="rounded-xl bg-zinc-900 px-3 py-2 text-[11px] font-semibold text-white hover:bg-zinc-700">Periksa koordinat</a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><div class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-400">Pengelolaan data awal</div><h1 class="mt-2 text-2xl font-bold tracking-tight">Impor data sekolah</h1><p class="mt-2 max-w-2xl text-xs leading-5 text-zinc-500">Unggah file JSON dari sumber sekolah. Hanya kolom yang disepakati yang disimpan; NPSN dipakai untuk mencocokkan sekolah yang sudah ada.</p></div>
            <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3"><div class="text-[10px] text-zinc-500">Sekolah di database</div><div class="mt-1 text-xl font-bold tabular-nums">{{ number_format($schoolCount, 0, ',', '.') }}</div></div>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-800">{{ session('status') }}</div>
        @endif
        @if (session('import_result'))
            <section class="rounded-2xl border border-emerald-200 bg-white p-5">
                <h2 class="text-sm font-bold">Impor berhasil</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-zinc-50 p-3"><div class="text-[10px] text-zinc-500">Total diproses</div><div class="mt-1 text-xl font-bold">{{ number_format(session('import_result.total'), 0, ',', '.') }}</div></div>
                    <div class="rounded-xl bg-zinc-50 p-3"><div class="text-[10px] text-zinc-500">Data baru</div><div class="mt-1 text-xl font-bold">{{ number_format(session('import_result.created'), 0, ',', '.') }}</div></div>
                    <div class="rounded-xl bg-zinc-50 p-3"><div class="text-[10px] text-zinc-500">Data diperbarui</div><div class="mt-1 text-xl font-bold">{{ number_format(session('import_result.updated'), 0, ',', '.') }}</div></div>
                    <div class="rounded-xl bg-zinc-50 p-3"><div class="text-[10px] text-zinc-500">Koordinat perlu ditinjau</div><div class="mt-1 text-xl font-bold">{{ number_format(session('import_result.coordinate_warnings'), 0, ',', '.') }}</div></div>
                </div>
            </section>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800">
                <div class="font-bold">Ada hal yang perlu diperiksa</div>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="border-b border-zinc-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-bold">Pilih file JSON</h2><p class="mt-1 text-[11px] text-zinc-500">Maksimal 10 MB. Format diharapkan berupa objek JSON dengan properti data berisi array sekolah.</p></div>
            <form action="{{ route('admin.schools.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4 p-5 sm:p-6">
                @csrf
                <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">File data sekolah (.json)</span><input type="file" name="file" accept=".json,application/json" required class="block w-full rounded-xl border border-zinc-200 bg-white px-3 py-3 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-[11px] file:font-semibold"></label>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-[10px] leading-5 text-zinc-600">Kolom yang diimpor: sekolah_id, npsn, nama, bentuk_pendidikan, status_sekolah, alamat_jalan, nama_dusun, kecamatan, kabupaten, provinsi, nomor_telepon, akreditasi, lintang, dan bujur. Kolom sumber lainnya diabaikan.</div>
                <button type="submit" class="rounded-xl bg-zinc-900 px-4 py-3 text-xs font-semibold text-white hover:bg-zinc-700">Periksa dan pratinjau JSON →</button>
            </form>
        </section>

        @php($preview = session('school_import_preview'))
        @if (is_array($preview))
            <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                <div class="flex flex-col gap-3 border-b border-zinc-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><h2 class="text-sm font-bold">Pratinjau: {{ $preview['file_name'] ?? 'schools.json' }}</h2><p class="mt-1 text-[11px] text-zinc-500">Tinjau hasil validasi sebelum menyimpan ke database.</p></div>
                    <span class="w-fit rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-[10px] font-semibold {{ empty($preview['errors']) ? 'text-zinc-700' : 'text-rose-700' }}">{{ empty($preview['errors']) ? 'Siap dikonfirmasi' : count($preview['errors']).' kesalahan' }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 lg:grid-cols-6 sm:px-6">
                    @foreach ([
                        'total' => 'Baris sumber',
                        'valid_rows' => 'Baris valid',
                        'inside' => 'Dalam wilayah',
                        'outside' => 'Di luar batas',
                        'missing' => 'Koordinat kosong',
                        'review' => 'Perlu pemeriksaan',
                    ] as $key => $label)
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3"><div class="text-[10px] text-zinc-500">{{ $label }}</div><div class="mt-1 text-xl font-bold tabular-nums">{{ number_format($preview['summary'][$key] ?? 0, 0, ',', '.') }}</div></div>
                    @endforeach
                </div>
                @if (empty($preview['boundary_available']))
                    <div class="mx-5 mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] leading-5 text-amber-900 sm:mx-6">Batas wilayah belum tersedia dari layanan BIG. Impor masih dapat menyimpan identitas sekolah, tetapi koordinat akan berstatus perlu pemeriksaan dan tidak akan tampil sebagai titik resmi.</div>
                @endif
                @if (!empty($preview['errors']))
                    <div class="mx-5 mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800 sm:mx-6"><div class="font-bold">Impor belum dapat dijalankan</div><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($preview['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @if (!empty($preview['warnings']))
                    <details class="mx-5 mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 sm:mx-6"><summary class="cursor-pointer text-[11px] font-bold text-amber-900">{{ count($preview['warnings']) }} peringatan koordinat (lihat detail)</summary><ul class="mt-2 max-h-48 list-disc space-y-1 overflow-auto pl-5 text-[10px] leading-5 text-amber-900">@foreach ($preview['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></details>
                @endif
                <div class="flex flex-col-reverse gap-2 border-t border-zinc-100 bg-zinc-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <form action="{{ route('admin.schools.import.cancel') }}" method="POST">@csrf @method('DELETE')<button class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 sm:w-auto">Batalkan pratinjau</button></form>
                    <form action="{{ route('admin.schools.import.confirm') }}" method="POST">@csrf<button type="submit" {{ !empty($preview['errors']) || empty($preview['rows']) ? 'disabled' : '' }} onclick="return confirm('Impor data sekolah sesuai pratinjau?')" class="w-full rounded-xl bg-zinc-900 px-4 py-3 text-xs font-semibold text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">Konfirmasi dan simpan</button></form>
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="border-b border-zinc-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-bold">Riwayat impor</h2><p class="mt-1 text-[11px] text-zinc-500">Hanya metadata dan ringkasan impor yang disimpan, bukan salinan seluruh JSON.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[600px] text-left text-xs"><thead class="bg-zinc-50 text-[10px] text-zinc-500"><tr><th class="px-5 py-3 font-semibold">Waktu</th><th class="px-5 py-3 font-semibold">File</th><th class="px-5 py-3 font-semibold">Admin</th><th class="px-5 py-3 font-semibold">Baru</th><th class="px-5 py-3 font-semibold">Diperbarui</th><th class="px-5 py-3 font-semibold">Peringatan koordinat</th></tr></thead><tbody class="divide-y divide-zinc-100">@forelse ($logs as $log)<tr><td class="whitespace-nowrap px-5 py-3 text-zinc-500">{{ $log->created_at->format('d M Y H:i') }}</td><td class="max-w-[220px] truncate px-5 py-3 font-medium text-zinc-800">{{ $log->source_file_name }}</td><td class="px-5 py-3 text-zinc-600">{{ $log->uploadedBy?->name ?? 'Akun dihapus' }}</td><td class="px-5 py-3 tabular-nums">{{ $log->created_count }}</td><td class="px-5 py-3 tabular-nums">{{ $log->updated_count }}</td><td class="px-5 py-3 tabular-nums">{{ $log->coordinate_warnings }}</td></tr>@empty<tr><td colspan="6" class="px-5 py-8 text-center text-xs text-zinc-500">Belum ada riwayat impor.</td></tr>@endforelse</tbody></table></div>
            <div class="border-t border-zinc-100 px-5 py-3">{{ $logs->links() }}</div>
        </section>
    </main>
</div>
@endsection
