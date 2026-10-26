@extends('layouts.app')

@section('title', 'Peta Persebaran Aset')

@section('content')

    <div class="flex h-[100dvh] flex-col overflow-hidden">

        @include('partials.header')

        <main class="relative min-h-0 flex-1 overflow-hidden">

            <div id="map" role="region" aria-label="Peta interaktif persebaran sekolah di Kota Palangka Raya">
            </div>

            @include('partials.map.controls')

            <div
                class="absolute bottom-4 left-1/2 z-[500]
                    w-[calc(100%-24px)] max-w-sm
                    -translate-x-1/2 sm:bottom-5">

                <div class="glass-panel rounded-xl px-4 py-3">

                    <div class="flex items-center justify-between gap-3">

                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0
                                    items-center justify-center
                                    rounded-lg bg-zinc-100">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                                    class="h-5 w-5 text-zinc-700">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14" />
                                    <path d="M9 21v-6h6v6M9 9h.01M15 9h.01" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold">
                                    Data Persebaran Sekolah
                                </p>

                                <p class="mt-1 text-[10px] text-zinc-500">
                                    Lokasi ditampilkan setelah diverifikasi
                                </p>
                            </div>
                        </div>

                        <button type="button" id="show-school-list"
                            class="shrink-0 rounded-lg border
                                   border-zinc-200 px-3 py-2
                                   text-[10px] font-semibold
                                   hover:bg-zinc-50">
                            Daftar Sekolah
                        </button>
                    </div>

                    <div id="school-list-panel" hidden class="mt-3 border-t border-zinc-200 pt-3">

                        <div class="mb-2 text-[10px] font-semibold
                                text-zinc-500">
                            SEKOLAH TERDAFTAR
                        </div>

                        <div id="school-list" class="max-h-48 space-y-1 overflow-y-auto">
                        </div>
                    </div>
                </div>
            </div>

        </main>

        @include('partials.modals.kib')
        @include('partials.modals.school-profile')
        @include('partials.modals.school-details')
        @include('partials.modals.users')
        @include('partials.modals.policies')
        @include('partials.modals.notifications')

    </div>

@endsection

