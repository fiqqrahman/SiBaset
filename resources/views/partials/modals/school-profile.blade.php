<div id="profile-modal"
    class="fixed inset-0 z-[2100] hidden items-center justify-center
            bg-zinc-950/40 p-4 backdrop-blur-[3px]"
    role="dialog" aria-modal="true" aria-labelledby="profile-modal-title" aria-hidden="true">

    <section
        class="w-full max-w-md overflow-hidden
                    rounded-2xl border border-white/70
                    bg-white shadow-2xl">

        <header class="flex items-start justify-between
                       border-b border-zinc-200 p-5">

            <div>
                <span class="text-[10px] font-bold uppercase
                             tracking-wider text-zinc-500">
                    Akun Pengguna
                </span>

                <h2 id="profile-modal-title" class="mt-2 text-lg font-bold">
                    Profil Pengguna
                </h2>
            </div>

            <button type="button" data-close-modal
                class="flex h-9 w-9 items-center justify-center
                           rounded-xl border border-zinc-200
                           text-xl text-zinc-500 hover:bg-zinc-100"
                aria-label="Tutup profil">
                &times;
            </button>
        </header>

        <div class="p-5">

            <div class="flex items-center gap-4">
                <div
                    class="flex h-14 w-14 items-center justify-center
                            rounded-2xl bg-zinc-950 text-lg
                            font-bold text-white">

                    {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                </div>

                <div class="min-w-0">
                    <p class="truncate text-sm font-bold">
                        {{ auth()->user()->name ?? 'Administrator' }}
                    </p>

                    <p class="mt-1 text-xs text-zinc-500">
                        {{ auth()->user()->email ?? 'Akun demo' }}
                    </p>

                    <span
                        class="mt-2 inline-flex rounded-md
                                 bg-zinc-100 px-2 py-1
                                 text-[9px] font-semibold text-zinc-600">
                        {{ auth()->user()->role ?? 'Pengguna' }}
                    </span>
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-zinc-200 p-3">
                <div class="text-[10px] font-semibold text-zinc-500">
                    STATUS AKUN
                </div>

                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="h-2 w-2 rounded-full bg-zinc-800"></span>
                    Akun terautentikasi
                </div>
            </div>

            <div class="mt-5 flex gap-2">

                <button type="button" data-close-modal
                    class="h-10 flex-1 rounded-xl
                               border border-zinc-200 text-xs
                               font-semibold hover:bg-zinc-50">
                    Tutup
                </button>

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf

                        <button type="submit"
                            class="h-10 w-full rounded-xl
                                       bg-zinc-950 px-3 text-xs
                                       font-semibold text-white
                                       hover:bg-zinc-800">
                            Keluar
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </section>
</div>
