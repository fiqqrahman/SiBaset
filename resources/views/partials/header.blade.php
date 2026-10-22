<header
    class="relative z-20 flex min-h-[68px] items-center gap-3
               border-b border-zinc-200 bg-white/95 px-4 sm:px-6">

    <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3" aria-label="SiBaset Dashboard">

        <div class="flex h-10 w-10 items-center justify-center
                    rounded-xl bg-zinc-950 text-white">

            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"
                aria-hidden="true">

                <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z" />
                <path d="M3 7.5 12 12l9-4.5M12 12v9" />
                <path d="m8 5 9 4.5" />
            </svg>
        </div>

        <div>
            <div class="text-lg font-extrabold tracking-tight">
                SiBaset
            </div>

            <div class="hidden text-[10px] font-medium text-zinc-500
                        sm:block">
                SISTEM INFORMASI ASET
            </div>
        </div>
    </a>

    <div class="mx-1 hidden h-8 w-px bg-zinc-200 sm:block"></div>

    <div class="hidden min-w-0 lg:block">
        <div class="text-xs font-semibold text-zinc-800">
            Pemetaan Aset Pendidikan
        </div>

        <div class="mt-1 text-[10px] text-zinc-500">
            Beranda / Peta Persebaran
        </div>
    </div>

    <div class="mx-auto w-full max-w-md">
        <label for="school-search" class="sr-only">
            Cari sekolah atau lokasi
        </label>

        <div class="relative">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                class="absolute left-3 top-1/2 h-4 w-4
                        -translate-y-1/2 text-zinc-400"
                aria-hidden="true">

                <circle cx="11" cy="11" r="7" />
                <path d="m16 16 4 4" />
            </svg>

            <input id="school-search" type="search" autocomplete="off"
                placeholder="Cari nama sekolah, NPSN, atau wilayah..."
                class="h-10 w-full rounded-xl border border-zinc-200
                       bg-zinc-50 pl-10 pr-3 text-xs outline-none
                       transition placeholder:text-zinc-400
                       focus:border-zinc-400 focus:bg-white
                       focus:ring-2 focus:ring-zinc-100">

            <div id="search-results"
                class="absolute left-0 right-0 top-12 z-[1000]
                        hidden overflow-hidden rounded-xl
                        border border-zinc-200 bg-white shadow-xl">
            </div>
        </div>
    </div>

    <div class="hidden shrink-0 items-center gap-2 xl:flex">
        <span class="h-2 w-2 rounded-full bg-zinc-800"></span>

        <span class="text-[11px] font-medium text-zinc-600">
            Kota Palangka Raya
        </span>
    </div>

    <div class="ml-auto flex shrink-0 items-center gap-2">

        <div class="hidden border-l border-zinc-200 pl-3 text-right
                    md:block">
            <div id="current-date" class="text-[10px] font-semibold text-zinc-700">
                —
            </div>

            <div id="current-time" class="mt-0.5 text-[10px] text-zinc-500">
                —
            </div>
        </div>

        <button type="button"
            class="relative flex h-10 w-10 items-center justify-center
                       rounded-xl border border-zinc-200 bg-white
                       text-zinc-600 hover:bg-zinc-50"
            aria-label="Notifikasi" title="Notifikasi">

            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"
                aria-hidden="true">

                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                <path d="M10 21h4" />
            </svg>

            <span
                class="absolute right-2 top-2 h-2 w-2
                         rounded-full bg-zinc-800 ring-2 ring-white">
            </span>
        </button>

        <button type="button" id="profile-trigger"
            class="flex h-10 w-10 items-center justify-center
                       rounded-xl bg-zinc-100 text-xs font-bold
                       text-zinc-800 hover:bg-zinc-200"
            aria-label="Buka profil pengguna" aria-haspopup="dialog" aria-controls="profile-modal">
            {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
        </button>
    </div>
</header>