@push('styles')
<style>
    .sibaset-school-div-icon { background: transparent; border: 0; }
    .sibaset-school-marker {
        display: grid; place-items: center; width: 36px; height: 36px;
        border: 2px solid rgba(255,255,255,.96); box-shadow: 0 2px 8px rgba(24,24,27,.25);
        color: white;
    }
    .sibaset-school-marker svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.65; stroke-linecap: round; stroke-linejoin: round; }
    .school-marker-pending { border-style: dashed; opacity: .76; box-shadow: 0 0 0 2px rgba(255,255,255,.65), 0 2px 8px rgba(24,24,27,.18); }
    .school-marker-tk { background: #a16207; border-radius: 50% 50% 50% 8px; transform: rotate(-45deg); }
    .school-marker-tk svg { transform: rotate(45deg); }
    .school-marker-sd { background: #2563eb; border-radius: 11px 11px 11px 4px; }
    .school-marker-smp { background: #6d28d9; border-radius: 50% 50% 50% 4px; transform: rotate(-45deg); }
    .school-marker-smp svg { transform: rotate(45deg); }
</style>
@endpush

@push('scripts')
    <script>
        (() => {
            'use strict';

            /*
             * Data harus berasal dari Laravel.
             * Sekolah tanpa koordinat terverifikasi tidak akan dibuatkan marker.
             */
            const schools = {{ \Illuminate\Support\Js::from($schools ?? []) }};
            const cityBoundary = {{ \Illuminate\Support\Js::from($cityBoundary ?? null) }};
            const assetDataEndpoint = @js(route('admin.assets.school-data', ['school' => '__SCHOOL__']));
            const schoolAcquisitionsEndpoint = @js(route('admin.assets.school-acquisitions', ['school' => '__SCHOOL__']));
            const notificationsEndpoint = @js(route('admin.notifications'));
            const schoolChangesEndpoint = @js(route('admin.assets.school-changes', ['school' => '__SCHOOL__']));
            const schoolHistoryEndpoint = @js(route('admin.assets.school-history', ['school' => '__SCHOOL__']));
            const assetTypesEndpoint = @js(route('admin.assets.types.catalogue'));
            const assetTypeStoreEndpoint = @js(route('admin.assets.types.store'));
            const assetTypeToggleEndpoint = @js(route('admin.assets.types.toggle', ['type' => '__TYPE__']));

            const canManageAssets =
                {{ \Illuminate\Support\Js::from($canManageAssets ?? true) }};

            const defaultCenter = [-2.21, 113.92];
            const defaultZoom = 12;

            const $ = (selector, root = document) =>
                root.querySelector(selector);

            const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

            const map = L.map('map', {
                zoomControl: false,
                scrollWheelZoom: true
            }).setView(defaultCenter, defaultZoom);

            const streetLayer = L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }
            );

            /*
             * Lapisan monokrom menggunakan layanan tile publik.
             * Untuk produksi, evaluasi ketentuan layanan tile yang dipilih.
             */
            const grayLayer = L.tileLayer(
                'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
                }
            );

            streetLayer.addTo(map);

            if (cityBoundary) {
                const boundaryLayer = L.geoJSON(cityBoundary, {
                    interactive: false,
                    style: {
                        color: '#71717a',
                        weight: 2,
                        opacity: 0.9,
                        fillColor: '#a1a1aa',
                        fillOpacity: 0.035,
                        dashArray: '5 5'
                    }
                }).addTo(map);
                map.fitBounds(boundaryLayer.getBounds(), {
                    padding: [42, 42],
                    maxZoom: defaultZoom
                });
            }

            const markers = new Map();
            const schoolById = new Map();

            let selectedSchool = null;
            let activeKib = 'A';
            let activeKibView = 'inventory';
            let activeAssetAdminTab = 'perolehan';
            let currentPage = 1;
            let kibLastPage = 0;
            let kibRequestId = 0;
            let schoolAcquisitionRequestId = 0;
            let assetAdminRequestId = 0;
            const adminTabPages = { perubahan: 1, riwayat: 1 };
            let notificationRequestId = 0;
            let lastFocusedElement = null;
            let activeModal = null;

            const pageSize = 8;

            const mappableSchools = schools.filter(school =>
                ['verified', 'pending'].includes(school.location_status) &&
                Number.isFinite(Number(school.latitude)) &&
                Number.isFinite(Number(school.longitude)) &&
                Number(school.latitude) >= -90 &&
                Number(school.latitude) <= 90 &&
                Number(school.longitude) >= -180 &&
                Number(school.longitude) <= 180 &&
                !(Number(school.latitude) === 0 && Number(school.longitude) === 0)
            );

            const verifiedSchools = mappableSchools.filter(
                school => school.location_status === 'verified'
            );

            schools.forEach(school => {
                schoolById.set(String(school.id), school);
            });

            $('#mapped-count').textContent = verifiedSchools.length;

            $('#unmapped-count').textContent = schools.filter(school =>
                school.location_status !== 'verified'
            ).length;

            function element(tag, className, text) {
                const node = document.createElement(tag);

                if (className) {
                    node.className = className;
                }

                if (text !== undefined) {
                    node.textContent = String(text);
                }

                return node;
            }

            function openModal(modal) {
                if (!modal) return;

                lastFocusedElement = document.activeElement;
                activeModal = modal;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');

                document.body.classList.add('modal-open');

                const focusable = modal.querySelector(
                    'button:not([disabled]), input:not([disabled]), ' +
                    'select:not([disabled]), a[href]'
                );

                focusable?.focus();
            }

            function closeModal(modal) {
                if (!modal) return;

                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');

                if (modal.id === 'notifications-modal') {
                    $('#open-notifications-modal')?.setAttribute('aria-expanded', 'false');
                }

                if (activeModal === modal) {
                    activeModal = null;
                }

                if (!document.querySelector(
                        '[role="dialog"]:not([aria-hidden="true"])'
                    )) {
                    document.body.classList.remove('modal-open');
                    lastFocusedElement?.focus();
                }
            }

            $$('[data-close-modal]').forEach(button => {
                button.addEventListener('click', () => {
                    closeModal(button.closest('[role="dialog"]'));
                });
            });

            const notificationsTrigger = $('#open-notifications-modal');
            notificationsTrigger?.addEventListener('click', () => {
                openModal($('#notifications-modal'));
                notificationsTrigger.setAttribute('aria-expanded', 'true');
                loadNotifications();
            });
            $('#notifications-refresh')?.addEventListener('click', loadNotifications);

            [$('#kib-modal'), $('#profile-modal'), $('#school-details-modal'), $('#users-modal'), $('#policies-modal'), $('#notifications-modal')].forEach(modal => {
                modal.addEventListener('click', event => {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && activeModal) {
                    closeModal(activeModal);
                }

                if (event.key === 'Tab' && activeModal) {
                    const focusable = $$(
                        'button:not([disabled]), input:not([disabled]), ' +
                        'select:not([disabled]), a[href], [tabindex="0"]',
                        activeModal
                    ).filter(node => !node.hidden);

                    if (!focusable.length) return;

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (
                        !event.shiftKey &&
                        document.activeElement === last
                    ) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });

            function updateNotificationBadge(count) {
                const badge = $('#notification-count-badge');
                if (!badge) return;
                const safeCount = Math.max(0, Number(count) || 0);
                badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
                badge.classList.toggle('hidden', safeCount === 0);
                badge.setAttribute('aria-label', safeCount + ' aktivitas perlu ditindaklanjuti');
                notificationsTrigger?.setAttribute('aria-label', 'Buka notifikasi, ' + safeCount + ' aktivitas perlu ditindaklanjuti');
            }

            function relativeActivityTime(value) {
                if (!value) return 'Waktu tidak tersedia';
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return 'Waktu tidak tersedia';
                return new Intl.DateTimeFormat('id-ID', {
                    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                }).format(date);
            }

            async function loadNotifications() {
                const list = $('#notifications-list');
                const meta = $('#notifications-feed-meta');
                if (!list || !meta) return;
                const requestId = ++notificationRequestId;
                list.setAttribute('aria-busy', 'true');
                list.replaceChildren(element('div', 'rounded-xl border border-zinc-200 px-4 py-8 text-center text-xs text-zinc-500', 'Memuat aktivitas terbaru…'));
                meta.textContent = 'Memuat aktivitas 14 hari terakhir…';

                try {
                    const response = await fetch(notificationsEndpoint, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!response.ok) throw new Error(response.status === 403 ? 'Akses notifikasi tidak diizinkan.' : 'Aktivitas terbaru tidak dapat dimuat.');
                    const result = await response.json();
                    if (requestId !== notificationRequestId) return;
                    const items = Array.isArray(result.data) ? result.data : [];
                    renderNotifications(items);
                    updateNotificationBadge(result.meta?.pending_count ?? 0);
                    meta.textContent = items.length + ' aktivitas terbaru · ' + (result.meta?.period_days || 14) + ' hari terakhir';
                } catch (error) {
                    if (requestId !== notificationRequestId) return;
                    list.replaceChildren();
                    const panel = element('div', 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-8 text-center');
                    panel.append(
                        element('p', 'text-sm font-semibold text-rose-900', 'Notifikasi tidak dapat dimuat'),
                        element('p', 'mt-2 text-xs leading-5 text-rose-800', error.message || 'Periksa koneksi lalu coba lagi.')
                    );
                    list.append(panel);
                    meta.textContent = 'Gagal memperbarui aktivitas';
                } finally {
                    if (requestId === notificationRequestId) list.setAttribute('aria-busy', 'false');
                }
            }

            function renderNotifications(items) {
                const list = $('#notifications-list');
                if (!list) return;
                list.replaceChildren();
                if (!items.length) {
                    list.append(element('div', 'rounded-xl border border-zinc-200 px-4 py-10 text-center text-xs text-zinc-500', 'Belum ada aktivitas dalam 14 hari terakhir.'));
                    return;
                }

                items.forEach(item => {
                    const card = element('article', 'flex gap-3 rounded-xl border border-zinc-200 bg-white p-3 sm:p-4');
                    const icon = element('div', 'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700', item.target === 'coordinate' ? '⌖' : '▤');
                    icon.setAttribute('aria-hidden', 'true');
                    const content = element('div', 'min-w-0 flex-1');
                    const title = element('p', 'text-xs font-semibold text-zinc-900', item.title || 'Aktivitas SiBaset');
                    const school = element('p', 'mt-1 text-[10px] font-semibold text-zinc-600', item.school_name || 'Sekolah tidak tersedia');
                    const description = element('p', 'mt-1 break-words text-[11px] leading-5 text-zinc-500', item.description || 'Aktivitas telah dicatat.');
                    const actorTime = element('p', 'mt-2 text-[10px] text-zinc-400', (item.actor_name || 'Pengguna') + ' · ' + relativeActivityTime(item.occurred_at));
                    content.append(title, school, description, actorTime);
                    if (item.status) content.append(element('span', 'mt-2 inline-flex rounded-md bg-zinc-100 px-2 py-1 text-[9px] font-medium text-zinc-600', String(item.status).replaceAll('_', ' ')));

                    const action = element('button', 'mt-1 min-h-8 shrink-0 self-start rounded-lg border border-zinc-200 px-2.5 text-[10px] font-semibold text-zinc-700 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-400', 'Lihat');
                    action.type = 'button';
                    action.addEventListener('click', () => {
                        if (item.target === 'coordinate') {
                            window.location.href = @js(route('admin.schools.coordinates.index'));
                            return;
                        }
                        const schoolRecord = item.school_id ? schoolById.get(String(item.school_id)) : null;
                        if (!schoolRecord) {
                            window.alert('Sekolah dari aktivitas ini tidak tersedia pada data peta.');
                            return;
                        }
                        closeModal($('#notifications-modal'));
                        const targetTab = item.target === 'change'
                            ? (item.event_type === 'change_requested' ? 'perubahan' : 'riwayat')
                            : (['acquisition_reviewed', 'asset_reviewed'].includes(item.event_type) ? 'riwayat' : 'perolehan');
                        showKibModal(schoolRecord, 'management', targetTab);
                    });
                    card.append(icon, content, action);
                    list.append(card);
                });
            }
            function createSchoolPopup(school) {
                const wrapper = element('div', 'w-[250px]');
                const stats = school.asset_stats || {};
                wrapper.append(
                    element('div', 'text-[9px] font-bold uppercase tracking-wider text-zinc-500', 'Informasi Sekolah'),
                    element('h3', 'mt-2 text-sm font-bold leading-5 text-zinc-900', school.name),
                    element('p', 'mt-1 text-[10px] text-zinc-500', 'NPSN: ' + (school.npsn || 'Belum tersedia')),
                    element('p', 'mt-2 text-[10px] leading-4 text-zinc-600', school.address || 'Alamat belum tersedia'),
                    element('span', 'mt-3 inline-flex rounded-md border px-2 py-1 text-[9px] font-semibold ' + (school.location_status === 'verified' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800'), coordinateStatusLabel(school.location_status))
                );

                const summary = element('div', 'mt-3 grid grid-cols-2 gap-2');
                [['Total aset', Number(stats.total || 0)], ['Pengajuan aktif', Number(stats.open_acquisitions || 0)]].forEach(([label, value]) => {
                    const tile = element('div', 'rounded-lg border border-zinc-100 bg-zinc-50 px-2 py-2');
                    tile.append(element('div', 'text-[9px] text-zinc-500', label), element('div', 'mt-1 text-sm font-bold tabular-nums text-zinc-900', value));
                    summary.append(tile);
                });

                const actions = element('div', 'mt-3 grid grid-cols-2 gap-2');
                const profileButton = element('button', 'rounded-lg border border-zinc-200 px-2 py-2 text-[10px] font-semibold text-zinc-700 hover:bg-zinc-50', 'Profil & statistik');
                profileButton.type = 'button';
                profileButton.addEventListener('click', () => { selectedSchool = school; showSchoolProfile(school); });
                const inventoryButton = element('button', 'rounded-lg bg-zinc-950 px-2 py-2 text-[10px] font-semibold text-white hover:bg-zinc-800', 'Inventaris / pengajuan');
                inventoryButton.type = 'button';
                inventoryButton.addEventListener('click', () => { selectedSchool = school; openSchoolInventory(school); });
                actions.append(profileButton, inventoryButton);
                wrapper.append(summary, actions);
                return wrapper;
            }
            const educationMarkerConfig = {
                TK: { className: 'school-marker-tk', title: 'TK', svg: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5 12 4l8 4.5-8 4.5-8-4.5Z"/><path d="M7 10.2v5.1c3.2 2.4 6.8 2.4 10 0v-5.1M20 9v6"/></svg>' },
                SD: { className: 'school-marker-sd', title: 'SD', svg: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5c3.1-.8 5.7 0 8 2.1v12c-2.3-2.1-4.9-2.9-8-2.1v-12Z"/><path d="M20 5.5c-3.1-.8-5.7 0-8 2.1v12c2.3-2.1 4.9-2.9 8-2.1v-12Z"/></svg>' },
                SMP: { className: 'school-marker-smp', title: 'SMP', svg: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m2.5 9.2 9.5-5 9.5 5-9.5 5-9.5-5Z"/><path d="M6.5 11.4v4.2c3.6 2.3 7.4 2.3 11 0v-4.2M21.5 9.5v6"/></svg>' }
            };

            function createEducationMarker(school) {
                const type = String(school.education_level || '').toUpperCase();
                const config = educationMarkerConfig[type] || educationMarkerConfig.SD;
                return L.divIcon({
                    className: 'sibaset-school-div-icon',
                    html: '<span class="sibaset-school-marker ' + (school.location_status === 'verified' ? '' : 'school-marker-pending ') + config.className + '" title="' + config.title + ' · ' + coordinateStatusLabel(school.location_status) + '">' + config.svg + '</span>',
                    iconSize: [38, 42],
                    iconAnchor: [19, 39],
                    popupAnchor: [0, -36]
                });
            }

            function coordinateStatusLabel(status) {
                return ({
                    verified: 'Terverifikasi',
                    pending: 'Perlu verifikasi',
                    missing: 'Belum ada koordinat',
                    needs_review: 'Perlu pemeriksaan',
                    outside_boundary: 'Di luar wilayah'
                })[status] || 'Perlu pemeriksaan';
            }

            mappableSchools.forEach(school => {
                const marker = L.marker([
                    Number(school.latitude),
                    Number(school.longitude)
                ], { icon: createEducationMarker(school), keyboard: true, title: school.name });

                marker.bindPopup(() => createSchoolPopup(school), {
                    maxWidth: 280,
                    closeButton: true
                });

                marker.addTo(map);

                marker.on('click', () => {
                    selectedSchool = school;
                });

                markers.set(String(school.id), marker);
            });

            /*
             * Profil sekolah menampilkan identitas tanpa menganggap koordinat
             * sekolah sebagai koordinat seluruh aset di dalamnya.
             */
            function showSchoolProfile(school) {
                const modal = $('#school-details-modal');
                const container = $('#school-details-content');
                if (!modal || !container) return;

                selectedSchool = school;
                container.replaceChildren();
                $('#school-details-modal-title').textContent = school.name || 'Profil Sekolah';
                const stats = school.asset_stats || {};

                const summary = element('section', 'space-y-3');
                summary.append(element('h3', 'text-xs font-bold text-zinc-800', 'Ringkasan inventaris dan pemeriksaan'));
                const statGrid = element('div', 'grid grid-cols-2 gap-3 sm:grid-cols-3');
                [
                    ['Total aset', Number(stats.total || 0), 'Semua unit tercatat'],
                    ['Menunggu verifikasi', Number(stats.pending || 0), 'Unit belum disetujui'],
                    ['Disetujui', Number(stats.approved || 0), 'Unit aktif'],
                    ['Perlu perbaikan', Number(stats.needs_revision || 0), 'Unit dikembalikan'],
                    ['Pengajuan aktif', Number(stats.open_acquisitions || 0), 'Perolehan perlu diperiksa'],
                    ['Perubahan tertunda', Number(stats.pending_changes || 0), 'Menunggu keputusan admin']
                ].forEach(([label, value, hint]) => {
                    const card = element('article', 'rounded-xl border border-zinc-200 bg-white p-3');
                    card.append(
                        element('div', 'text-[10px] font-medium text-zinc-500', label),
                        element('div', 'mt-2 text-2xl font-bold tracking-tight tabular-nums text-zinc-900', value),
                        element('div', 'mt-1 text-[9px] leading-4 text-zinc-400', hint)
                    );
                    statGrid.append(card);
                });
                summary.append(statGrid);

                const actions = element('section', 'grid gap-2 sm:grid-cols-2');
                const inventoryButton = element('button', 'min-h-10 rounded-xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2', 'Buka inventaris KIB');
                inventoryButton.type = 'button';
                inventoryButton.addEventListener('click', () => { closeModal(modal); openSchoolInventory(school); });

                const acquisitionButton = element('button', 'min-h-10 rounded-xl border border-zinc-300 bg-white px-4 py-2 text-xs font-semibold text-zinc-800 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2', 'Periksa pengajuan (' + Number(stats.open_acquisitions || 0) + ')');
                acquisitionButton.type = 'button';
                acquisitionButton.addEventListener('click', () => { closeModal(modal); showSchoolAcquisitionsModal(school); });

                actions.append(inventoryButton, acquisitionButton);

                const detailsGrid = element('section', 'grid gap-3 sm:grid-cols-2');
                const details = [
                    ['Nama Sekolah', school.name], ['NPSN', school.npsn],
                    ['Jenjang Pendidikan', school.education_level], ['Status Sekolah', school.school_status],
                    ['Alamat', school.address], ['Dusun', school.hamlet],
                    ['Kecamatan', school.district], ['Kabupaten/Kota', school.regency],
                    ['Provinsi', school.province], ['Nomor Telepon', school.phone],
                    ['Akreditasi', school.accreditation], ['Status Lokasi', coordinateStatusLabel(school.location_status)]
                ];
                details.forEach(([label, value]) => {
                    const row = element('div', 'rounded-xl border border-zinc-100 bg-zinc-50/50 p-3');
                    row.append(element('div', 'text-[10px] font-semibold text-zinc-500', label), element('div', 'mt-1 break-words text-xs font-medium text-zinc-800', displayValue(value)));
                    detailsGrid.append(row);
                });
                container.append(summary, actions, detailsGrid);
                openModal(modal);
            }
            function displayValue(value) {
                if (value === null || value === undefined || String(value).trim() === '') {
                    return 'Belum tersedia';
                }

                return String(value);
            }

            /*
             * Modal KIB tetap satu untuk semua sekolah.
             * Data aset menggunakan bentuk:
             * school.assets = [{ category, code, name, location, condition, year }]
             */
            function showKibModal(school, initialView = 'inventory', initialAdminTab = 'perolehan') {
                selectedSchool = school;
                activeKib = 'A';
                currentPage = 1;
                kibLastPage = 0;
                adminTabPages.perubahan = 1;
                adminTabPages.riwayat = 1;
                $('#kib-school-name').textContent = school.name;
                $('#kib-school-status').textContent = 'Lokasi: ' + coordinateStatusLabel(school.location_status);
                $$('.kib-tab').forEach(tab => tab.setAttribute('aria-selected', tab.dataset.kibTab === activeKib ? 'true' : 'false'));
                $('#kib-search').value = '';
                $('#kib-condition').value = '';
                $('#add-asset-button').hidden = !canManageAssets;
                openModal($('#kib-modal'));
                setKibView(initialView, initialAdminTab);
            }

            function setKibView(view, adminTab = activeAssetAdminTab) {
                if (view === 'management' && !canManageAssets) view = 'inventory';
                activeKibView = view;
                if (view === 'management') activeAssetAdminTab = adminTab;
                $$('.kib-view-tab').forEach(tab => {
                    const isSelected = tab.dataset.kibView === view;
                    tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    tab.classList.toggle('bg-white', isSelected);
                    tab.classList.toggle('border-zinc-300', isSelected);
                    tab.classList.toggle('shadow-sm', isSelected);
                    tab.classList.toggle('text-zinc-900', isSelected);
                });
                const inventoryPanel = $('#kib-inventory-panel');
                const managementPanel = $('#kib-management-panel');
                if (inventoryPanel) inventoryPanel.hidden = view !== 'inventory';
                if (managementPanel) managementPanel.hidden = view !== 'management';
                if (view === 'management') loadAssetAdminTab(activeAssetAdminTab);
                else renderKibTable();
            }

            function openSchoolInventory(school) {
                selectedSchool = school;
                showKibModal(school, 'inventory');
            }

            function showSchoolAcquisitionsModal(school) {
                selectedSchool = school;
                showKibModal(school, 'management', 'perolehan');
            }

            async function loadSchoolAcquisitions(school, expectedRequestId = assetAdminRequestId) {
                const content = $('#asset-admin-tab-content');
                const requestId = ++schoolAcquisitionRequestId;
                const feedback = $('#asset-admin-feedback');
                if (feedback) feedback.hidden = true;
                const endpoint = schoolAcquisitionsEndpoint.replace('__SCHOOL__', encodeURIComponent(String(school.id)));
                try {
                    const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error(response.status === 403 ? 'Akses tidak diizinkan.' : 'Pengajuan tidak dapat dimuat.');
                    const result = await response.json();
                    if (requestId !== schoolAcquisitionRequestId || expectedRequestId !== assetAdminRequestId || activeAssetAdminTab !== 'perolehan') return;
                    const acquisitions = Array.isArray(result.data) ? result.data : [];
                    const meta = result.meta || {};
                    school.asset_stats = school.asset_stats || {};
                    school.asset_stats.pending_acquisitions = Number(meta.pending || 0);
                    school.asset_stats.needs_revision_acquisitions = Number(meta.needs_revision || 0);
                    school.asset_stats.open_acquisitions = Number(meta.total || acquisitions.length);
                    $('#asset-admin-school-name').textContent = (school.name || 'Sekolah terpilih') + ' · ' + school.asset_stats.open_acquisitions + ' pengajuan aktif';
                    renderSchoolAcquisitions(school, acquisitions);
                } catch (error) {
                    if (requestId !== schoolAcquisitionRequestId || expectedRequestId !== assetAdminRequestId || activeAssetAdminTab !== 'perolehan') return;
                    content.replaceChildren();
                    const panel = element('div', 'rounded-xl border border-rose-200 bg-rose-50 px-5 py-8 text-center');
                    panel.append(element('p', 'text-sm font-semibold text-rose-900', 'Pengajuan tidak dapat dimuat'), element('p', 'mt-2 text-xs leading-5 text-rose-800', error.message || 'Periksa koneksi lalu coba lagi.'));
                    content.append(panel);
                }
            }

            function renderSchoolAcquisitions(school, acquisitions) {
                const content = $('#asset-admin-tab-content');
                content.replaceChildren();
                if (!acquisitions.length) {
                    const empty = element('div', 'rounded-xl border border-zinc-200 px-5 py-10 text-center');
                    empty.append(element('p', 'text-sm font-semibold text-zinc-800', 'Tidak ada pengajuan yang menunggu pemeriksaan'), element('p', 'mt-2 text-xs leading-5 text-zinc-500', 'Semua pengadaan sekolah ini sudah diperiksa. Data unit tersimpan tersedia pada inventaris KIB.'));
                    content.append(empty);
                    return;
                }

                const fundingLabels = { apbd_kota: 'APBD Kota', apbd_provinsi: 'APBD Provinsi', apbn: 'APBN / Pemerintah Pusat', bos_bosp: 'BOS / BOSP', hibah: 'Hibah', bantuan_pemerintah: 'Bantuan Pemerintah', swadaya: 'Swadaya Sekolah', lainnya: 'Lainnya' };
                const methodLabels = { pembelian: 'Pembelian', bantuan: 'Bantuan', hibah: 'Hibah', transfer: 'Transfer / penyerahan', donasi: 'Donasi', lainnya: 'Lainnya' };
                const ownerLabels = { belum_ditentukan: 'Belum ditentukan', pemkot: 'Pemerintah Kota Palangka Raya', pemprov: 'Pemerintah Provinsi', pemerintah_pusat: 'Pemerintah Pusat', yayasan: 'Yayasan', pihak_lain: 'Pihak lain' };
                const docLabels = { bast: 'BAST', bapp: 'BAPP', bukti_pembelian: 'Bukti pembelian', kontrak_pesanan: 'Kontrak / pesanan', dokumen_hibah: 'Dokumen hibah', dokumen_bantuan: 'Dokumen bantuan', dokumen_pendukung: 'Dokumen pendukung' };
                const conditionLabels = { baik: 'Baik', rusak_ringan: 'Rusak ringan', rusak_berat: 'Rusak berat', tidak_diketahui: 'Belum diketahui' };

                acquisitions.forEach(acquisition => {
                    const card = element('article', 'overflow-hidden rounded-2xl border border-zinc-200 bg-white');
                    const header = element('header', 'flex flex-col gap-3 border-b border-zinc-100 bg-zinc-50/70 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5');
                    const headerText = element('div', 'min-w-0');
                    const submittedAt = acquisition.created_at ? relativeActivityTime(acquisition.created_at) : 'Tanggal tidak tersedia';
                    headerText.append(element('h3', 'text-sm font-bold text-zinc-900', 'Perolehan #' + acquisition.id + ' · ' + (acquisition.assets || []).length + ' unit'), element('p', 'mt-1 text-xs text-zinc-500', (acquisition.document_reference || 'Tanpa nomor referensi') + ' · Diajukan ' + submittedAt));
                    if (acquisition.review_note) headerText.append(element('p', 'mt-3 rounded-lg bg-rose-50 p-3 text-xs leading-5 text-rose-900', 'Catatan sebelumnya: ' + acquisition.review_note));
                    const revision = acquisition.status === 'needs_revision';
                    header.append(headerText, element('span', 'w-fit rounded-lg border px-2.5 py-1.5 text-[10px] font-semibold ' + (revision ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-amber-200 bg-amber-50 text-amber-800'), revision ? 'Perlu perbaikan' : 'Menunggu verifikasi'));

                    const body = element('div', 'grid gap-5 p-4 sm:p-5 xl:grid-cols-2');
                    const details = element('section', 'space-y-3');
                    details.append(element('h4', 'text-xs font-bold text-zinc-900', 'Informasi perolehan'));
                    const detailGrid = element('dl', 'grid gap-2 sm:grid-cols-2');
                    [
                        ['Sumber dana', fundingLabels[acquisition.funding_source] || acquisition.funding_source],
                        ['Cara perolehan', methodLabels[acquisition.acquisition_method] || acquisition.acquisition_method],
                        ['Pemilik diajukan', ownerLabels[acquisition.owner_entity] || acquisition.owner_entity],
                        ['Pemberi / penyedia', acquisition.provider_name],
                        ['Nomor referensi', acquisition.document_reference],
                        ['Tanggal perolehan', acquisition.acquired_at || '—'],
                        ['Nilai total', acquisition.total_value !== null && acquisition.total_value !== undefined ? 'Rp ' + Number(acquisition.total_value).toLocaleString('id-ID') : '—']
                    ].forEach(([label, value]) => {
                        const row = element('div', 'rounded-lg border border-zinc-100 p-3');
                        row.append(element('dt', 'text-[10px] text-zinc-500', label), element('dd', 'mt-1 break-words text-xs font-semibold text-zinc-800', value || '—'));
                        detailGrid.append(row);
                    });
                    details.append(detailGrid);
                    if (acquisition.notes) details.append(element('p', 'text-xs leading-5 text-zinc-600', acquisition.notes));

                    const documentsSection = element('section', 'space-y-2');
                    documentsSection.append(element('h4', 'text-xs font-bold text-zinc-900', 'Dokumen pendukung'));
                    const docs = Array.isArray(acquisition.documents) ? acquisition.documents : [];
                    if (!docs.length) documentsSection.append(element('p', 'text-xs text-rose-700', 'Belum ada dokumen tersimpan.'));
                    docs.forEach(doc => {
                        const link = element('a', 'flex items-start justify-between gap-3 rounded-lg border border-zinc-200 p-3 text-xs font-semibold text-zinc-800 hover:bg-zinc-50');
                        link.href = doc.download_url;
                        link.append(element('span', 'min-w-0 break-words', doc.name || 'Dokumen'), element('span', 'shrink-0 text-[10px] text-zinc-500', docLabels[doc.kind] || doc.kind || 'Dokumen'));
                        documentsSection.append(link);
                    });
                    details.append(documentsSection);

                    const assetsSection = element('section', 'space-y-3');
                    assetsSection.append(element('h4', 'text-xs font-bold text-zinc-900', 'Unit aset pada pengajuan'));
                    const assetList = element('div', 'space-y-2');
                    (acquisition.assets || []).forEach(asset => {
                        const item = element('article', 'rounded-xl border border-zinc-200 p-3');
                        const first = element('div', 'flex flex-wrap items-start justify-between gap-2');
                        first.append(element('div', 'text-xs font-bold text-zinc-900', asset.name || 'Tanpa nama'), element('span', 'rounded-md bg-zinc-100 px-2 py-1 text-[10px] font-semibold text-zinc-700', asset.code || 'Tanpa kode'));
                        item.append(first, element('p', 'mt-2 text-[10px] leading-5 text-zinc-500', (asset.category || 'Kategori') + ' · ' + (asset.type || 'Jenis') + ' · Kondisi: ' + (conditionLabels[asset.condition] || asset.condition || 'Belum diketahui') + ' · Lokasi: ' + (asset.location || 'Belum tersedia') + ' · Nomor seri: ' + (asset.serial_number || '—')));
                        assetList.append(item);
                    });
                    assetsSection.append(assetList);

                    const form = element('form', 'mt-4 space-y-3 rounded-xl border border-zinc-200 bg-zinc-50/70 p-4');
                    form.action = acquisition.review_url;
                    form.method = 'POST';
                    const csrf = element('input'); csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || ''; form.append(csrf);
                    form.append(element('h4', 'text-xs font-bold text-zinc-900', 'Keputusan pemeriksaan'));

                    const decisionLabel = element('label', 'block');
                    decisionLabel.append(element('span', 'mb-1.5 block text-[10px] font-semibold text-zinc-700', 'Hasil pemeriksaan'));
                    const decision = element('select', 'min-h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-xs');
                    decision.name = 'decision'; decision.required = true;
                    [['', 'Pilih keputusan'], ['verified_bmd', 'Setujui sebagai BMD Kota'], ['verified_non_bmd', 'Setujui sebagai non-BMD Kota'], ['needs_revision', 'Kembalikan untuk perbaikan'], ['rejected', 'Tolak pengajuan']].forEach(([value, label]) => { const option = element('option', '', label); option.value = value; decision.append(option); });
                    decisionLabel.append(decision);

                    const ownerLabel = element('label', 'block');
                    ownerLabel.append(element('span', 'mb-1.5 block text-[10px] font-semibold text-zinc-700', 'Pemilik yang disahkan admin'));
                    const owner = element('select', 'min-h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-xs'); owner.name = 'verified_owner_entity';
                    [['belum_ditentukan', 'Belum ditentukan'], ['pemkot', 'Pemerintah Kota Palangka Raya'], ['pemprov', 'Pemerintah Provinsi'], ['pemerintah_pusat', 'Pemerintah Pusat'], ['yayasan', 'Yayasan'], ['pihak_lain', 'Pihak lain']].forEach(([value, label]) => { const option = element('option', '', label); option.value = value; owner.append(option); });
                    owner.value = acquisition.owner_entity || 'belum_ditentukan'; ownerLabel.append(owner);

                    const noteLabel = element('label', 'block');
                    noteLabel.append(element('span', 'mb-1.5 block text-[10px] font-semibold text-zinc-700', 'Catatan pemeriksaan'));
                    const note = element('textarea', 'w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs');
                    note.name = 'review_note'; note.rows = 3; note.maxLength = 3000; note.placeholder = 'Wajib diisi jika pengajuan dikembalikan atau ditolak.'; noteLabel.append(note);
                    const error = element('div', 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-900'); error.hidden = true; error.setAttribute('role', 'alert');
                    const submit = element('button', 'min-h-10 w-full rounded-lg bg-zinc-950 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50', 'Simpan keputusan'); submit.type = 'submit';

                    decision.addEventListener('change', () => {
                        if (decision.value === 'verified_bmd') owner.value = 'pemkot';
                        if (decision.value === 'verified_non_bmd' && ['pemkot', 'belum_ditentukan'].includes(owner.value)) owner.value = 'pemprov';
                        note.required = ['needs_revision', 'rejected'].includes(decision.value);
                    });
                    form.append(decisionLabel, ownerLabel, noteLabel, error, submit);
                    form.addEventListener('submit', async event => {
                        event.preventDefault(); error.hidden = true; submit.disabled = true;
                        try {
                            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                            const result = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                const validation = Object.values(result.errors || {}).flat().join(' ');
                                throw new Error(validation || result.message || 'Keputusan belum berhasil disimpan.');
                            }
                            const feedback = $('#asset-admin-feedback');
                            if (feedback) { feedback.hidden = false; feedback.textContent = result.message || 'Keputusan pemeriksaan berhasil dicatat.'; }
                            await loadSchoolAcquisitions(school);
                            loadNotifications();
                        } catch (failure) {
                            error.textContent = failure.message || 'Keputusan belum berhasil disimpan.'; error.hidden = false;
                        } finally { submit.disabled = false; }
                    });

                    body.append(details, assetsSection);
                    card.append(header, body, form);
                    content.append(card);
                });
            }

            function setAssetAdminFeedback(message, variant = 'success') {
                const feedback = $('#asset-admin-feedback');
                if (!feedback) return;
                feedback.textContent = message || '';
                feedback.hidden = !message;
                feedback.className = 'mt-3 rounded-xl border px-4 py-3 text-xs leading-5 ' + (variant === 'error'
                    ? 'border-rose-200 bg-rose-50 text-rose-900'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-900');
            }

            function setAssetAdminLoading(message = 'Memuat data...') {
                const content = $('#asset-admin-tab-content');
                if (content) content.replaceChildren(element('div', 'rounded-xl border border-zinc-200 px-5 py-10 text-center text-xs text-zinc-500', message));
            }

            async function loadAssetAdminTab(tab = activeAssetAdminTab) {
                if (!canManageAssets) return;
                activeAssetAdminTab = tab;
                const requestId = ++assetAdminRequestId;
                $$('[data-asset-admin-tab]').forEach(button => {
                    const isSelected = button.dataset.assetAdminTab === tab;
                    button.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    button.classList.toggle('bg-zinc-950', isSelected);
                    button.classList.toggle('text-white', isSelected);
                    button.classList.toggle('text-zinc-600', !isSelected);
                    button.classList.toggle('hover:bg-zinc-100', !isSelected);
                });
                const schoolLabel = $('#asset-admin-school-name');
                if (schoolLabel) schoolLabel.textContent = tab === 'jenis'
                    ? 'Master global · dipakai bersama oleh seluruh sekolah'
                    : (selectedSchool?.name || 'Sekolah belum dipilih');
                const feedback = $('#asset-admin-feedback');
                if (feedback) feedback.hidden = true;
                setAssetAdminLoading();
                if (tab === 'jenis') {
                    await loadAssetTypeCatalogue(requestId);
                    return;
                }
                if (!selectedSchool) {
                    setAssetAdminLoading('Pilih sekolah dari peta untuk melihat data pengelolaan.');
                    return;
                }
                if (tab === 'perolehan') {
                    await loadSchoolAcquisitions(selectedSchool, requestId);
                    return;
                }
                const endpointTemplate = tab === 'perubahan' ? schoolChangesEndpoint : schoolHistoryEndpoint;
                const endpoint = endpointTemplate.replace('__SCHOOL__', encodeURIComponent(String(selectedSchool.id)));
                const page = adminTabPages[tab] || 1;
                try {
                    const response = await fetch(endpoint + '?page=' + String(page), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error(response.status === 403 ? 'Akses tidak diizinkan.' : 'Data pengelolaan tidak dapat dimuat.');
                    const result = await response.json();
                    if (requestId !== assetAdminRequestId || activeAssetAdminTab !== tab) return;
                    if (tab === 'perubahan') renderSchoolChanges(result.data || [], result.meta || {});
                    else renderSchoolHistory(result.data || [], result.meta || {});
                } catch (error) {
                    if (requestId !== assetAdminRequestId || activeAssetAdminTab !== tab) return;
                    setAssetAdminLoading(error.message || 'Data pengelolaan tidak dapat dimuat.');
                }
            }

            function renderAssetAdminPagination(meta, tab) {
                const content = $('#asset-admin-tab-content');
                if (!content || Number(meta.last_page || 1) <= 1) return;
                const currentPage = Number(meta.current_page || 1);
                const lastPage = Number(meta.last_page || 1);
                const total = Number(meta.total || 0);
                const perPage = Number(meta.per_page || 10);
                const start = total ? ((currentPage - 1) * perPage) + 1 : 0;
                const end = Math.min(currentPage * perPage, total);
                const footer = element('div', 'flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-3');
                footer.append(element('p', 'text-[10px] text-zinc-500', total ? 'Menampilkan ' + start + '–' + end + ' dari ' + total + ' data' : 'Tidak ada data'));
                const controls = element('div', 'flex items-center gap-2');
                const previous = element('button', 'min-h-9 rounded-lg border border-zinc-200 px-3 text-xs font-medium disabled:opacity-40', 'Sebelumnya');
                previous.type = 'button'; previous.disabled = currentPage <= 1;
                previous.addEventListener('click', () => { adminTabPages[tab] = Math.max(1, currentPage - 1); loadAssetAdminTab(tab); });
                controls.append(previous, element('span', 'min-w-12 text-center text-xs font-semibold text-zinc-600', currentPage + ' / ' + lastPage));
                const next = element('button', 'min-h-9 rounded-lg border border-zinc-200 px-3 text-xs font-medium disabled:opacity-40', 'Berikutnya');
                next.type = 'button'; next.disabled = currentPage >= lastPage;
                next.addEventListener('click', () => { adminTabPages[tab] = Math.min(lastPage, currentPage + 1); loadAssetAdminTab(tab); });
                controls.append(next); footer.append(controls); content.append(footer);
            }

            function renderSchoolChanges(changes, meta) {
                const content = $('#asset-admin-tab-content');
                content.replaceChildren();
                if (!changes.length) {
                    const empty = element('div', 'rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center');
                    empty.append(element('p', 'text-sm font-semibold text-zinc-800', 'Tidak ada pengajuan perubahan yang menunggu pemeriksaan'), element('p', 'mt-2 text-xs leading-5 text-zinc-500', 'Pengajuan kondisi atau lokasi dari sekolah ini akan muncul di sini.'));
                    content.append(empty); renderAssetAdminPagination(meta, 'perubahan'); return;
                }
                const changeLabels = { condition: 'Kondisi', location: 'Lokasi', notes: 'Catatan' };
                const conditionLabels = { baik: 'Baik', rusak_ringan: 'Rusak ringan', rusak_berat: 'Rusak berat', tidak_diketahui: 'Belum diketahui' };
                changes.forEach(change => {
                    const card = element('article', 'space-y-4 rounded-xl border border-zinc-200 bg-white p-4 sm:p-5');
                    const header = element('div', 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between');
                    const title = element('div', 'min-w-0');
                    title.append(element('h4', 'text-sm font-bold text-zinc-900', (change.asset_name || 'Aset') + ' · ' + (change.asset_code || 'Tanpa kode')));
                    title.append(element('p', 'mt-1 text-xs text-zinc-500', (change.school_name || selectedSchool?.name || 'Sekolah') + ' · NPSN ' + (change.npsn || '—')));
                    header.append(title, element('span', 'w-fit rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[10px] font-semibold text-amber-800', 'Menunggu pemeriksaan'));
                    card.append(header, element('p', 'text-xs leading-5 text-zinc-600', change.request_note || 'Tidak ada catatan pengajuan.'));
                    const changesGrid = element('dl', 'grid gap-2 sm:grid-cols-2');
                    Object.entries(change.proposed_changes || {}).forEach(([key, value]) => {
                        const row = element('div', 'rounded-lg bg-zinc-50 p-3');
                        const valueText = key === 'condition' ? (conditionLabels[value] || value) : value;
                        row.append(element('dt', 'text-[10px] font-semibold text-zinc-500', changeLabels[key] || key), element('dd', 'mt-1 whitespace-pre-line break-words text-xs text-zinc-800', valueText == null || valueText === '' ? '—' : String(valueText)));
                        changesGrid.append(row);
                    });
                    card.append(changesGrid);
                    const form = element('form', 'grid gap-3 border-t border-zinc-100 pt-4 sm:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)_auto] sm:items-end');
                    form.action = change.review_url; form.method = 'POST';
                    const csrf = element('input'); csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || ''; form.append(csrf);
                    const decisionLabel = element('label', 'block');
                    decisionLabel.append(element('span', 'mb-1.5 block text-[10px] font-semibold text-zinc-700', 'Keputusan'));
                    const decision = element('select', 'min-h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-xs');
                    decision.name = 'decision'; decision.required = true;
                    [['approved', 'Setujui perubahan'], ['needs_revision', 'Minta perbaikan'], ['rejected', 'Tolak']].forEach(([value, label]) => {
                        const option = element('option', '', label); option.value = value; decision.append(option);
                    });
                    decisionLabel.append(decision);
                    const noteLabel = element('label', 'block');
                    noteLabel.append(element('span', 'mb-1.5 block text-[10px] font-semibold text-zinc-700', 'Catatan admin'));
                    const note = element('input', 'min-h-10 w-full rounded-lg border border-zinc-200 px-3 text-xs');
                    note.name = 'review_note'; note.maxLength = 3000; note.placeholder = 'Wajib untuk perbaikan atau penolakan'; noteLabel.append(note);
                    const feedback = element('div', 'sm:col-span-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-900');
                    feedback.hidden = true; feedback.setAttribute('role', 'alert');
                    const submit = element('button', 'min-h-10 rounded-lg bg-zinc-950 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50', 'Simpan');
                    submit.type = 'submit'; form.append(decisionLabel, noteLabel, submit, feedback);
                    form.addEventListener('submit', async event => {
                        event.preventDefault(); feedback.hidden = true; submit.disabled = true;
                        try {
                            const response = await fetch(form.action, {
                                method: 'POST', body: new FormData(form),
                                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin'
                            });
                            const result = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                const validation = Object.values(result.errors || {}).flat().join(' ');
                                throw new Error(validation || result.message || 'Keputusan belum berhasil disimpan.');
                            }
                            setAssetAdminFeedback(result.message || 'Keputusan pengajuan perubahan berhasil dicatat.');
                            loadNotifications();
                            if (activeAssetAdminTab === 'perubahan') {
                                await loadAssetAdminTab('perubahan');
                                setAssetAdminFeedback(result.message || 'Keputusan pengajuan perubahan berhasil dicatat.');
                            }
                        } catch (error) {
                            feedback.textContent = error.message || 'Keputusan belum berhasil disimpan.';
                            feedback.hidden = false;
                        } finally { submit.disabled = false; }
                    });
                    content.append(card);
                });
                renderAssetAdminPagination(meta, 'perubahan');
            }

            function formatAuditDate(value) {
                if (!value) return 'Tanggal tidak tersedia';
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return value;
                return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
            }

            function renderSchoolHistory(history, meta) {
                const content = $('#asset-admin-tab-content'); content.replaceChildren();
                const ownerLabels = { pemkot: 'Pemerintah Kota Palangka Raya', pemprov: 'Pemerintah Provinsi', pemerintah_pusat: 'Pemerintah Pusat', yayasan: 'Yayasan', pihak_lain: 'Pihak lain', belum_ditentukan: 'Belum ditentukan' };
                const statusLabels = {
                    verified_bmd: ['Terverifikasi sebagai BMD', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
                    verified_non_bmd: ['Terverifikasi bukan BMD Kota', 'border-sky-200 bg-sky-50 text-sky-800'],
                    rejected: ['Ditolak', 'border-zinc-300 bg-zinc-100 text-zinc-700']
                };
                if (!history.length) {
                    const empty = element('div', 'rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center');
                    empty.append(element('p', 'text-sm font-semibold text-zinc-800', 'Belum ada riwayat verifikasi'), element('p', 'mt-2 text-xs leading-5 text-zinc-500', 'Keputusan pemeriksaan perolehan sekolah ini akan tercatat di sini.'));
                    content.append(empty); renderAssetAdminPagination(meta, 'riwayat'); return;
                }
                history.forEach(item => {
                    const [statusLabel, statusClass] = statusLabels[item.status] || ['Status tidak diketahui', 'border-zinc-200 bg-zinc-50 text-zinc-700'];
                    const card = element('article', 'overflow-hidden rounded-xl border border-zinc-200 bg-white');
                    const header = element('header', 'flex flex-col gap-2 border-b border-zinc-100 bg-zinc-50/70 p-4 sm:flex-row sm:items-start sm:justify-between');
                    const heading = element('div', 'min-w-0');
                    heading.append(element('h4', 'text-sm font-bold text-zinc-900', 'Perolehan #' + item.id));
                    heading.append(element('p', 'mt-1 text-xs text-zinc-500', (item.document_reference || 'Tanpa nomor referensi') + ' · ' + formatAuditDate(item.reviewed_at)));
                    heading.append(element('p', 'mt-2 text-xs text-zinc-600', 'Pemilik disahkan: ' + (ownerLabels[item.owner_entity] || item.owner_entity || 'Belum ditentukan')));
                    header.append(heading, element('span', 'w-fit rounded-lg border px-2.5 py-1.5 text-[10px] font-semibold ' + statusClass, statusLabel));
                    card.append(header);
                    const body = element('div', 'grid gap-4 p-4 xl:grid-cols-2');
                    const documents = element('section', 'space-y-2');
                    documents.append(element('h5', 'text-xs font-bold text-zinc-900', 'Dokumen perolehan'));
                    if (!(item.documents || []).length) documents.append(element('p', 'text-xs text-zinc-500', 'Tidak ada dokumen terlampir.'));
                    else item.documents.forEach(doc => {
                        const link = element('a', 'flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 text-xs font-semibold hover:bg-zinc-50');
                        link.href = doc.download_url;
                        link.append(element('span', 'min-w-0 break-words', doc.name || 'Dokumen'), element('span', 'shrink-0 text-[10px] text-zinc-500', String(doc.kind || 'dokumen').replaceAll('_', ' ')));
                        documents.append(link);
                    });
                    const audit = element('section', 'space-y-2');
                    audit.append(element('h5', 'text-xs font-bold text-zinc-900', 'Jejak aktivitas'));
                    const events = Array.isArray(item.events) ? item.events : [];
                    if (!events.length) audit.append(element('p', 'text-xs text-zinc-500', 'Belum ada aktivitas audit yang tercatat.'));
                    else {
                        const list = element('ol', 'space-y-3');
                        events.forEach(entry => {
                            const row = element('li', 'flex gap-3');
                            row.append(element('span', 'mt-1.5 h-2 w-2 shrink-0 rounded-full bg-zinc-400'));
                            const detail = element('div', 'min-w-0 flex-1');
                            detail.append(element('p', 'text-xs font-semibold text-zinc-800', String(entry.event_type || 'aktivitas').replaceAll('_', ' ')));
                            detail.append(element('p', 'mt-1 text-[10px] text-zinc-500', formatAuditDate(entry.created_at) + ' · ' + (entry.actor_name || 'Akun tidak tersedia')));
                            if (entry.decision) detail.append(element('p', 'mt-1 text-[10px] text-zinc-600', 'Keputusan: ' + String(entry.decision).replaceAll('_', ' ')));
                            if (entry.review_note) detail.append(element('p', 'mt-1 whitespace-pre-line text-[10px] leading-4 text-zinc-600', entry.review_note));
                            row.append(detail); list.append(row);
                        });
                        audit.append(list);
                    }
                    if (item.review_note) audit.append(element('p', 'rounded-lg bg-zinc-50 p-3 text-xs leading-5 text-zinc-600', 'Catatan pemeriksaan: ' + item.review_note));
                    body.append(documents, audit); card.append(body);
                    if ((item.assets || []).length) {
                        const assetSummary = element('div', 'border-t border-zinc-100 px-4 py-3');
                        assetSummary.append(element('p', 'mb-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500', 'Unit dalam perolehan'));
                        const assetList = element('div', 'flex flex-wrap gap-2');
                        item.assets.forEach(asset => assetList.append(element('span', 'rounded-lg bg-zinc-100 px-2.5 py-1.5 text-[10px] text-zinc-700', (asset.code || '—') + ' · ' + (asset.name || 'Aset'))));
                        assetSummary.append(assetList); card.append(assetSummary);
                    }
                    content.append(card);
                });
                renderAssetAdminPagination(meta, 'riwayat');
            }

            async function loadAssetTypeCatalogue(expectedRequestId) {
                try {
                    const response = await fetch(assetTypesEndpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error(response.status === 403 ? 'Akses tidak diizinkan.' : 'Master jenis barang tidak dapat dimuat.');
                    const result = await response.json();
                    if (expectedRequestId !== assetAdminRequestId || activeAssetAdminTab !== 'jenis') return;
                    renderAssetTypeCatalogue(result.categories || [], result.types || []);
                } catch (error) {
                    if (expectedRequestId !== assetAdminRequestId || activeAssetAdminTab !== 'jenis') return;
                    setAssetAdminLoading(error.message || 'Master jenis barang tidak dapat dimuat.');
                }
            }

            function renderAssetTypeCatalogue(categories, types) {
                const content = $('#asset-admin-tab-content'); content.replaceChildren();
                const layout = element('div', 'grid gap-4 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]');
                const formPanel = element('section', 'rounded-xl border border-zinc-200 bg-white p-4 sm:p-5');
                formPanel.append(element('h4', 'text-sm font-bold text-zinc-900', 'Tambah jenis barang global'));
                formPanel.append(element('p', 'mt-1 text-xs leading-5 text-zinc-500', 'Jenis barang dan kategori dipakai bersama oleh seluruh sekolah.'));
                const form = element('form', 'mt-4 space-y-3'); form.method = 'POST'; form.action = assetTypeStoreEndpoint;
                const csrf = element('input'); csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || ''; form.append(csrf);
                const categoryLabel = element('label', 'block');
                categoryLabel.append(element('span', 'mb-1.5 block text-xs font-semibold text-zinc-700', 'Kategori'));
                const category = element('select', 'min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs');
                category.name = 'asset_category_id'; category.required = true;
                categories.forEach(item => {
                    const option = element('option', '', (item.scheme === 'KIBAR' ? 'KIBAR' : 'KIB ' + item.code) + ' · ' + item.name);
                    option.value = String(item.id); category.append(option);
                });
                categoryLabel.append(category);
                const nameLabel = element('label', 'block');
                nameLabel.append(element('span', 'mb-1.5 block text-xs font-semibold text-zinc-700', 'Nama jenis barang'));
                const name = element('input', 'min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs');
                name.name = 'name'; name.required = true; name.maxLength = 255; name.placeholder = 'Contoh: Perangkat audio'; nameLabel.append(name);
                const codeLabel = element('label', 'block');
                codeLabel.append(element('span', 'mb-1.5 block text-xs font-semibold text-zinc-700', 'Kode klasifikasi resmi'));
                const code = element('input', 'min-h-11 w-full rounded-xl border border-zinc-200 px-3 text-xs');
                code.name = 'classification_code'; code.maxLength = 100; code.placeholder = 'Opsional'; codeLabel.append(code);
                const descriptionLabel = element('label', 'block');
                descriptionLabel.append(element('span', 'mb-1.5 block text-xs font-semibold text-zinc-700', 'Deskripsi'));
                const description = element('textarea', 'w-full rounded-xl border border-zinc-200 px-3 py-2 text-xs');
                description.name = 'description'; description.rows = 3; description.maxLength = 2000; descriptionLabel.append(description);
                const error = element('div', 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-900');
                error.hidden = true; error.setAttribute('role', 'alert');
                const submit = element('button', 'min-h-11 w-full rounded-xl bg-zinc-950 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50', 'Tambahkan ke master');
                submit.type = 'submit'; form.append(categoryLabel, nameLabel, codeLabel, descriptionLabel, error, submit);
                form.addEventListener('submit', async event => {
                    event.preventDefault(); error.hidden = true; submit.disabled = true;
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST', body: new FormData(form),
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin'
                        });
                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const validation = Object.values(result.errors || {}).flat().join(' ');
                            throw new Error(validation || result.message || 'Jenis barang belum berhasil ditambahkan.');
                        }
                        await loadAssetAdminTab('jenis');
                        setAssetAdminFeedback(result.message || 'Master jenis barang berhasil ditambahkan.');
                    } catch (failure) {
                        error.textContent = failure.message || 'Jenis barang belum berhasil ditambahkan.'; error.hidden = false;
                    } finally { submit.disabled = false; }
                });
                formPanel.append(form);

                const typesPanel = element('section', 'overflow-hidden rounded-xl border border-zinc-200 bg-white');
                const typesHeader = element('div', 'border-b border-zinc-100 p-4 sm:p-5');
                typesHeader.append(element('h4', 'text-sm font-bold text-zinc-900', 'Master jenis barang'));
                typesHeader.append(element('p', 'mt-1 text-xs leading-5 text-zinc-500', categories.length + ' kategori · ' + types.length + ' jenis barang'));
                const search = element('input', 'mt-3 min-h-10 w-full rounded-lg border border-zinc-200 px-3 text-xs');
                search.type = 'search'; search.placeholder = 'Cari nama, kode, atau kategori...'; search.setAttribute('aria-label', 'Cari master jenis barang'); typesHeader.append(search);
                const typeList = element('div', 'max-h-[55vh] divide-y divide-zinc-100 overflow-y-auto');
                const renderTypeRows = query => {
                    typeList.replaceChildren();
                    const needle = query.trim().toLocaleLowerCase('id-ID');
                    const filtered = types.filter(item => [item.name, item.classification_code, item.category_name, item.category_code].some(value => String(value || '').toLocaleLowerCase('id-ID').includes(needle)));
                    if (!filtered.length) {
                        typeList.append(element('p', 'px-4 py-8 text-center text-xs text-zinc-500', 'Jenis barang tidak ditemukan.')); return;
                    }
                    filtered.forEach(item => {
                        const row = element('div', 'flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between');
                        const detail = element('div', 'min-w-0');
                        detail.append(element('p', 'text-xs font-semibold text-zinc-900', item.name));
                        detail.append(element('p', 'mt-1 text-[10px] text-zinc-500', (item.category_scheme === 'KIBAR' ? 'KIBAR' : 'KIB ' + item.category_code) + ' · ' + item.category_name + (item.classification_code ? ' · ' + item.classification_code : '')));
                        detail.append(element('p', 'mt-1 text-[10px] ' + (item.is_active ? 'text-emerald-700' : 'text-zinc-500'), item.is_active ? 'Aktif' : 'Nonaktif'));
                        if (item.description) detail.append(element('p', 'mt-1 text-[10px] leading-4 text-zinc-500', item.description));
                        const toggle = element('button', 'min-h-9 shrink-0 rounded-lg border border-zinc-200 px-3 py-2 text-[10px] font-semibold hover:bg-zinc-50 disabled:opacity-50', item.is_active ? 'Nonaktifkan' : 'Aktifkan');
                        toggle.type = 'button';
                        toggle.addEventListener('click', async () => {
                            toggle.disabled = true;
                            const body = new FormData(); body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                            try {
                                const response = await fetch(item.toggle_url, {
                                    method: 'POST', body,
                                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                    credentials: 'same-origin'
                                });
                                const result = await response.json().catch(() => ({}));
                                if (!response.ok) throw new Error(result.message || 'Status jenis barang belum berhasil diubah.');
                                await loadAssetAdminTab('jenis');
                                setAssetAdminFeedback(result.message || 'Status jenis barang berhasil diubah.');
                            } catch (failure) {
                                setAssetAdminFeedback(failure.message || 'Status jenis barang belum berhasil diubah.', 'error');
                            } finally { toggle.disabled = false; }
                        });
                        row.append(detail, toggle); typeList.append(row);
                    });
                };
                search.addEventListener('input', () => renderTypeRows(search.value));
                renderTypeRows(''); typesPanel.append(typesHeader, typeList);
                layout.append(formPanel, typesPanel); content.append(layout);
            }

            $$('.kib-view-tab').forEach(button => button.addEventListener('click', () => setKibView(button.dataset.kibView)));
            $$('[data-asset-admin-tab]').forEach(button => button.addEventListener('click', () => loadAssetAdminTab(button.dataset.assetAdminTab)));

            function conditionLabel(condition) {
                const labels = {
                    baik: 'Baik',
                    rusak_ringan: 'Rusak Ringan',
                    rusak_berat: 'Rusak Berat'
                };

                return labels[condition] || 'Belum diketahui';
            }


            async function renderKibTable() {
                const tbody = $('#kib-table-body');
                const empty = $('#kib-empty-state');
                const requestId = ++kibRequestId;

                tbody.replaceChildren();
                empty.classList.add('hidden');

                if (!selectedSchool) {
                    $('#kib-result-count').textContent = '0 barang';
                    $('#kib-pagination-info').textContent = 'Sekolah belum dipilih';
                    $('#kib-page-number').textContent = '0 / 0';
                    $('#kib-prev-page').disabled = true;
                    $('#kib-next-page').disabled = true;
                    return;
                }

                const loadingRow = document.createElement('tr');
                const loadingCell = element('td', 'px-4 py-8 text-center text-xs text-zinc-500', 'Memuat data inventaris...');
                loadingCell.colSpan = 5;
                loadingRow.append(loadingCell);
                tbody.append(loadingRow);
                $('#kib-prev-page').disabled = true;
                $('#kib-next-page').disabled = true;
                const params = new URLSearchParams({ category: activeKib, page: String(currentPage) });
                const query = $('#kib-search').value.trim();
                const condition = $('#kib-condition').value;
                if (query) params.set('q', query);
                if (condition) params.set('condition', condition);

                try {
                    const url = assetDataEndpoint.replace('__SCHOOL__', encodeURIComponent(String(selectedSchool.id))) + '?' + params.toString();
                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        throw new Error(response.status === 403 ? 'Akses tidak diizinkan.' : 'Data aset tidak dapat dimuat.');
                    }

                    const result = await response.json();
                    if (requestId !== kibRequestId) return;

                    const assets = Array.isArray(result.data) ? result.data : [];
                    const meta = result.meta || {};
                    const total = Number(meta.total || 0);
                    const totalPages = Number(meta.last_page || 0);
                    kibLastPage = totalPages;

                    if (totalPages > 0 && currentPage > totalPages) {
                        currentPage = totalPages;
                        renderKibTable();
                        return;
                    }
                    if (total === 0) currentPage = 1;

                    tbody.replaceChildren();
                    assets.forEach(asset => {
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-zinc-50';
                        [
                            asset.code || '—',
                            asset.name || '—',
                            asset.location || 'Belum tersedia'
                        ].forEach(value => row.append(element('td', 'px-4 py-3 text-[11px] text-zinc-700', value)));

                        row.append(
                            element('td', 'px-4 py-3 text-[10px] text-zinc-600', conditionLabel(asset.condition)),
                            element('td', 'px-4 py-3 text-[11px] text-zinc-600', asset.year || '—')
                        );
                        tbody.append(row);
                    });

                    empty.classList.toggle('hidden', total > 0);
                    const emptyTitle = empty.querySelector('p.mt-3');
                    if (emptyTitle) emptyTitle.textContent = 'Tidak ada data inventaris';
                    const emptyCopy = empty.querySelector('p.mt-1');
                    if (emptyCopy) emptyCopy.textContent = 'Coba ubah pencarian atau kondisi barang.';
                    $('#kib-result-count').textContent = String(total) + ' barang';

                    const start = total ? ((currentPage - 1) * Number(meta.per_page || 8)) + 1 : 0;
                    const end = total ? Math.min(start + assets.length - 1, total) : 0;
                    $('#kib-pagination-info').textContent = total
                        ? 'Menampilkan ' + start + '–' + end + ' dari ' + total + ' data'
                        : 'Menampilkan 0 data';
                    $('#kib-page-number').textContent = String(total ? currentPage : 0) + ' / ' + totalPages;
                    $('#kib-prev-page').disabled = currentPage <= 1;
                    $('#kib-next-page').disabled = totalPages === 0 || currentPage >= totalPages;
                } catch (error) {
                    if (requestId !== kibRequestId) return;
                    tbody.replaceChildren();
                    empty.classList.remove('hidden');
                    const emptyTitle = empty.querySelector('p.mt-3');
                    if (emptyTitle) emptyTitle.textContent = 'Gagal memuat data inventaris';
                    const emptyCopy = empty.querySelector('p.mt-1');
                    if (emptyCopy) emptyCopy.textContent = error.message || 'Periksa koneksi dan coba lagi.';
                    $('#kib-result-count').textContent = 'Data tidak tersedia';
                    $('#kib-pagination-info').textContent = 'Periksa koneksi atau coba muat ulang.';
                    $('#kib-page-number').textContent = '0 / 0';
                    $('#kib-prev-page').disabled = true;
                    $('#kib-next-page').disabled = true;
                }
            }

            $$('.kib-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    activeKib = tab.dataset.kibTab;
                    currentPage = 1;

                    $$('.kib-tab').forEach(item => {
                        item.setAttribute(
                            'aria-selected',
                            item === tab ? 'true' : 'false'
                        );
                    });

                    renderKibTable();
                });
            });

            $('#kib-search').addEventListener('input', () => {
                currentPage = 1;
                renderKibTable();
            });

            $('#kib-condition').addEventListener('change', () => {
                currentPage = 1;
                renderKibTable();
            });

            $('#kib-prev-page').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderKibTable();
                }
            });

            $('#kib-next-page').addEventListener('click', () => {
                if (currentPage < kibLastPage) {
                    currentPage++;
                    renderKibTable();
                }
            });

            $('#add-asset-button').addEventListener('click', () => {
                setKibView('management', 'perolehan');
            });

            function renderSchoolList(query = '') {
                const container = $('#school-list');
                container.replaceChildren();

                const normalized = query.toLowerCase();

                const results = schools.filter(school => {
                    const searchable = [
                        school.name,
                        school.npsn,
                        school.district,
                        school.hamlet
                    ].filter(Boolean).join(' ').toLowerCase();

                    return searchable.includes(normalized);
                }).slice(0, 100);

                if (!results.length) {
                    container.append(element(
                        'p',
                        'px-3 py-4 text-xs text-zinc-500',
                        'Sekolah tidak ditemukan.'
                    ));

                    return;
                }

                results.forEach(school => {
                    const button = element(
                        'button',
                        'flex w-full items-center justify-between gap-3 ' +
                        'rounded-lg px-3 py-2 text-left hover:bg-zinc-50'
                    );

                    button.type = 'button';

                    const info = element('div', 'min-w-0');

                    info.append(
                        element(
                            'div',
                            'truncate text-xs font-semibold text-zinc-800',
                            school.name
                        ),
                        element(
                            'div',
                            'mt-1 truncate text-[10px] text-zinc-500',
                            school.district || 'Kecamatan belum tersedia'
                        )
                    );

                    button.append(
                        info,
                        element(
                            'span',
                            'shrink-0 rounded-md bg-zinc-100 px-2 py-1 ' +
                            'text-[9px] font-medium text-zinc-600',
                            coordinateStatusLabel(school.location_status)
                        )
                    );

                    button.addEventListener('click', () => {
                        selectedSchool = school;

                        if (markers.has(String(school.id))) {
                            map.flyTo([
                                Number(school.latitude),
                                Number(school.longitude)
                            ], 16, {
                                duration: 0.7
                            });

                            markers.get(String(school.id)).openPopup();
                        } else {
                            /*
                             * Sekolah tanpa koordinat valid tetap dapat dicari,
                             * tetapi tidak diberi marker buatan.
                             */
                            showSchoolProfile(school);
                        }
                    });

                    container.append(button);
                });
            }

            $('#show-school-list').addEventListener('click', () => {
                const panel = $('#school-list-panel');
                panel.hidden = !panel.hidden;

                if (!panel.hidden) {
                    renderSchoolList($('#school-search').value);
                }
            });

            const searchInput = $('#school-search');
            const searchResults = $('#search-results');

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.trim().toLowerCase();

                searchResults.replaceChildren();

                if (!query) {
                    searchResults.classList.add('hidden');
                    return;
                }

                const results = schools.filter(school => {
                    const searchable = [
                        school.name,
                        school.npsn,
                        school.district,
                        school.hamlet
                    ].filter(Boolean).join(' ').toLowerCase();

                    return searchable.includes(query);
                }).slice(0, 8);

                if (!results.length) {
                    searchResults.append(element(
                        'div',
                        'px-4 py-3 text-xs text-zinc-500',
                        'Tidak ada sekolah yang cocok.'
                    ));
                }

                results.forEach(school => {
                    const button = element(
                        'button',
                        'block w-full border-b border-zinc-100 px-4 py-3 ' +
                        'text-left hover:bg-zinc-50'
                    );

                    button.type = 'button';

                    button.append(
                        element(
                            'div',
                            'text-xs font-semibold text-zinc-800',
                            school.name
                        ),
                        element(
                            'div',
                            'mt-1 text-[10px] text-zinc-500',
                            coordinateStatusLabel(school.location_status)
                        )
                    );

                    button.addEventListener('click', () => {
                        searchResults.classList.add('hidden');
                        searchInput.value = school.name;

                        selectedSchool = school;

                        if (markers.has(String(school.id))) {
                            map.flyTo([
                                Number(school.latitude),
                                Number(school.longitude)
                            ], 16, {
                                duration: 0.7
                            });

                            markers.get(String(school.id)).openPopup();
                        } else {
                            showSchoolProfile(school);
                        }
                    });

                    searchResults.append(button);
                });

                searchResults.classList.remove('hidden');
            });

            document.addEventListener('click', event => {
                if (!event.target.closest('#school-search') &&
                    !event.target.closest('#search-results')) {
                    searchResults.classList.add('hidden');
                }
            });

            $('#zoom-in').addEventListener('click', () => map.zoomIn());
            $('#zoom-out').addEventListener('click', () => map.zoomOut());

            $('#reset-map').addEventListener('click', () => {
                if (mappableSchools.length > 0) {
                    const bounds = L.latLngBounds(
                        mappableSchools.map(school => [
                            Number(school.latitude),
                            Number(school.longitude)
                        ])
                    );

                    map.fitBounds(bounds, {
                        padding: [50, 50],
                        maxZoom: defaultZoom
                    });
                } else {
                    map.setView(defaultCenter, defaultZoom);
                }
            });

            map.on('mousemove', event => {
                $('#cursor-coordinates').textContent =
                    `${event.latlng.lat.toFixed(5)}, ${event.latlng.lng.toFixed(5)}`;
            });

            $('#profile-trigger').addEventListener('click', () => {
                openModal($('#profile-modal'));
            });

            $('#open-users-modal').addEventListener('click', () => {
                openModal($('#users-modal'));
            });

            $('#open-policies-modal').addEventListener('click', () => {
                openModal($('#policies-modal'));
            });

            function updateClock() {
                const now = new Date();

                $('#current-date').textContent = new Intl.DateTimeFormat(
                    'id-ID', {
                        weekday: 'short',
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    }
                ).format(now);

                $('#current-time').textContent = new Intl.DateTimeFormat(
                    'id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    }
                ).format(now);
            }

            updateClock();
            window.setInterval(updateClock, 1000);

            renderSchoolList();

            /*
             * Peta perlu invalidasi ukuran ketika layout berubah.
             */
            window.addEventListener('resize', () => {
                map.invalidateSize({
                    pan: false
                });
            });

            /*
             * Integrasi ringan untuk modul lain tanpa menambahkan
             * ketergantungan global baru.
             */
            window.SiBasetMap = Object.freeze({
                focusSchool(id) {
                    const school = schoolById.get(String(id));
                    const marker = markers.get(String(id));

                    if (!school) return false;

                    selectedSchool = school;

                    if (marker) {
                        map.flyTo(marker.getLatLng(), 16);
                        marker.openPopup();
                        return true;
                    }

                    showSchoolProfile(school);
                    return false;
                },

                openInventory(id, category = 'A') {
                    const school = schoolById.get(String(id));

                    if (!school) return false;

                    selectedSchool = school;
                    showKibModal(school);

                    const tab = $(`[data-kib-tab="${category}"]`);

                    if (tab) tab.click();

                    return true;
                }
            });

        })();
    </script>
@endpush
