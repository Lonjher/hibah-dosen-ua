{{-- resources/views/layouts/sidebar.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Cegah flash warna salah: set dark/light SEBELUM CSS & Alpine dimuat --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @include('partials.head')

    {{-- Font: DM Sans untuk body, Manrope untuk heading --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:400,500,600,700,800&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --font-heading: 'Manrope', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --font-body: 'DM Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        .font-heading,
        .font-title-sm {
            font-family: var(--font-heading);
        }

        body,
        .font-body,
        .font-label-sm {
            font-family: var(--font-body);
        }
    </style>
</head>

<body class="min-h-screen bg-white font-body text-on-surface antialiased dark:bg-zinc-950">

    {{-- Background pattern & dekorasi --}}
    <div class="pointer-events-none fixed inset-0 -z-10 bg-pattern-dots dark:opacity-40"></div>
    <div
        class="pointer-events-none fixed -top-16 left-1/4 -z-10 h-96 w-96 rounded-full bg-primary/5 blur-3xl dark:bg-primary/10">
    </div>
    <div
        class="pointer-events-none fixed top-40 right-10 -z-10 h-80 w-80 rounded-full bg-secondary/10 blur-3xl dark:bg-secondary/15">
    </div>

    <div x-data="{ sidebarOpen: false }" class="min-h-screen w-full bg-surface relative dark:bg-zinc-950">

        {{-- Overlay (mobile, saat sidebar terbuka) --}}
        <div x-show="sidebarOpen" x-transition.opacity x-on:click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-zinc-950/50 backdrop-blur-sm lg:hidden" style="display: none;"></div>

        {{-- ======================= SIDEBAR KIRI ======================= --}}
        <aside x-data="{ usersOpen: true }" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-48 flex flex-col justify-between bg-white/70 backdrop-blur-md border-r border-white/80 shadow-xs select-none transition-transform duration-200 ease-in-out lg:translate-x-0 dark:bg-zinc-900/70 dark:border-zinc-800">
            <div class="flex flex-col h-full overflow-y-auto">

                {{-- Header & Logo --}}
                <div
                    class="p-2.5 border-b border-white/60 flex items-center gap-2 bg-white/60 dark:border-zinc-800 dark:bg-zinc-900/60">
                    <img alt="LPPM Annuqayah Logo" class="w-7 h-7 rounded-full object-contain shrink-0"
                        src="{{ asset('images/logo.webp') }}" />
                    <div class="flex flex-col min-w-0 flex-1">
                        <span
                            class="font-heading text-[12px] font-bold text-on-surface leading-tight tracking-tight truncate dark:text-zinc-100">HIBAH
                            DOSEN</span>
                        <span
                            class="font-body text-[9px] text-on-surface-variant leading-none truncate dark:text-zinc-400">Universitas
                            Annuqayah</span>
                    </div>
                    {{-- Tombol tutup, hanya tampil di mobile --}}
                    <button type="button" x-on:click="sidebarOpen = false"
                        class="p-1 rounded-md text-on-surface-variant hover:text-emerald-500 hover:bg-surface-container-low transition-colors lg:hidden dark:text-zinc-400 dark:hover:text-emerald-400 dark:hover:bg-zinc-800">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>

                {{-- Navigasi --}}
                <div class="flex flex-col p-2 gap-1.5 flex-1">

                    {{-- Dashboard --}}
                    <x-sidebar-link href="dashboard" title="Dashboard" icon="squares-2x2" />

                    @can('superadminOrAdmin')
                        {{-- Group Administrator --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">Administrator</span>

                            <x-sidebar-link href="admin.manage-periods" title="Periods" icon="calendar-days" />
                            <x-sidebar-link href="admin.manage-schemes" title="Schemes" icon="rectangle-group" />

                            <div class="flex flex-col">
                                <button type="button" x-on:click="usersOpen = !usersOpen"
                                    class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low/50 font-heading text-[11px] font-medium transition-all group dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/50">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.users
                                            class="size-4 text-outline group-hover:text-emerald-500 transition-colors shrink-0 dark:group-hover:text-emerald-400" />
                                        <span>Users</span>
                                    </div>
                                    <flux:icon.chevron-down
                                        class="size-3.5 text-outline transition-transform duration-200 shrink-0 dark:text-zinc-500"
                                        x-bind:class="usersOpen && 'rotate-180'" />
                                </button>
                                <div x-show="usersOpen" x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    class="flex flex-col gap-0.5 border-l border-white/80 ml-3.5 my-0.5 dark:border-zinc-800">
                                    <x-sidebar-link href="admin.manage-admins" title="Admins" icon="shield-check" />
                                    <x-sidebar-link href="admin.manage-reviewers" title="Reviewers"
                                        icon="clipboard-document-check" />
                                    <x-sidebar-link href="admin.manage-users" title="Users" icon="user-circle" />
                                </div>
                            </div>
                        </div>
                        {{-- Group Internal Data --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">Internal
                                Data</span>
                            <x-sidebar-link href="admin.internal.manage-researches" title="Research" icon="beaker" />
                            <a href="#"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low/50 font-heading text-[11px] font-medium transition-all group dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/50">
                                <flux:icon.hand-raised
                                    class="size-4 text-outline group-hover:text-emerald-500 transition-colors shrink-0 dark:group-hover:text-emerald-400" />
                                <span>Dedication</span>
                            </a>
                        </div>

                        {{-- Group External Data --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">External
                                Data</span>
                            <a href="#"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low/50 font-heading text-[11px] font-medium transition-all group dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/50">
                                <flux:icon.globe-alt
                                    class="size-4 text-outline group-hover:text-emerald-500 transition-colors shrink-0 dark:group-hover:text-emerald-400" />
                                <span>Research</span>
                            </a>
                            <a href="#"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low/50 font-heading text-[11px] font-medium transition-all group dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/50">
                                <flux:icon.gift
                                    class="size-4 text-outline group-hover:text-emerald-500 transition-colors shrink-0 dark:group-hover:text-emerald-400" />
                                <span>Dedication</span>
                            </a>
                        </div>
                    @endcan
                    @can('reviewer')
                        {{-- Group Internal Data --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">Internal
                                Data</span>
                            <x-sidebar-link href="reviewer.review-proposal" title="Research" icon="beaker" />
                            <x-sidebar-link href="reviewer.review-progress-report" title="Progress Report" icon="beaker" />
                            <a href="#"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low/50 font-heading text-[11px] font-medium transition-all group dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/50">
                                <flux:icon.hand-raised
                                    class="size-4 text-outline group-hover:text-emerald-500 transition-colors shrink-0 dark:group-hover:text-emerald-400" />
                                <span>Dedication</span>
                            </a>
                        </div>
                    @endcan
                    @can('user')
                        {{-- Group Internal Data --}}
                        <div class="flex flex-col gap-0.5">
                            <span
                                class="px-2 py-1 font-body text-[9px] uppercase tracking-wider text-outline font-bold dark:text-zinc-500">Internal
                                Data</span>
                            <x-sidebar-link href="user.internal.manage-researches" title="Research" icon="beaker" />
                            <x-sidebar-link href="user.internal.manage-dedications" title="Dedication" icon="hand-raised" />
                        </div>
                    @endcan
                </div>

                {{-- User Profile --}}
                <div
                    class="p-2 border-t border-white/60 bg-white/80 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/80">
                    @auth
                        <div
                            class="flex items-center gap-2 p-1.5 rounded-lg bg-surface-container-low/60 dark:bg-zinc-800/60">
                            <div
                                class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center font-heading font-bold text-[11px] shadow-xs shrink-0">
                                {{ auth()->user()->initials() ?? 'DK' }}
                            </div>
                            <div class="flex flex-col min-w-0 flex-1">
                                <span
                                    class="font-heading text-[10px] font-semibold text-on-surface truncate dark:text-zinc-100">{{ auth()->user()->name }}</span>
                                <span class="font-body text-[9px] text-on-surface-variant truncate dark:text-zinc-400">NIDN:
                                    {{ auth()->user()->nidn ?? '...' }}</span>
                                <div
                                    class="flex items-center gap-1 mt-0.5 font-body text-[8px] text-emerald-600 font-bold dark:text-emerald-300">
                                    <span>SINTA {{ auth()->user()->sinta_score ?? '...' }}</span>
                                    <span class="text-outline-variant dark:text-zinc-600">•</span>
                                    <span class="truncate">{{ auth()->user()->fakultas ?? '...' }}</span>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="p-1 text-on-surface-variant hover:text-red-500 hover:bg-surface-container rounded transition-colors dark:text-zinc-400 dark:hover:text-red-400 dark:hover:bg-zinc-800"
                                    title="Keluar">
                                    <flux:icon.arrow-right-start-on-rectangle class="size-4" />
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </aside>

        {{-- ======================= KANAN: HEADER + KONTEN ======================= --}}
        <div class="flex flex-col min-h-screen bg-surface transition-[margin] duration-200 lg:ml-48 dark:bg-zinc-950">
            <header
                class="sticky top-0 z-30 bg-white/75 backdrop-blur-md border-b border-white/60 shadow-[0_1px_8px_rgba(0,0,0,0.04)] px-4 lg:px-6 py-2 flex items-center justify-between gap-4 dark:bg-zinc-900/75 dark:border-zinc-800">
                {{-- Hamburger (mobile) + Breadcrumb --}}
                <div class="flex items-center gap-2 min-w-0 font-body text-[11px]">
                    <button type="button" x-on:click="sidebarOpen = true"
                        class="p-1 -ml-1 rounded-md text-on-surface-variant hover:text-emerald-500 hover:bg-surface-container-high transition-colors lg:hidden dark:text-zinc-400 dark:hover:text-emerald-400 dark:hover:bg-zinc-800">
                        <flux:icon.bars-3 class="size-5" />
                    </button>
                    <flux:icon.academic-cap class="size-4 text-emerald-500 hidden sm:block shrink-0" />
                    <span class="text-on-surface-variant font-medium hidden sm:inline dark:text-zinc-400">LPPM</span>
                    <span class="text-outline-variant hidden sm:inline dark:text-zinc-600">/</span>
                    <span class="text-on-surface-variant font-medium hidden sm:inline dark:text-zinc-400">Dosen</span>
                    <span class="text-outline-variant hidden sm:inline dark:text-zinc-600">/</span>
                    <span class="text-on-surface font-semibold truncate font-heading dark:text-zinc-100">Dashboard
                        Riset & Monev</span>
                </div>

                {{-- Search & Badges --}}
                <div class="flex items-center gap-3 flex-1 justify-end max-w-xl">
                    <x-input-search name="q" id="search-proposal" size="sm" placeholder="Cari usulan, skema, luaran..."
                        value="{{ request('q') }}" />
                    <div class="flex items-center gap-1">
                        <livewire:period-badge />

                        {{-- Toggle Dark Mode --}}
                        <button type="button"
                            x-on:click="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';"
                            class="p-1 rounded-md text-on-surface-variant hover:text-emerald-500 hover:bg-surface-container-high transition-colors dark:text-zinc-400 dark:hover:text-emerald-400 dark:hover:bg-zinc-800">
                            <flux:icon.sun class="size-4 hidden dark:block" />
                            <flux:icon.moon class="size-4 dark:hidden" />
                        </button>

                        <button
                            class="relative p-1 rounded-md text-on-surface-variant hover:text-emerald-500 hover:bg-surface-container-high transition-colors dark:text-zinc-400 dark:hover:text-emerald-400 dark:hover:bg-zinc-800">
                            <flux:icon.bell class="size-4" />
                            <span
                                class="absolute top-1 right-1 w-2 h-2 rounded-full bg-red-500 ring-2 ring-surface-container-lowest dark:ring-zinc-800"></span>
                        </button>
                    </div>
                </div>
            </header>

            {{-- Slot utama --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast / Scripts --}}
    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
