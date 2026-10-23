@extends('layouts.school')

@section('title', 'Profil Sekolah')
@section('page-heading', 'Profil Sekolah')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div><div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">Identitas satuan pendidikan</div><h1 class="mt-2 text-2xl font-bold tracking-tight">Profil Sekolah</h1><p class="mt-2 text-xs leading-5 text-zinc-500">Periksa data sekolah dan ajukan koreksi koordinat lokasi apabila diperlukan.</p></div>

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs leading-5 text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs leading-5 text-rose-800"><div class="font-bold">Pengajuan belum dapat disimpan</div><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 ring-1 ring-amber-200"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg></div>
        <div><div class="text-xs font-bold text-amber-950">Koordinat resmi memerlukan verifikasi admin</div><p class="mt-1 text-[11px] leading-5 text-amber-900/80">Koordinat yang diajukan tidak langsung mengganti lokasi resmi di peta. Admin akan memeriksa titik terlebih dahulu. Selama proses ini, koordinat resmi sebelumnya tetap digunakan.</p></div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-bold">Informasi sekolah</h2><p class="mt-1 text-[11px] text-zinc-500">Identitas berikut bersumber dari data awal yang dikelola admin.</p></div>
        <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2 sm:p-6">
            @foreach ([
                'Nama sekolah' => $school->nama,
                'NPSN' => $school->npsn,
                'Jenjang' => $school->bentuk_pendidikan,
                'Status' => $school->status_sekolah,
                'Alamat' => $school->alamat_jalan,
                'Dusun' => $school->nama_dusun,
                'Kecamatan' => $school->kecamatan,
                'Kabupaten/Kota' => $school->kabupaten,
                'Provinsi' => $school->provinsi,
                'Nomor telepon' => $school->nomor_telepon,
                'Akreditasi' => $school->akreditasi,
            ] as $label => $value)
                <div class="{{ $label === 'Alamat' ? 'sm:col-span-2' : '' }}"><dt class="text-[10px] font-semibold text-zinc-500">{{ $label }}</dt><dd class="mt-1 break-words text-xs font-medium text-zinc-800">{{ filled($value) ? $value : 'Belum tersedia' }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-100 px-5 py-4 sm:px-6"><h2 class="text-sm font-bold">Koordinat sekolah</h2><p class="mt-1 text-[11px] text-zinc-500">Pilih titik di dalam batas wilayah kota atau masukkan latitude dan longitude secara manual.</p></div>
        <div class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div><div class="text-[11px] font-semibold text-zinc-800">Status koordinat saat ini</div><div class="mt-1 text-[10px] leading-5 text-zinc-500">{{ $school->lintang !== null && $school->bujur !== null ? 'Titik tersimpan: '.$school->lintang.', '.$school->bujur : 'Sekolah belum memiliki pasangan koordinat.' }}</div></div>
                <span class="w-fit rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-zinc-600">{{ match($school->coordinate_status) {'verified' => 'Terverifikasi', 'pending' => 'Belum diverifikasi', 'outside_boundary' => 'Di luar wilayah kota', 'needs_review' => 'Perlu pemeriksaan', default => 'Belum ada koordinat'} }}</span>
            </div>

            @if ($pendingRequest)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="text-xs font-bold text-amber-950">Ada pengajuan yang sedang diperiksa</div><p class="mt-1 text-[11px] leading-5 text-amber-900">Diajukan {{ $pendingRequest->created_at->format('d M Y H:i') }} dengan titik {{ $pendingRequest->latitude }}, {{ $pendingRequest->longitude }}. Pengajuan baru dinonaktifkan sampai admin menyelesaikan pemeriksaan ini.</p></div>
            @endif

            <div id="school-coordinate-map" class="z-0 h-[330px] w-full overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100" role="region" aria-label="Peta pemilih koordinat sekolah"></div>
            <p id="school-coordinate-map-status" class="text-[10px] leading-5 text-zinc-500" aria-live="polite">{{ $cityBoundary ? 'Batas administrasi Kota Palangka Raya ditampilkan. Klik peta untuk memilih titik di dalam wilayah kota.' : 'Batas wilayah belum tersedia. Pemilihan titik dinonaktifkan sampai pemeriksaan wilayah dapat dilakukan.' }}</p>

            <form action="{{ route('school.coordinates.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">Lintang (latitude)</span><input id="school-latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude', $school->lintang) }}" required {{ $pendingRequest || !$cityBoundary ? 'disabled' : '' }} class="h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400 disabled:bg-zinc-50 disabled:text-zinc-400">@error('latitude')<span class="mt-1 block text-[10px] text-rose-700">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">Bujur (longitude)</span><input id="school-longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude', $school->bujur) }}" required {{ $pendingRequest || !$cityBoundary ? 'disabled' : '' }} class="h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs outline-none focus:border-zinc-400 disabled:bg-zinc-50 disabled:text-zinc-400">@error('longitude')<span class="mt-1 block text-[10px] text-rose-700">{{ $message }}</span>@enderror</label>
                </div>
                <label class="block"><span class="mb-2 block text-[11px] font-semibold text-zinc-700">Catatan perubahan (opsional)</span><textarea name="request_note" rows="2" maxlength="2000" {{ $pendingRequest || !$cityBoundary ? 'disabled' : '' }} placeholder="Contoh: titik lama berada di jalan; lokasi gedung sekolah sudah dikoreksi." class="w-full resize-y rounded-xl border border-zinc-200 px-3 py-3 text-xs leading-5 outline-none focus:border-zinc-400 disabled:bg-zinc-50">{{ old('request_note') }}</textarea></label>
                <div class="flex flex-col gap-3 border-t border-zinc-100 pt-4 sm:flex-row sm:items-center sm:justify-between"><p class="max-w-xl text-[10px] leading-5 text-zinc-500">Titik di luar poligon Kota Palangka Raya akan ditolak oleh server. Pasangan 0,0 tidak diterima. Simpan akan membuat pengajuan, bukan langsung mengubah koordinat resmi.</p><button type="submit" {{ $pendingRequest || !$cityBoundary ? 'disabled' : '' }} class="shrink-0 rounded-xl bg-zinc-900 px-4 py-3 text-[11px] font-semibold text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-40">Kirim pengajuan koordinat →</button></div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(() => {
    const geometry = {{ \Illuminate\Support\Js::from($cityBoundary) }};
    const latitude = document.getElementById('school-latitude');
    const longitude = document.getElementById('school-longitude');
    const mapStatus = document.getElementById('school-coordinate-map-status');

    if (!window.L || !geometry) return;

    const map = L.map('school-coordinate-map', { scrollWheelZoom: false }).setView([-2.21, 113.92], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const boundaryLayer = L.geoJSON(geometry, {
        style: { color: '#52525b', weight: 2, opacity: .9, fillColor: '#a1a1aa', fillOpacity: .08 }
    }).addTo(map);
    map.fitBounds(boundaryLayer.getBounds(), { padding: [12, 12] });

    const geometryType = geometry.type;
    const coordinates = geometry.coordinates;

    function insideRing(ring, lng, lat) {
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const [xi, yi] = ring[i];
            const [xj, yj] = ring[j];
            if ((yi > lat) !== (yj > lat) && lng < ((xj - xi) * (lat - yi) / (yj - yi)) + xi) inside = !inside;
        }
        return inside;
    }

    function insidePolygon(rings, lng, lat) {
        if (!rings.length || !insideRing(rings[0], lng, lat)) return false;
        return !rings.slice(1).some(hole => insideRing(hole, lng, lat));
    }

    function insideCity(lat, lng) {
        if (geometryType === 'Polygon') return insidePolygon(coordinates, lng, lat);
        if (geometryType === 'MultiPolygon') return coordinates.some(polygon => insidePolygon(polygon, lng, lat));
        return false;
    }

    let marker = null;
    function setMarker(lat, lng, writeInputs = false) {
        if (writeInputs) {
            latitude.value = lat.toFixed(7);
            longitude.value = lng.toFixed(7);
        }
        if (!marker) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', () => {
                const point = marker.getLatLng();
                if (!insideCity(point.lat, point.lng)) {
                    marker.setLatLng([Number(latitude.value) || -2.21, Number(longitude.value) || 113.92]);
                    mapStatus.textContent = 'Titik harus berada di dalam batas Kota Palangka Raya.';
                    return;
                }
                setMarker(point.lat, point.lng, true);
                mapStatus.textContent = 'Titik berada di dalam batas wilayah. Pengajuan tetap menunggu verifikasi admin.';
            });
        } else {
            marker.setLatLng([lat, lng]);
        }
    }

    const initialLat = Number(latitude.value);
    const initialLng = Number(longitude.value);
    if (latitude.value !== '' && longitude.value !== '' && insideCity(initialLat, initialLng)) {
        setMarker(initialLat, initialLng);
    }

    map.on('click', event => {
        if (!insideCity(event.latlng.lat, event.latlng.lng)) {
            mapStatus.textContent = 'Titik yang dipilih berada di luar wilayah Kota Palangka Raya. Pilih titik di dalam garis batas.';
            return;
        }
        setMarker(event.latlng.lat, event.latlng.lng, true);
        mapStatus.textContent = 'Titik dipilih dalam batas wilayah. Klik dan seret marker untuk menyesuaikan posisi.';
    });

    function updateFromInputs() {
        const lat = Number(latitude.value);
        const lng = Number(longitude.value);
        if (latitude.value === '' || longitude.value === '' || !Number.isFinite(lat) || !Number.isFinite(lng)) return;
        if (insideCity(lat, lng)) {
            setMarker(lat, lng);
            mapStatus.textContent = 'Koordinat berada di dalam batas wilayah; persetujuan admin tetap diperlukan.';
        } else {
            mapStatus.textContent = 'Koordinat di luar wilayah kota atau tidak valid. Pengajuan akan ditolak oleh server.';
        }
    }
    latitude.addEventListener('change', updateFromInputs);
    longitude.addEventListener('change', updateFromInputs);
})();
</script>
@endpush
