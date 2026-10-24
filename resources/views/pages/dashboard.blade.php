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

            const canManageAssets =
                {{ \Illuminate\Support\Js::from($canManageAssets ?? false) }};

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
            let currentPage = 1;
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
            });

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

            function createSchoolPopup(school) {
                const wrapper = element('div', 'w-[240px]');

                const eyebrow = element(
                    'div',
                    'text-[9px] font-bold uppercase tracking-wider text-zinc-500',
                    'Informasi Sekolah'
                );

                const name = element(
                    'h3',
                    'mt-2 text-sm font-bold leading-5 text-zinc-900',
                    school.name
                );

                const npsn = element(
                    'p',
                    'mt-1 text-[10px] text-zinc-500',
                    'NPSN: ' + (school.npsn || 'Belum tersedia')
                );

                const address = element(
                    'p',
                    'mt-2 text-[10px] leading-4 text-zinc-600',
                    school.address || 'Alamat belum tersedia'
                );

                const isVerified = school.location_status === 'verified';
                const status = element(
                    'span',
                    'mt-3 inline-flex rounded-md border px-2 py-1 text-[9px] font-semibold ' +
                    (isVerified
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                        : 'border-amber-200 bg-amber-50 text-amber-800'),
                    coordinateStatusLabel(school.location_status)
                );

                const actions = element('div', 'mt-3 grid grid-cols-2 gap-2');

                const profileButton = element(
                    'button',
                    'rounded-lg border border-zinc-200 px-2 py-2 ' +
                    'text-[10px] font-semibold text-zinc-700 hover:bg-zinc-50',
                    'Profil Sekolah'
                );

                profileButton.type = 'button';
                profileButton.addEventListener('click', () => {
                    selectedSchool = school;
                    showSchoolProfile(school);
                });

                const kibButton = element(
                    'button',
                    'rounded-lg bg-zinc-950 px-2 py-2 ' +
                    'text-[10px] font-semibold text-white hover:bg-zinc-800',
                    'Lihat Inventaris'
                );

                kibButton.type = 'button';
                kibButton.addEventListener('click', () => {
                    selectedSchool = school;
                    showKibModal(school);
                });

                actions.append(profileButton, kibButton);

                wrapper.append(eyebrow, name, npsn, address, status, actions);

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

                container.replaceChildren();

                const details = [
                    ['Nama Sekolah', school.name],
                    ['NPSN', school.npsn],
                    ['Jenjang Pendidikan', school.education_level],
                    ['Status Sekolah', school.school_status],
                    ['Alamat', school.address],
                    ['Dusun', school.hamlet],
                    ['Kecamatan', school.district],
                    ['Kabupaten/Kota', school.regency],
                    ['Provinsi', school.province],
                    ['Nomor Telepon', school.phone],
                    ['Akreditasi', school.accreditation],
                    ['Status Lokasi', coordinateStatusLabel(school.location_status)]
                ];

                details.forEach(([label, value]) => {
                    const row = element('div', 'border-b border-zinc-100 pb-3 last:border-b-0');

                    row.append(
                        element('div', 'text-[10px] font-semibold text-zinc-500', label),
                        element('div', 'mt-1 break-words text-xs font-medium text-zinc-800', displayValue(value))
                    );

                    container.append(row);
                });

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
            function showKibModal(school) {
                activeKib = 'A';
                currentPage = 1;

                $('#kib-school-name').textContent = school.name;
                $('#kib-school-status').textContent =
                    'Lokasi terverifikasi';

                $$('.kib-tab').forEach(tab => {
                    tab.setAttribute(
                        'aria-selected',
                        tab.dataset.kibTab === activeKib ? 'true' : 'false'
                    );
                });

                $('#kib-search').value = '';
                $('#kib-condition').value = '';

                $('#add-asset-button').hidden = !canManageAssets;

                renderKibTable();

                openModal($('#kib-modal'));
            }

            function getFilteredAssets() {
                if (!selectedSchool) return [];

                const assets = Array.isArray(selectedSchool.assets) ?
                    selectedSchool.assets : [];

                const query = $('#kib-search').value.trim().toLowerCase();
                const condition = $('#kib-condition').value;

                return assets.filter(asset => {
                    if (asset.category !== activeKib) return false;

                    const searchable = [
                        asset.code,
                        asset.name,
                        asset.location
                    ].filter(Boolean).join(' ').toLowerCase();

                    if (query && !searchable.includes(query)) return false;

                    if (condition && asset.condition !== condition) return false;

                    return true;
                });
            }

            function conditionLabel(condition) {
                const labels = {
                    baik: 'Baik',
                    rusak_ringan: 'Rusak Ringan',
                    rusak_berat: 'Rusak Berat'
                };

                return labels[condition] || 'Belum diketahui';
            }

            function renderKibTable() {
                const tbody = $('#kib-table-body');
                const empty = $('#kib-empty-state');

                tbody.replaceChildren();

                const assets = getFilteredAssets();
                const total = assets.length;

                const totalPages = Math.ceil(total / pageSize);

                if (totalPages > 0 && currentPage > totalPages) {
                    currentPage = totalPages;
                }

                if (total === 0) {
                    currentPage = 1;
                }

                const start = (currentPage - 1) * pageSize;
                const pageAssets = assets.slice(start, start + pageSize);

                pageAssets.forEach(asset => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-zinc-50';

                    [
                        asset.code || '—',
                        asset.name || '—',
                        asset.location || 'Belum tersedia'
                    ].forEach(value => {
                        const cell = element(
                            'td',
                            'px-4 py-3 text-[11px] text-zinc-700',
                            value
                        );

                        row.append(cell);
                    });

                    const conditionCell = element(
                        'td',
                        'px-4 py-3 text-[10px] text-zinc-600',
                        conditionLabel(asset.condition)
                    );

                    const yearCell = element(
                        'td',
                        'px-4 py-3 text-[11px] text-zinc-600',
                        asset.year || '—'
                    );

                    row.append(conditionCell, yearCell);
                    tbody.append(row);
                });

                empty.classList.toggle('hidden', total > 0);

                $('#kib-result-count').textContent = `${total} barang`;

                $('#kib-pagination-info').textContent = total ?
                    `Menampilkan ${start + 1}–${Math.min(start + pageSize, total)} dari ${total} data` :
                    'Menampilkan 0 data';

                $('#kib-page-number').textContent =
                    `${total ? currentPage : 0} / ${totalPages}`;

                $('#kib-prev-page').disabled = currentPage <= 1;
                $('#kib-next-page').disabled =
                    totalPages === 0 || currentPage >= totalPages;
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
                const totalPages = Math.ceil(
                    getFilteredAssets().length / pageSize
                );

                if (currentPage < totalPages) {
                    currentPage++;
                    renderKibTable();
                }
            });

            $('#add-asset-button').addEventListener('click', () => {
                /*
                 * Tombol ini hanya titik integrasi UI.
                 * Form dan endpoint penyimpanan harus dibuat terpisah.
                 */
                window.dispatchEvent(new CustomEvent('sibaset:asset-create', {
                    detail: {
                        schoolId: selectedSchool?.id,
                        category: activeKib
                    }
                }));
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
